<?php

namespace Database\Seeders;

use App\Domain\HelpNow\HelpCategory;
use App\Domain\HelpNow\HelpItem;
use Database\Seeders\Data\HelpNowContent;
use Illuminate\Database\Seeder;

class HelpNowSeeder extends Seeder
{
    public function run(): void
    {
        $categoryIdsBySlug = [];

        foreach (HelpNowContent::categories() as $cat) {
            $category = HelpCategory::updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'sort_order' => $cat['sort_order'],
                    'icon' => $cat['icon'],
                    'is_active' => true,
                    'title_en' => $cat['title_en'],
                    'title_ar' => $cat['title_ar'],
                    'description_en' => null,
                    'description_ar' => null,
                ]
            );
            $categoryIdsBySlug[$cat['slug']] = $category->id;
        }

        foreach (HelpNowContent::items() as $item) {
            $categoryId = $categoryIdsBySlug[$item['category_slug']] ?? null;
            if ($categoryId === null) {
                continue;
            }
            HelpItem::updateOrCreate(
                [
                    'category_id' => $categoryId,
                    'slug' => $item['slug'],
                ],
                [
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                    'title_en' => $item['title_en'],
                    'title_ar' => $item['title_ar'],
                    'subtitle_en' => null,
                    'subtitle_ar' => null,
                    'content_en' => $item['content_en'],
                    'content_ar' => $item['content_ar'],
                ]
            );
        }
    }
}
