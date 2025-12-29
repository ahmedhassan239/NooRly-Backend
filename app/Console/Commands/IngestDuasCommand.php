<?php

namespace App\Console\Commands;

use App\Domain\Duas\Services\DuasIngestionService;
use App\Domain\Ingestion\Services\IngestionRunService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class IngestDuasCommand extends Command
{
    protected $signature = 'ingest:duas {--langs=ar,en : Languages to process}';

    protected $description = 'Ingest Duas from local JSON files';

    public function handle(
        DuasIngestionService $service,
        IngestionRunService $runService
    ): int {
        $langs = explode(',', $this->option('langs'));

        $this->info("Starting Duas ingestion...");

        $run = $runService->startRun('duas_ingestion');

        try {
            $stats = $service->ingest($langs);

            $runService->updateStats($run->id, $stats);
            $runService->finishRun($run->id, 'success');

            $this->info("✓ Duas ingestion completed!");
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Processed', $stats['total_processed']],
                    ['Created', $stats['created']],
                    ['Updated', $stats['updated']],
                ]
            );

            return self::SUCCESS;

        } catch (\Exception $e) {
            $runService->finishRun($run->id, 'fail', $e->getMessage());

            $this->error("✗ Ingestion failed: " . $e->getMessage());
            Log::error("Duas ingestion failed", ['error' => $e->getMessage()]);

            return self::FAILURE;
        }
    }
}
