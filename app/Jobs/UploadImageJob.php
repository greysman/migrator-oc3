<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class UploadImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Model instance
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $model;

    /**
     * Image uri
     * @var string
     */
    public $uri;

    /**
     * Create a new job instance.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $uri
     * @return void
     */
    public function __construct(Model $model, string $uri)
    {
        $this->model = $model;
        $this->uri = $uri;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $fullUri = urldecode(env('SOURCE_URL') . '/image/' . $this->uri); 

        $path = explode('/', $this->uri);
        $filename = array_pop($path);
        $pathToImages = realpath(base_path() . '/' . env('OC_IMAGE_PATH')) . '/' . implode('/', $path);

        if(!File::exists($pathToImages)) {
            File::makeDirectory($pathToImages, 0777, true); //creates directory
        }

        $image = null;
        try {
            $image = file_get_contents($fullUri);
        } catch (\Throwable $th) {
            Log::error('Image ' . $fullUri . ' unavailable');
        }

        if ($image && file_put_contents($pathToImages . '/' . $filename, $image)) {
                $this->model->image = $this->uri;
                $this->model->save();
        }
    }
}
