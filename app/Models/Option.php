<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'option';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'option_id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'option_id',
        'type',
        'sort_order',
    ];

    public function description()
    {
        return $this->hasOne(OptionDescription::class, 'option_id', 'option_id');
    }

    public function values()
    {
        return $this->hasMany(OptionValue::class, 'option_id', 'option_id');
    }
}
