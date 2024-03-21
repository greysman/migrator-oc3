<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductDescription extends Model
{
    use HasFactory;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'product_id';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_description';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'language_id',
        'name',
        'description',
        // 'tag',
        // 'meta_title',
        // 'meta_description',
        // 'meta_keyword'
    ];
}
