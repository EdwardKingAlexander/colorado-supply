<?php

namespace App\Reporting\Formula;

use App\Reporting\Aggregate;

/**
 * Recursive-descent parser for report formulas:
 *
 *   comparison := additive (("<" | "<=" | ">" | ">=" | "=" | "!=") additive)?
 *   additive   := term (("+" | "-") term)*
 *   term       := unary (("*" | "/") unary)*
 *   unary      := "-" unary | primary
 *   primary    := NUMBER | "(" comparison ")" | NAME "(" args ")" | NAME
 */
final class Parser
{
    public const MAX_LENGTH = 500;

    public const MAX_NODES = 100;

    /** name => [min args, max args or null for unlimited] */
    public const FUNCTIONS = [
        'ROUND' => [1, 2],
        'ABS' => [1, 1],
        'MIN' => [2, null],
        'MAX' => [2, null],
        'IF' => [3, 3],
        'COALESCE' => [1, null],
        'NULLIF' => [2, 2],
    ];

    /** @var list<Token> */
    private array $tokens;

    private int $index = 0;

    private int $nodes = 0;

    /**
     * @throws FormulaException
     */
    public static function parse(string $source): Node
    {
        if (trim($source) === '') {
            throw new FormulaException('Enter a formula');
        }

        if (strlen($source) > self::MAX_LENGTH) {
            throw new FormulaException('Formulas can be at most '.self::MAX_LENGTH.' characters', self::MAX_LENGTH);
        }

        $parser = new self;
        $parser->tokens = Lexer::tokenize($source);

        $node = $parser->comparison();
        $parser->expect(Token::END, null, 'Unexpected');

        return $node;
    }

    private function comparison(): Node
    {
        $left = $this->additive();
        $token = $this->peek();

        if ($token->type === Token::OPERATOR && in_array($token->value, ['<', '<=', '>', '>=', '=', '!='], true)) {
            $this->index++;

            return $this->node(new BinaryNode($token->value, $left, $this->additive(), $token->position));
        }

        return $left;
    }

    private function additive(): Node
    {
        $node = $this->term();

        while (($token = $this->peek())->type === Token::OPERATOR && in_array($token->value, ['+', '-'], true)) {
            $this->index++;
            $node = $this->node(new BinaryNode($token->value, $node, $this->term(), $token->position));
        }

        return $node;
    }

    private function term(): Node
    {
        $node = $this->unary();

        while (($token = $this->peek())->type === Token::OPERATOR && in_array($token->value, ['*', '/'], true)) {
            $this->index++;
            $node = $this->node(new BinaryNode($token->value, $node, $this->unary(), $token->position));
        }

        return $node;
    }

    private function unary(): Node
    {
        $token = $this->peek();

        if ($token->is(Token::OPERATOR, '-')) {
            $this->index++;

            return $this->node(new UnaryNode($this->unary(), $token->position));
        }

        return $this->primary();
    }

    private function primary(): Node
    {
        $token = $this->next();

        if ($token->type === Token::NUMBER) {
            return $this->node(new NumberNode((float) $token->value, $token->position));
        }

        if ($token->is(Token::OPERATOR, '(')) {
            $node = $this->comparison();
            $this->expect(Token::OPERATOR, ')', 'Missing closing parenthesis before');

            return $node;
        }

        if ($token->type !== Token::IDENTIFIER) {
            throw new FormulaException($token->type === Token::END ? 'The formula ends too soon' : "Unexpected \"{$token->value}\"", $token->position);
        }

        if (! $this->peek()->is(Token::OPERATOR, '(')) {
            return $this->node(new ReferenceNode($token->value, $token->position));
        }

        $this->index++;
        $arguments = $this->arguments();

        return $this->call(strtoupper($token->value), $arguments, $token);
    }

    /**
     * @return list<Node>
     */
    private function arguments(): array
    {
        $arguments = [];

        if ($this->peek()->is(Token::OPERATOR, ')')) {
            $this->index++;

            return $arguments;
        }

        do {
            $arguments[] = $this->comparison();
            $separator = $this->next();
        } while ($separator->is(Token::OPERATOR, ','));

        if (! $separator->is(Token::OPERATOR, ')')) {
            throw new FormulaException('Expected "," or ")"', $separator->position);
        }

        return $arguments;
    }

    /**
     * @param  list<Node>  $arguments
     */
    private function call(string $name, array $arguments, Token $token): Node
    {
        $aggregate = Aggregate::fromFormulaName($name);
        $singleField = count($arguments) === 1 && $arguments[0] instanceof ReferenceNode && $arguments[0]->isField();

        // MIN/MAX of one field are aggregates; of several values, scalar functions.
        if ($aggregate !== null && (! in_array($aggregate, [Aggregate::Min, Aggregate::Max], true) || $singleField)) {
            if (! $singleField) {
                throw new FormulaException("{$name}() takes one field, like {$name}(line.line_total)", $token->position);
            }

            $this->nodes--; // the field reference is folded into the aggregate node

            return $this->node(new AggregateNode($aggregate, $arguments[0]->name, $token->position));
        }

        if (! isset(self::FUNCTIONS[$name])) {
            throw new FormulaException("Unknown function {$name}()", $token->position);
        }

        [$min, $max] = self::FUNCTIONS[$name];

        if (count($arguments) < $min || ($max !== null && count($arguments) > $max)) {
            $expected = $max === null ? "at least {$min}" : ($min === $max ? (string) $min : "{$min} or {$max}");

            throw new FormulaException("{$name}() takes {$expected} values", $token->position);
        }

        return $this->node(new CallNode($name, $arguments, $token->position));
    }

    private function node(Node $node): Node
    {
        if (++$this->nodes > self::MAX_NODES) {
            throw new FormulaException('The formula is too long', $node->position);
        }

        return $node;
    }

    private function peek(): Token
    {
        return $this->tokens[$this->index];
    }

    private function next(): Token
    {
        $token = $this->tokens[$this->index];

        if ($token->type !== Token::END) {
            $this->index++;
        }

        return $token;
    }

    private function expect(string $type, ?string $value, string $message): void
    {
        $token = $this->next();

        if (! $token->is($type, $value)) {
            $found = $token->type === Token::END ? 'the end of the formula' : "\"{$token->value}\"";

            throw new FormulaException("{$message} {$found}", $token->position);
        }
    }
}
