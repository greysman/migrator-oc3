<?php

namespace App\Console\Commands;

use App\Jobs\UploadImageJob;
use App\Models\Attribute;
use App\Models\AttributeDescription;
use App\Models\AttributeGroup;
use App\Models\AttributeGroupDescription;
use App\Models\ExporterRequest;
use Illuminate\Console\Command;

class ImportAttributesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrator:import-attributes';

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
        $response = ExporterRequest::make(env('EXPORTER_URL') . 'attributes');

        $this->info('Retrieved ' . $response['meta']['total'] . ' attributes');
        $this->info('Importing...');
        $this->newLine(2);

        $bar = $this->output->createProgressBar($response['meta']['total']);

        $bar->start();

        foreach ($response['data'] as $key => $attribute) {
            $bar->advance();
            
            if (Attribute::find($attribute['attribute_id'])) 
                continue;

            if (isset($attribute['group']) && !AttributeGroup::find($attribute['group']['attribute_group_id'])) {
                $group = $attribute['group'];
                
                $groupInstance = AttributeGroup::create($group);
                $groupInstance
                    ->description()
                    ->save(new AttributeGroupDescription($group['description']));
            }

            $attributeInstance = Attribute::create($attribute);
            $attributeInstance
                ->description()
                ->save(new AttributeDescription($attribute['description']));
            
        }
            
        $bar->finish();
        $this->newLine(2);
        return Command::SUCCESS;
    }
}
