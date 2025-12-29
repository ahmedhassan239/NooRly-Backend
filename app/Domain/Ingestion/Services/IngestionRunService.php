<?php

namespace App\Domain\Ingestion\Services;

use App\Domain\Ingestion\IngestionRun;

class IngestionRunService
{
    public function startRun(string $jobName): IngestionRun
    {
        return IngestionRun::create([
            'job_name' => $jobName,
            'status' => 'running',
            'started_at' => now(),
            'stats' => [],
            'checkpoint' => [],
        ]);
    }

    public function updateCheckpoint(int $runId, array $checkpoint): void
    {
        IngestionRun::where('id', $runId)->update([
            'checkpoint' => $checkpoint,
        ]);
    }

    public function updateStats(int $runId, array $stats): void
    {
        IngestionRun::where('id', $runId)->update([
            'stats' => $stats,
        ]);
    }

    public function finishRun(int $runId, string $status, ?string $errorMessage = null): void
    {
        IngestionRun::where('id', $runId)->update([
            'status' => $status,
            'finished_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    public function getLastRun(string $jobName): ?IngestionRun
    {
        return IngestionRun::where('job_name', $jobName)
            ->latest('id')
            ->first();
    }
}
