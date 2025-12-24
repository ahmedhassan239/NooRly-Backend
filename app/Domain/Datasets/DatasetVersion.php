<?php

namespace App\Domain\Datasets;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatasetVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'dataset_type',
        'locale',
        'version',
        'file_path',
        'checksum',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];
}
