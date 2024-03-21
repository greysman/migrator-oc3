<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\CategoryDescription;
use App\Models\ExporterRequest;
use Illuminate\Console\Command;

class ImportCategoriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrator:import-categories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import categories from another Opencart2 store';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $response = ExporterRequest::make(env('EXPORTER_URL') . 'categories');

        $this->info('Retrieved ' . $response['meta']['total'] . ' categories');
        $this->newLine(2);

        $bar = $this->output->createProgressBar($response['meta']['total']);

        $bar->start();

        foreach ($response['data'] as $key => $category) {
            $bar->advance();

            if (Category::find($category['category_id'])) continue;

            $description = new CategoryDescription($category['description']);

            $categoryInstance = Category::create($category);
            $categoryInstance->description()->save($description);
        }

        $bar->finish();
        $this->newLine(2);
        return Command::SUCCESS;
    }
}
