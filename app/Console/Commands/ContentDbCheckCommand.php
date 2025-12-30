<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ContentDbCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'content:db-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check connections to external Quran and Hadith databases';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking external database connections...');

        $connections = ['mysql_quran', 'mysql_hadith'];

        foreach ($connections as $conn) {
            $this->checkConnection($conn);
        }
    }

    private function checkConnection($connectionName)
    {
        try {
            $config = config("database.connections.{$connectionName}");
            $this->comment("Testing {$connectionName} ({$config['host']}:{$config['port']} -> {$config['database']})...");
            
            DB::connection($connectionName)->getPdo();
            
            $this->info("✔ {$connectionName}: OK");
        } catch (\Exception $e) {
            $this->error("✘ {$connectionName}: FAILED");
            $this->error("  " . $e->getMessage());
        }
    }
}
