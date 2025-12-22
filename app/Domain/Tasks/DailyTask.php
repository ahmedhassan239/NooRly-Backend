<?php

namespace App\Domain\Tasks;

use App\Domain\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyTask extends Model
{
    use HasFactory, HasTranslations;

    protected static function newFactory()
    {
        return \Database\Factories\DailyTaskFactory::new();
    }

    protected $fillable = [
        'day_number',
        'title',
        'type',
        'points',
    ];

    // HasTranslations implementation
    protected function getTranslationTable(): string
    {
        return 'daily_task_translations';
    }

    protected function getTranslationForeignKey(): string
    {
        return 'daily_task_id';
    }

    protected function getTranslatableFields(): array
    {
        return ['title', 'description'];
    }

    // Relationships
    public function translations()
    {
        return $this->hasMany(DailyTaskTranslation::class);
    }
}
