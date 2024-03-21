<?php

namespace App\Models;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExporterRequest
{
    public static function make(string $uri)
    {
        $client = Http::withoutVerifying()->acceptJson()->withHeaders([
            'ss-token' => env('ACCESS_TOKEN', ''),
            'ss-key' => env('ACCESS_KEY', ''),
        ]);

        try {
            $response = $client->get($uri);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception("Request Failed", 1);
            
        } catch (\Throwable $th) {
            Log::error('Website unavailable', [
                'error' => $th
            ]);
            return false;
        }
    }
}
