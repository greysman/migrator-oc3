<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOptionValueWarehouse extends Model
{
    use HasFactory;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'product_option_value_warehouse_id';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_option_value_warehouse';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'product_option_value_warehouse_id',
        'product_option_value_id',
        'warehouse_id',
        'warehouse_quantity',
    ];
}
