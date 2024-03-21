<?php

namespace App\Console\Commands;

use App\Jobs\UploadImageJob;
use App\Models\Attribute;
use App\Models\ExporterRequest;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductCategory;
use App\Models\ProductDescription;
use App\Models\ProductDiscount;
use App\Models\ProductImage;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductOptionValueWarehouse;
use App\Models\ProductSpecial;
use App\Services\SeoUrlService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrator:import-products {uri?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import products from another Opencart2 store';

    protected $retryTimeout = 60;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $uri = $this->argument('uri') ?? env('EXPORTER_URL') . 'products';
        $response = ExporterRequest::make($uri);
        if (!$response) {
            $this->error("Website unavailable! Retry after $this->retryTimeout seconds");
            $this->newLine(2);

            sleep($this->retryTimeout);

            $this->call('migrator:import-products', [
                'uri' => $uri,
            ]);
        } else {
            $this->info('Get ' . $response['meta']['current_page'] . ' page of ' . $response['meta']['last_page'] . ' pages.');

            $this->newLine(2);
    
            $this->info('Retrieved ' . count($response['data']) . ' products');
            $this->info('Importing...');
            $this->newLine(2);
    
            $bar = $this->output->createProgressBar(count($response['data']));
    
            $bar->start();
    
            foreach ($response['data'] as $key => $product) {
                $bar->advance();

                if ($product['product_id'] == 4110) {
                    dd($product);
                // //     dd(!isset($product['manufacturer_id']) 
                // //     || $product['manufacturer_id'] == null 
                // //     || ($product['manufacturer_id'] && !in_array((int) $product['manufacturer_id'], [48, 46, 47 , 23, 42])));
                }

                // dd([
                //     'in_array' => in_array((int) $product['manufacturer_id'], [48, 46, 47 , 23, 42]),
                //     'manufacturer_id' => $product['manufacturer_id'],
                // ]);

                if ($productInstance = Product::find($product['product_id'])) {
                    $productInstance->update([
                        'quantity' => $product['quantity'],
                        'price' => $product['price'],
                        'stock_status_id' => $product['stock_status_id'],
                        'date_available' => $product['date_available'],
                        'status' => $product['status'],
                    ]);
                    
                    foreach ($productInstance->discounts as $discount) {
                        $discount->delete();
                    }

                    $this->importDiscounts($product['discounts']);

                    foreach ($productInstance->specials as $special) {
                        $special->delete();
                    }
    
                    $this->importSpecials($product['specials']);
                    
                    if (
                        !isset($product['manufacturer_id']) 
                        || $product['manufacturer_id'] == null 
                        || !in_array((int) $product['manufacturer_id'], [48, 46, 47, 23, 42])
                    ) {
                        if ($productInstance->image == null && $product['image']) {
                            UploadImageJob::dispatch($productInstance, $product['image'])
                                ->onQueue('low')
                                ->delay(now()->addMinutes(30));
                        }

                        if (!$productInstance->images()->count() && $product['images']) {
                            $this->importImages($product['images']);
                        }
                    }

                    $this->deleteOptions($productInstance->product_id);

                    $this->importOptions($product['options']);
    
                    $this->importOptionValues($product['option_values']);
                } else {
                    $productInstance = Product::create($product);

                    $product['description']['description'] = '';
                    
                    $productInstance
                        ->description()
                        ->save(new ProductDescription($product['description']));
    
                    $this->importAttributes($product['attributes']);
    
                    $this->importCategories($product['categories']);
    
                    $this->importDiscounts($product['discounts']);
    
                    if (
                        !isset($product['manufacturer_id']) 
                        || $product['manufacturer_id'] == null 
                        || !in_array((int) $product['manufacturer_id'], [48, 46, 47, 23, 42])
                    ) {
                        if ($product['image']) {
                            UploadImageJob::dispatch($productInstance, $product['image'])
                                ->onQueue('low')
                                ->delay(now()->addMinutes(30));
                        }
                        $this->importImages($product['images']);
                    }
    
                    $this->importOptions($product['options']);
    
                    $this->importOptionValues($product['option_values']);
    
                    $this->importSpecials($product['specials']);
    
                    DB::table('product_to_layout')
                        ->insert([
                            'product_id' => $productInstance->product_id,
                            'store_id' => 0,
                            'layout_id' => 0,
                        ]);
    
                    DB::table('product_to_store')
                        ->insert([
                            'product_id' => $productInstance->product_id,
                            'store_id' => 0,
                        ]);
    
                    SeoUrlService::make(
                        $productInstance->product_id, 
                        'product_id=', 
                        trim($productInstance->manufacturer?->name) . ' ' . trim($productInstance->model)
                    );
                }
    
                unset($response['data'][$key]);
            }
                
            $bar->finish();
    
            if ($response['links']['next']) {
                unset($response['data'], $response['meta']);
                $this->newLine(2);
                $this->call('migrator:import-products', [
                    'uri' => $response['links']['next'],
                ]);
            } else {
                $this->newLine(2);
                $this->call('migrator:clear-cache');
            }
            $this->newLine(2);
        }

        return Command::SUCCESS;
    }

    protected function importAttributes($data)
    {
        if (!empty($data)) {
            foreach ($data as $key => $attribute) {
                ProductAttribute::firstOrCreate(
                    ['product_id' => $attribute['product_id'], 'attribute_id' => $attribute['attribute_id']],
                    $attribute
                );
            }
        }
    }

    protected function importCategories($data)
    {
        if (!empty($data)) {
            foreach ($data as $key => $category) {
                ProductCategory::firstOrCreate(
                    ['product_id' => $category['product_id'], 'category_id' => $category['category_id']],
                    $category,
                );
            }
        }
    }

    protected function importDiscounts($data)
    {
        if (!empty($data)) {
            foreach ($data as $key => $discount) {
                if (Carbon::parse($discount['date_start'])->year < 1) unset($discount['date_start']); 
                if (Carbon::parse($discount['date_end'])->year < 1) unset($discount['date_end']); 
                ProductDiscount::updateOrCreate(
                    ['product_discount_id' => $discount['product_discount_id']],
                    $discount
                );
            }
        }
    }

    protected function importImages($data)
    {
        if (!empty($data)) {
            foreach ($data as $key => $image) {
                $imageInstance = ProductImage::where([
                    ['product_id', '=', $image['product_id']],
                    ['image', '=', $image['image']]
                ])->first();
                
                if (!$imageInstance) {
                    unset($image['product_image_id']);
                    $imageInstance = ProductImage::create($image);
                }

                if ($imageInstance->wasRecentlyCreated) {
                    UploadImageJob::dispatch($imageInstance, $image['image'])
                        ->onQueue('low')
                        ->delay(now()->addMinutes(30));
                }
            }
        }
    }

    protected function importOptions($data)
    {
        if (!empty($data)) {
            foreach ($data as $key => $option) {
                ProductOption::firstOrCreate(
                    ['product_option_id' => $option['product_option_id']],
                    $option
                );
            }
        }
    }

    protected function deleteOptions($productID)
    {
        $optionsIDs = ProductOptionValue::where('product_id', $productID)->pluck('product_option_value_id')->toArray();
        ProductOptionValue::where('product_id', $productID)->delete();
        ProductOptionValueWarehouse::whereIn('product_option_value_id', $optionsIDs)->delete();
    }

    protected function importOptionValues($data)
    {
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                ProductOptionValue::updateOrCreate(
                    ['product_option_value_id' => $value['product_option_value_id']],
                    $value
                );
                if (isset($value['warehouses'])) {
                    $this->importOptionValueWarehouses($value['warehouses']);
                }
            }
        }
    }

    protected function importOptionValueWarehouses($data)
    {
        if (!empty($data)) {
            foreach ($data as $key => $warehouse) {
                ProductOptionValueWarehouse::updateOrCreate(
                    ['product_option_value_warehouse_id' => $warehouse['product_option_value_warehouse_id']],
                    $warehouse
                );
            }
        }
    }

    protected function importSpecials($data)
    {
        if (!empty($data)) {
            foreach ($data as $key => $special) {
                if (Carbon::parse($special['date_start'])->year < 1) unset($special['date_start']); 
                if (Carbon::parse($special['date_end'])->year < 1) unset($special['date_end']); 
                ProductSpecial::updateOrCreate(
                    ['product_special_id' => $special['product_special_id']],
                    $special
                );
            }
        }
    }
}
