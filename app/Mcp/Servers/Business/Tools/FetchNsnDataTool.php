<?php

namespace App\Mcp\Servers\Business\Tools;

use App\Mcp\Servers\Tool;
use App\Services\NsnLookupService;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class FetchNsnDataTool extends Tool
{
    protected string $name = 'fetch-nsn-data';

    protected string $description = 'Read-only PUB LOG lookup by NSN, NIIN, exact part number or CAGE. Returns paginated references and dated company snapshots, not confirmed production or suppliers. Does not save curated parts. Legacy nsn input is accepted; mil_spec_part_id is always null.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->enum(['nsn', 'niin', 'part', 'cage'])->description('Search type; defaults to nsn.'),
            'query' => $schema->string()->description('Number/code to search. Use query or legacy nsn.'),
            'nsn' => $schema->string()->description('Legacy NSN input.'),
            'page' => $schema->integer()->min(1)->description('Reference page, starting at 1.'),
            'snapshot_id' => $schema->integer()->min(1)->description('Reuse source.import_id for consistent pagination.'),
            'relationship_page' => $schema->integer()->min(1)->description('Status context page for an NSN/NIIN.'),
        ];
    }

    public function execute(array $inputs): string
    {
        $type = $inputs['type'] ?? 'nsn';
        $query = $inputs['query'] ?? $inputs['nsn'] ?? '';
        $valid = is_string($type) && is_string($query);
        foreach (['page', 'snapshot_id', 'relationship_page'] as $field) {
            if (isset($inputs[$field]) && (! is_int($inputs[$field]) || $inputs[$field] < 1)) {
                $valid = false;
            }
        }
        $result = $valid ? app(NsnLookupService::class)->lookup($type, $query, $inputs['page'] ?? 1, $inputs['snapshot_id'] ?? null, $inputs['relationship_page'] ?? 1)
            : ['contract_version' => 2, 'success' => false, 'status' => 'invalid_input', 'error' => 'invalid_input', 'message' => 'Invalid lookup parameters.', 'mil_spec_part_id' => null, 'saved' => false];

        return json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    }
}
