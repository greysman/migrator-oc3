<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearOpencartCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrator:clear-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear Opencart Store\'s cache';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $pathToCache = base_path() . '/' . env('OC_CACHE_PATH', '/../public_html/system/storage/cache');
        $path = realpath($pathToCache);

        exec('rm ' . $path . '/cache.*');

        DB::table('ocfilter_cache')->delete();

        $this->info('Opencart cache cleared!');

        return Command::SUCCESS;
    }
}
