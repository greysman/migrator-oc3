<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeoUrlService {

    public static function make($id, string $queryPrefix = 'product_id=', string $name = '')
    {
        DB::table('seo_url')->insert([
            'store_id' => 0,
            'language_id' => 1,
            'query' => $queryPrefix . (int) $id,
            'keyword' => Str::of($name)->slug(),
        ]);
    }

}