<?php

namespace App\Domain\Categories\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Categorizable Model
 * 
 * Represents a pivot entry in the categorizables table.
 * Used for Filament relation managers.
 */
class Categorizable extends Model
{
    protected $table = 'categorizables';

    public $timestamps = true;

    protected $fillable = [
        'category_id',
        'categorizable_type',
        'categorizable_id',
    ];
}
