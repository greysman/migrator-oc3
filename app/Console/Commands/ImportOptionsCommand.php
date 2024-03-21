<?php

namespace App\Console\Commands;

use App\Jobs\UploadImageJob;
use App\Models\ExporterRequest;
use App\Models\Option;
use App\Models\OptionDescription;
use App\Models\OptionValue;
use App\Models\OptionValueDescription;
use Illuminate\Console\Command;

class ImportOptionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrator:import-options';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import attributes from another Opencart2 store';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $response = ExporterRequest::make(env('EXPORTER_URL') . 'options');

        $this->info('Retrieved ' . $response['meta']['total'] . ' options');
        $this->info('Importing...');
        $this->newLine(2);

        $bar = $this->output->createProgressBar($response['meta']['total']);

        $bar->start();

        foreach ($response['data'] as $key => $option) {
            $bar->advance();
            // dd(Option::find($option['option_id']));
            // if (Option::find($option['option_id'])) 
            //     continue;

            $optionInstance = Option::firstOrcreate([
                'option_id' => $option['option_id'],
            ],
            $option);

            if ($optionInstance->wasRecentlyCreated)
                $optionInstance
                    ->description()
                    ->save(new OptionDescription($option['description']));
        
            if (isset($option['values']) && $option['values']) {
                foreach ($option['values'] as $key => $value) {
                    if (!OptionValue::find($value['option_value_id'])) {
                        $valueInstance = OptionValue::create($value);
                        $valueInstance
                            ->description()
                            ->save(new OptionValueDescription($value['description']));
                        
                            if ($value['image']) {
                            UploadImageJob::dispatch($valueInstance, $value['image'])
                                ->onQueue('low');
                        }
                    }
                }
            }
        }
            
        $bar->finish();
        $this->newLine(2);
        return Command::SUCCESS;
    }
}
