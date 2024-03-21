<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportStartCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrator:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from another Opencart2 store';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->call('migrator:import-categories');
        $this->call('migrator:import-manufacturers');
        $this->call('migrator:import-attributes');
        $this->call('migrator:import-options');
        $this->call('migrator:import-products');
        
        return Command::SUCCESS;
    }
}
