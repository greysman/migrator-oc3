<?php

namespace App\Console\Commands;

use App\Jobs\UploadImageJob;
use App\Models\ExporterRequest;
use App\Models\Manufacturer;
use App\Models\ManufacturerDescription;
use App\Models\ManufacturerStore;
use App\Services\SeoUrlService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ImportManufacturersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrator:import-manufacturers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import manufacturers from another Opencart2 store';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $response = ExporterRequest::make(env('EXPORTER_URL') . 'manufacturers');

        $this->info('Retrieved ' . $response['meta']['total'] . ' manufacturers');
        $this->info('Importing...');
        $this->newLine(2);

        $bar = $this->output->createProgressBar($response['meta']['total']);

        $bar->start();

        foreach ($response['data'] as $key => $manufacturer) {
            $bar->advance();

            if (Manufacturer::find($manufacturer['manufacturer_id'])) 
                continue;

            $store = new ManufacturerStore(['store_id' => 0]);

            $manufacturerInstance = Manufacturer::create($manufacturer);
            $manufacturerInstance->store()->save($store);

            SeoUrlService::make(
                $manufacturerInstance->manufacturer_id, 
                'manufacturer_id=', 
                $manufacturerInstance->name
            );

            if ($manufacturer['image'])
                UploadImageJob::dispatch($manufacturerInstance, $manufacturer['image'])
                    ->onQueue('low');

        }
            
        $bar->finish();

        $this->newLine(2);
        $this->call('migrator:clear-cache');
        $this->newLine(2);
        return Command::SUCCESS;
    }
}
