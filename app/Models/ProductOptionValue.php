<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOptionValue extends Model
{
    use HasFactory;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'product_option_value_id';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_option_value';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'product_option_value_id',
        'product_option_id',
        'product_id',
        'option_id',
        'option_value_id',
        'quantity',
        'subtract',
        'price',
        'price_prefix',
        'points',
        'points_prefix',
        'weight',
        'weight_prefix',
    ];

    public function value()
    {
        return $this->belongsTo(OptionValue::class, 'option_value_id', 'option_value_id');
    }

    public function warehouses()
    {
        return $this->hasMany(ProductOptionValueWarehouse::class, 'product_option_value_id', 'product_option_value_id');
    }
}
