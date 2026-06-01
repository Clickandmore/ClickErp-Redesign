<?php

namespace App\Console\Commands;

use App\Services\Zoho\SalesIqClient;
use Illuminate\Console\Command;

class SalesIqVisitorsCommand extends Command
{
    protected $signature = 'salesiq:visitors {--limit=10 : Number of visitors to fetch} {--next_key=} {--prev_key=}';

    protected $description = 'Test fetching visitors from Zoho SalesIQ';

    public function handle(SalesIqClient $salesIq): int
    {
        $payload = $salesIq->visitors([
            'limit' => (int) $this->option('limit'),
            'next_key' => $this->option('next_key'),
            'prev_key' => $this->option('prev_key'),
        ]);

        $this->info('Zoho SalesIQ visitors fetched.');
        $this->line('Count: ' . count($payload['data'] ?? []));

        foreach (array_slice($payload['data'] ?? [], 0, 5) as $visitor) {
            $this->line(sprintf(
                '- %s | %s | %s',
                $visitor['name'] ?? $visitor['id'] ?? 'Unknown',
                $visitor['phone_number'] ?? '-',
                $visitor['email_id'] ?? '-'
            ));
        }

        if (!empty($payload['next_key'])) {
            $this->line('next_key: ' . $payload['next_key']);
        }

        if (!empty($payload['prev_key'])) {
            $this->line('prev_key: ' . $payload['prev_key']);
        }

        return self::SUCCESS;
    }
}
