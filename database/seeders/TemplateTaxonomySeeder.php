<?php

namespace Database\Seeders;

use App\Models\TemplateCategory;
use App\Models\TemplateStyle;
use App\Models\TemplateTag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TemplateTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Promotion', 'Announcement', 'Product showcase', 'Quote', 'Event',
            'Testimonial', 'Educational', 'Seasonal', 'Hiring', 'Story', 'Cover', 'Carousel',
        ];

        foreach ($categories as $index => $name) {
            TemplateCategory::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $name.' templates for social channels.',
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }

        $styles = [
            'Minimal', 'Modern', 'Luxury', 'Corporate', 'Elegant', 'Creative', 'Bold',
            'Colorful', 'Gradient', 'Retro', 'Vintage', 'Editorial', 'Geometric',
            'Futuristic', '3D', 'Abstract', 'Professional', 'Playful', 'Dark', 'Light',
        ];

        foreach ($styles as $index => $name) {
            TemplateStyle::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $name.' social template direction.',
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }

        $tags = [
            'sale', 'launch', 'discount', 'new-arrival', 'webinar', 'holiday',
            'giveaway', 'menu', 'booking', 'hiring', 'review', 'behind-the-scenes',
            'quote', 'event', 'product', 'story', 'carousel', 'cover',
        ];

        foreach ($tags as $name) {
            TemplateTag::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => Str::headline($name), 'is_active' => true]
            );
        }
    }
}
