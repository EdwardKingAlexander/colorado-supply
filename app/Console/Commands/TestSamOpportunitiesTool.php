<?php

namespace App\Console\Commands;

use App\Mcp\Servers\Business\Tools\FetchSamOpportunitiesTool;
use Illuminate\Console\Command;

class TestSamOpportunitiesTool extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sam:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SAM.gov opportunities tool';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing SAM.gov Opportunities Tool...');
        $this->newLine();

        $tool = new FetchSamOpportunitiesTool;

        $this->info('Fetching Colorado opportunities (last 7 days)...');
        $result = $tool->execute([
            'state_code' => 'CO',
            'days_back' => 7,
            'limit' => 10,
        ]);

        $data = json_decode($result, true);

        if ($data['success']) {
            $this->info('✅ Success!');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Records', $data['summary']['total_records']],
                    ['Returned', $data['summary']['returned']],
                    ['Date Range', $data['query']['date_range']],
                    ['State', $data['query']['state_code']],
                ]
            );

            if (count($data['opportunities']) > 0) {
                $this->newLine();
                $this->info('Sample Opportunities:');
                foreach (array_slice($data['opportunities'], 0, 3) as $opp) {
                    $this->line("- {$opp['title']} ({$opp['notice_type']})");
                }
            }

            return Command::SUCCESS;
        }

        if ($data['partial_success'] ?? false) {
            $this->warn('⚠️ Partial success – some NAICS queries failed.');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total After Dedup', $data['summary']['total_after_dedup'] ?? 0],
                    ['Returned', $data['summary']['returned'] ?? 0],
                    ['Successful NAICS', $data['summary']['successful_naics_count'] ?? 0],
                    ['Failed NAICS', $data['summary']['failed_naics_count'] ?? 0],
                ]
            );

            if (! empty($data['summary']['failed_naics'])) {
                $this->newLine();
                $this->info('Failed NAICS:');
                foreach ($data['summary']['failed_naics'] as $fail) {
                    $naics = $fail['naics'] ?? 'unknown';
                    $msg = $fail['message'] ?? ($fail['error'] ?? 'Unknown error');
                    $this->line("- {$naics}: {$msg}");
                }
            }

            return Command::SUCCESS;
        }

        $errorMessage = $data['error'] ?? 'Unknown error';

        $this->error('❌ Failed: '.$errorMessage);
        if (isset($data['status_code'])) {
            $this->line('Status Code: '.$data['status_code']);
        }
        if (isset($data['message'])) {
            $this->line('Response: '.substr($data['message'], 0, 200));
        }
        if (isset($data['url'])) {
            $this->line('URL: '.$data['url']);
        }
        if (isset($data['params'])) {
            $this->line('Params: '.json_encode($data['params']));
        }

        return Command::FAILURE;
    }
}
