<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manufacturer extends Model
{
    use HasFactory;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'manufacturer_id';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'manufacturer';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'manufacturer_id',
        'name',
        'sort_order',
    ];

    public function description()
    {
        return $this->hasOne(ManufacturerDescription::class, 'manufacturer_id', 'manufacturer_id');
    }

    public function store()
    {
        return $this->hasOne(ManufacturerStore::class, 'manufacturer_id', 'manufacturer_id');
    }
}
