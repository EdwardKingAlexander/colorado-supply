<?php

namespace App\Mcp\Servers\Business\Tools;

use App\Mcp\Servers\Tool; // Assuming Tool is in app/Mcp/Servers, will adjust if needed.
use App\Services\NsnLookupService;
use Illuminate\Support\Facades\Log;

class FetchNsnDataTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'fetch-nsn-data';

    /**
     * The tool's description.
     */
    protected string $description = 'Fetches National Stock Number (NSN) data including manufacturer, suppliers, and procurement history from external sources and saves it to the database.';

    /**
     * Define the tool's input schema.
     */
    protected function inputSchema(): array
    {
        return [
            'nsn' => [
                'type' => 'string',
                'description' => 'The 13-digit National Stock Number (NSN) to fetch data for (e.g., "1234-56-789-0123" or "1234567890123")',
                'required' => true,
            ],
            // Future parameters could include 'force_refresh', 'source_preference', etc.
        ];
    }

    /**
     * Get the output schema documentation.
     */
    protected function outputSchema(): array
    {
        return [
            'success' => [
                'type' => 'boolean',
                'description' => 'True if NSN data was successfully fetched and persisted, false otherwise.',
            ],
            'nsn' => [
                'type' => 'string',
                'description' => 'The NSN that was processed.',
            ],
            'mil_spec_part_id' => [
                'type' => 'integer',
                'description' => 'The ID of the MilSpecPart record in the database, if successful.',
                'nullable' => true,
            ],
            'message' => [
                'type' => 'string',
                'description' => 'A descriptive message about the outcome of the operation.',
            ],
            'error' => [
                'type' => 'string',
                'description' => 'An error message if the operation failed.',
                'nullable' => true,
            ],
        ];
    }

    /**
     * Example responses for different scenarios.
     */
    protected function exampleResponses(): array
    {
        return [
            'success' => [
                'success' => true,
                'nsn' => '1234-56-789-0123',
                'mil_spec_part_id' => 1,
                'message' => 'NSN data fetched and persisted successfully.',
                'error' => null,
            ],
            'failure' => [
                'success' => false,
                'nsn' => 'INVALID_NSN',
                'mil_spec_part_id' => null,
                'message' => 'Failed to fetch or persist NSN data.',
                'error' => 'NSN not found or external service unavailable.',
            ],
        ];
    }

    /**
     * Execute the tool.
     */
    public function execute(array $inputs): string
    {
        $nsn = $inputs['nsn'];
        $nsnLookupService = app(NsnLookupService::class);

        Log::info("Executing FetchNsnDataTool for NSN: {$nsn}");

        try {
            $milSpecPart = $nsnLookupService->fetchAndPersistNsnData($nsn);

            if ($milSpecPart) {
                $response = [
                    'success' => true,
                    'nsn' => $milSpecPart->nsn,
                    'mil_spec_part_id' => $milSpecPart->id,
                    'message' => 'NSN data fetched and persisted successfully.',
                    'error' => null,
                ];
            } else {
                $response = [
                    'success' => false,
                    'nsn' => $nsn,
                    'mil_spec_part_id' => null,
                    'message' => 'Failed to fetch or persist NSN data. Check logs for details.',
                    'error' => 'No data found or an issue occurred during persistence.',
                ];
            }
        } catch (\Exception $e) {
            Log::error("FetchNsnDataTool failed for NSN: {$nsn}", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $response = [
                'success' => false,
                'nsn' => $nsn,
                'mil_spec_part_id' => null,
                'message' => 'An unexpected error occurred while processing NSN data.',
                'error' => $e->getMessage(),
            ];
        }

        return json_encode($response, JSON_PRETTY_PRINT);
    }
}
