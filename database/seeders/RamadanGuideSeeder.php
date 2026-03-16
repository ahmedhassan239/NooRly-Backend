<?php

namespace Database\Seeders;

use App\Domain\RamadanGuide\RamadanGuideItem;
use Database\Seeders\Data\RamadanGuideContent;
use Illuminate\Database\Seeder;

class RamadanGuideSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RamadanGuideContent::data() as $item) {
            RamadanGuideItem::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'sort_order' => $item['sort_order'],
                    'icon' => $item['icon'],
                    'is_active' => true,
                    'title_en' => $item['title_en'],
                    'title_ar' => $item['title_ar'],
                    'description_en' => $item['description_en'],
                    'description_ar' => $item['description_ar'],
                    'content_en' => $item['content_en'],
                    'content_ar' => $item['content_ar'],
                ]
            );
        }
    }
}
