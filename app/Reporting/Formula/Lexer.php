<?php

namespace App\Reporting\Formula;

final class Lexer
{
    private const OPERATORS = ['<=', '>=', '!=', '<>', '+', '-', '*', '/', '(', ')', ',', '<', '>', '='];

    /**
     * @return list<Token> always ending with an END token
     *
     * @throws FormulaException
     */
    public static function tokenize(string $source): array
    {
        $tokens = [];
        $length = strlen($source);
        $offset = 0;

        while ($offset < $length) {
            $rest = substr($source, $offset);

            if (preg_match('/^\s+/', $rest, $match)) {
                $offset += strlen($match[0]);

                continue;
            }

            if (preg_match('/^(\d+(\.\d+)?|\.\d+)/', $rest, $match)) {
                $tokens[] = new Token(Token::NUMBER, $match[0], $offset);
                $offset += strlen($match[0]);

                continue;
            }

            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?/', $rest, $match)) {
                $tokens[] = new Token(Token::IDENTIFIER, $match[0], $offset);
                $offset += strlen($match[0]);

                continue;
            }

            foreach (self::OPERATORS as $operator) {
                if (str_starts_with($rest, $operator)) {
                    $tokens[] = new Token(Token::OPERATOR, $operator === '<>' ? '!=' : $operator, $offset);
                    $offset += strlen($operator);

                    continue 2;
                }
            }

            throw new FormulaException("Unexpected character \"{$rest[0]}\"", $offset);
        }

        $tokens[] = new Token(Token::END, '', $length);

        return $tokens;
    }
}
