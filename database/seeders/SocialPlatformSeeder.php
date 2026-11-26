<?php

namespace Database\Seeders;

use App\Models\SocialDesignType;
use App\Models\SocialPlatform;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SocialPlatformSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->platforms() as $platformIndex => $platform) {
            $model = SocialPlatform::query()->updateOrCreate(
                ['slug' => $platform['slug']],
                [
                    'name' => $platform['name'],
                    'icon' => $platform['icon'],
                    'accent_color' => $platform['accent_color'],
                    'description' => $platform['description'],
                    'is_active' => true,
                    'sort_order' => $platformIndex,
                ]
            );

            foreach ($platform['types'] as $typeIndex => $type) {
                SocialDesignType::query()->updateOrCreate(
                    ['platform_id' => $model->id, 'slug' => $type['slug']],
                    [
                        'name' => $type['name'],
                        'width' => $type['width'],
                        'height' => $type['height'],
                        'aspect_ratio' => $this->ratio($type['width'], $type['height']),
                        'safe_area' => $type['safe_area'] ?? ['top' => 0.05, 'right' => 0.05, 'bottom' => 0.05, 'left' => 0.05],
                        'export_formats' => $type['export_formats'] ?? ['png', 'jpg'],
                        'layout_family' => $type['layout_family'] ?? 'social',
                        'variant' => $type['variant'] ?? 'post',
                        'notes' => $type['notes'] ?? null,
                        'is_active' => true,
                        'sort_order' => $typeIndex,
                    ]
                );
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function platforms(): array
    {
        $standard = ['top' => 0.05, 'right' => 0.05, 'bottom' => 0.05, 'left' => 0.05];
        $cover = ['top' => 0.14, 'right' => 0.06, 'bottom' => 0.14, 'left' => 0.06];
        $story = ['top' => 0.12, 'right' => 0.07, 'bottom' => 0.18, 'left' => 0.07];
        $profile = ['top' => 0.1, 'right' => 0.1, 'bottom' => 0.1, 'left' => 0.1];

        return [
            [
                'name' => 'Facebook', 'slug' => 'facebook', 'icon' => 'facebook', 'accent_color' => '#1877F2',
                'description' => 'Covers, feed posts, stories, events and ad creative for Facebook Pages.',
                'types' => [
                    ['name' => 'Cover photo', 'slug' => 'facebook-cover', 'width' => 820, 'height' => 312, 'safe_area' => $cover, 'layout_family' => 'banner', 'variant' => 'cover', 'notes' => 'Keep critical content out of the profile-photo overlap on the left.'],
                    ['name' => 'Feed post', 'slug' => 'facebook-feed-post', 'width' => 1200, 'height' => 630, 'safe_area' => $standard, 'layout_family' => 'social', 'variant' => 'post'],
                    ['name' => 'Story', 'slug' => 'facebook-story', 'width' => 1080, 'height' => 1920, 'safe_area' => $story, 'layout_family' => 'story', 'variant' => 'story'],
                    ['name' => 'Event cover', 'slug' => 'facebook-event-cover', 'width' => 1920, 'height' => 1080, 'safe_area' => $standard, 'layout_family' => 'banner', 'variant' => 'cover'],
                    ['name' => 'Profile picture', 'slug' => 'facebook-profile-picture', 'width' => 320, 'height' => 320, 'safe_area' => $profile, 'layout_family' => 'social', 'variant' => 'post'],
                    ['name' => 'Advertisement', 'slug' => 'facebook-advertisement', 'width' => 1200, 'height' => 628, 'safe_area' => $standard, 'layout_family' => 'social', 'variant' => 'ad'],
                ],
            ],
            [
                'name' => 'Instagram', 'slug' => 'instagram', 'icon' => 'instagram', 'accent_color' => '#E1306C',
                'description' => 'Square, portrait, story, reel and carousel templates for Instagram.',
                'types' => [
                    ['name' => 'Square post', 'slug' => 'instagram-square-post', 'width' => 1080, 'height' => 1080, 'safe_area' => $standard, 'layout_family' => 'social', 'variant' => 'post'],
                    ['name' => 'Portrait post', 'slug' => 'instagram-portrait-post', 'width' => 1080, 'height' => 1350, 'safe_area' => $standard, 'layout_family' => 'social', 'variant' => 'post'],
                    ['name' => 'Landscape post', 'slug' => 'instagram-landscape-post', 'width' => 1080, 'height' => 566, 'safe_area' => $standard, 'layout_family' => 'social', 'variant' => 'post'],
                    ['name' => 'Story', 'slug' => 'instagram-story', 'width' => 1080, 'height' => 1920, 'safe_area' => $story, 'layout_family' => 'story', 'variant' => 'story'],
                    ['name' => 'Reel cover', 'slug' => 'instagram-reel-cover', 'width' => 1080, 'height' => 1920, 'safe_area' => $story, 'layout_family' => 'story', 'variant' => 'story'],
                    ['name' => 'Carousel', 'slug' => 'instagram-carousel', 'width' => 1080, 'height' => 1080, 'safe_area' => $standard, 'layout_family' => 'social', 'variant' => 'carousel'],
                    ['name' => 'Profile picture', 'slug' => 'instagram-profile-picture', 'width' => 320, 'height' => 320, 'safe_area' => $profile, 'layout_family' => 'social', 'variant' => 'post'],
                    ['name' => 'Advertisement', 'slug' => 'instagram-advertisement', 'width' => 1080, 'height' => 1080, 'safe_area' => $standard, 'layout_family' => 'social', 'variant' => 'ad'],
                ],
            ],
            [
                'name' => 'YouTube', 'slug' => 'youtube', 'icon' => 'youtube', 'accent_color' => '#FF0000',
                'description' => 'Channel art, thumbnails and community creative for YouTube.',
                'types' => [
                    ['name' => 'Channel banner', 'slug' => 'youtube-channel-banner', 'width' => 2560, 'height' => 1440, 'safe_area' => ['top' => 0.2, 'right' => 0.06, 'bottom' => 0.2, 'left' => 0.06], 'layout_family' => 'banner', 'variant' => 'cover', 'notes' => 'Safe area 1546 × 423 px on all devices.'],
                    ['name' => 'Video thumbnail', 'slug' => 'youtube-video-thumbnail', 'width' => 1280, 'height' => 720, 'safe_area' => $standard, 'layout_family' => 'social', 'variant' => 'thumbnail'],
                    ['name' => 'Community post', 'slug' => 'youtube-community-post', 'width' => 1080, 'height' => 1080, 'safe_area' => $standard, 'layout_family' => 'social', 'variant' => 'post'],
                    ['name' => 'Video end screen', 'slug' => 'youtube-end-screen', 'width' => 1920, 'height' => 1080, 'safe_area' => $standard, 'layout_family' => 'banner', 'variant' => 'cover'],
                    ['name' => 'Channel profile picture', 'slug' => 'youtube-profile-picture', 'width' => 800, 'height' => 800, 'safe_area' => $profile, 'layout_family' => 'social', 'variant' => 'post'],
                ],
            ],
            [
                'name' => 'LinkedIn', 'slug' => 'linkedin', 'icon' => 'linkedin', 'accent_color' => '#0A66C2',
                'description' => 'Company covers, personal banners, feed posts and carousels for LinkedIn.',
                'types' => [
                    ['name' => 'Personal profile banner', 'slug' => 'linkedin-personal-banner', 'width' => 1584, 'height' => 396, 'safe_area' => $cover, 'layout_family' => 'banner', 'variant' => 'cover'],
                    ['name' => 'Company cover', 'slug' => 'linkedin-company-cover', 'width' => 1128, 'height' => 191, 'safe_area' => $cover, 'layout_family' => 'banner', 'variant' => 'cover'],
                    ['name' => 'Feed post', 'slug' => 'linkedin-feed-post', 'width' => 1200, 'height' => 627, 'safe_area' => $standard, 'layout_family' => 'social', 'variant' => 'post'],
                    ['name' => 'Carousel', 'slug' => 'linkedin-carousel', 'width' => 1080, 'height' => 1080, 'safe_area' => $standard, 'layout_family' => 'social', 'variant' => 'carousel'],
                    ['name' => 'Advertisement', 'slug' => 'linkedin-advertisement', 'width' => 1200, 'height' => 627, 'safe_area' => $standard, 'layout_family' => 'social', 'variant' => 'ad'],
                ],
            ],
            [
                'name' => 'TikTok', 'slug' => 'tiktok', 'icon' => 'music', 'accent_color' => '#111111',
                'description' => 'Vertical covers, profile art and promotional graphics for TikTok.',
                'types' => [
                    ['name' => 'Video cover', 'slug' => 'tiktok-video-cover', 'width' => 1080, 'height' => 1920, 'safe_area' => $story, 'layout_family' => 'story', 'variant' => 'story'],
                    ['name' => 'Profile picture', 'slug' => 'tiktok-profile-picture', 'width' => 200, 'height' => 200, 'safe_area' => $profile, 'layout_family' => 'social', 'variant' => 'post'],
                    ['name' => 'Promotional post', 'slug' => 'tiktok-promotional-post', 'width' => 1080, 'height' => 1920, 'safe_area' => $story, 'layout_family' => 'story', 'variant' => 'story'],
                    ['name' => 'Advertisement', 'slug' => 'tiktok-advertisement', 'width' => 1080, 'height' => 1920, 'safe_area' => $story, 'layout_family' => 'story', 'variant' => 'ad'],
                ],
            ],
            [
                'name' => 'Pinterest', 'slug' => 'pinterest', 'icon' => 'image', 'accent_color' => '#E60023',
                'description' => 'Standard pins, idea pins, board covers and promotional pins.',
                'types' => [
                    ['name' => 'Standard pin', 'slug' => 'pinterest-standard-pin', 'width' => 1000, 'height' => 1500, 'safe_area' => $standard, 'layout_family' => 'social', 'variant' => 'post'],
                    ['name' => 'Idea pin', 'slug' => 'pinterest-idea-pin', 'width' => 1080, 'height' => 1920, 'safe_area' => $story, 'layout_family' => 'story', 'variant' => 'story'],
                    ['name' => 'Board cover', 'slug' => 'pinterest-board-cover', 'width' => 600, 'height' => 600, 'safe_area' => $standard, 'layout_family' => 'social', 'variant' => 'post'],
                    ['name' => 'Promotional pin', 'slug' => 'pinterest-promotional-pin', 'width' => 1000, 'height' => 1500, 'safe_area' => $standard, 'layout_family' => 'social', 'variant' => 'ad'],
                ],
            ],
            [
                'name' => 'X', 'slug' => 'x', 'icon' => 'twitter', 'accent_color' => '#000000',
                'description' => 'Headers, profile images, feed posts and promotional creative for X.',
                'types' => [
                    ['name' => 'Header image', 'slug' => 'x-header-image', 'width' => 1500, 'height' => 500, 'safe_area' => $cover, 'layout_family' => 'banner', 'variant' => 'cover'],
                    ['name' => 'Profile picture', 'slug' => 'x-profile-picture', 'width' => 400, 'height' => 400, 'safe_area' => $profile, 'layout_family' => 'social', 'variant' => 'post'],
                    ['name' => 'Feed post', 'slug' => 'x-feed-post', 'width' => 1200, 'height' => 675, 'safe_area' => $standard, 'layout_family' => 'social', 'variant' => 'post'],
                    ['name' => 'Promotional post', 'slug' => 'x-promotional-post', 'width' => 1200, 'height' => 675, 'safe_area' => $standard, 'layout_family' => 'social', 'variant' => 'ad'],
                ],
            ],
            [
                'name' => 'WhatsApp Business', 'slug' => 'whatsapp-business', 'icon' => 'message-circle', 'accent_color' => '#25D366',
                'description' => 'Status designs, promotional graphics and profile imagery for WhatsApp Business.',
                'types' => [
                    ['name' => 'Status design', 'slug' => 'whatsapp-status', 'width' => 1080, 'height' => 1920, 'safe_area' => $story, 'layout_family' => 'story', 'variant' => 'story'],
                    ['name' => 'Promotional graphic', 'slug' => 'whatsapp-promotional-graphic', 'width' => 1080, 'height' => 1080, 'safe_area' => $standard, 'layout_family' => 'social', 'variant' => 'ad'],
                    ['name' => 'Business profile image', 'slug' => 'whatsapp-profile-image', 'width' => 640, 'height' => 640, 'safe_area' => $profile, 'layout_family' => 'social', 'variant' => 'post'],
                ],
            ],
        ];
    }

    private function ratio(int $width, int $height): string
    {
        $a = $width;
        $b = $height;

        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }

        $divisor = $a ?: 1;

        return intdiv($width, $divisor).':'.intdiv($height, $divisor);
    }
}
