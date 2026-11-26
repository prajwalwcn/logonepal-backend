<?php

namespace Tests\Unit;

use App\Services\Design\DesignFormats;
use App\Services\Design\FormatComposer;
use App\Services\Design\QrCodeMatrix;
use Tests\TestCase;

class DesignFormatsTest extends TestCase
{
    public function test_every_generator_has_a_valid_canvas_layout_and_field_schema(): void
    {
        $formats = DesignFormats::all();

        $this->assertGreaterThanOrEqual(120, count($formats), 'Expected the full generator catalogue.');

        foreach ($formats as $slug => $format) {
            $this->assertSame($slug, $format->slug);
            $this->assertGreaterThan(0, $format->width, $slug);
            $this->assertGreaterThan(0, $format->height, $slug);
            $this->assertArrayHasKey($format->category, DesignFormats::CATEGORIES, $slug);
            $this->assertTrue(
                method_exists(FormatComposer::class, 'layout'.ucfirst($format->layout)),
                "Layout [{$format->layout}] for [{$slug}] has no composer method."
            );

            $fields = $format->fields();
            $this->assertNotEmpty($fields, $slug);
            $this->assertSame('brand', $fields[0]['key'], $slug);

            $keys = array_column($fields, 'key');
            $this->assertSame(array_values(array_unique($keys)), $keys, $slug);

            foreach ($fields as $field) {
                $this->assertNotSame('', $field['label'], $slug);
                $this->assertContains($field['type'], ['text', 'textarea', 'tel', 'email'], $slug);
                $this->assertGreaterThan(0, $field['max'], $slug);
            }
        }
    }

    public function test_catalogue_is_grouped_into_categories(): void
    {
        $groups = DesignFormats::grouped();

        $this->assertGreaterThanOrEqual(10, count($groups));
        $this->assertSame(
            count(DesignFormats::all()),
            array_sum(array_column($groups, 'count'))
        );

        $social = DesignFormats::grouped('social');
        $this->assertSame('social', $social[0]['slug']);
        $this->assertSame(6, $social[0]['count']);
    }

    public function test_social_suite_is_registered_across_three_pillars(): void
    {
        $covers = DesignFormats::grouped('covers')[0];
        $posts = DesignFormats::grouped('posts')[0];
        $profiles = DesignFormats::grouped('profiles')[0];

        $this->assertSame(11, $covers['count']);
        $this->assertSame(14, $posts['count']);
        $this->assertSame(8, $profiles['count']);
        $this->assertSame(33, $covers['count'] + $posts['count'] + $profiles['count']);

        $expected = [
            'facebook-cover' => [1640, 624],
            'youtube-banner' => [2560, 1440],
            'twitch-banner' => [1920, 480],
            'linkedin-banner' => [1584, 396],
            'facebook-event-cover' => [1920, 1005],
            'soundcloud-banner' => [2480, 520],
            'twitter-header' => [1500, 500],
            'pinterest-board-cover' => [1000, 1000],
            'zoom-background' => [1920, 1080],
            'etsy-banner' => [2400, 600],
            'tumblr-banner' => [3000, 1055],
            'instagram-post' => [1080, 1080],
            'instagram-story' => [1080, 1920],
            'facebook-post' => [1200, 630],
            'facebook-story' => [1080, 1920],
            'facebook-ad' => [1080, 1080],
            'twitter-post' => [1600, 900],
            'linkedin-post' => [1200, 1200],
            'pinterest-pin' => [1000, 1500],
            'tiktok-cover' => [1080, 1920],
            'reel-cover' => [1080, 1920],
            'youtube-short' => [1080, 1920],
            'youtube-thumbnail' => [1280, 720],
            'whatsapp-story' => [1080, 1920],
            'snapchat-design' => [1080, 1920],
        ];

        foreach ($expected as $slug => [$width, $height]) {
            $format = DesignFormats::find($slug);
            $this->assertNotNull($format, $slug);
            $this->assertSame($width, $format->width, $slug);
            $this->assertSame($height, $format->height, $slug);
        }

        foreach ($profiles['formats'] as $format) {
            $this->assertSame(800, $format['width'], $format['slug']);
            $this->assertSame(800, $format['height'], $format['slug']);
        }
    }

    public function test_qr_matrix_is_square_and_has_finder_patterns(): void
    {
        $matrix = QrCodeMatrix::encode('https://yourbrand.com');

        $this->assertNotNull($matrix);
        $size = count($matrix);
        $this->assertSame($size, count($matrix[0]));
        $this->assertContains($size, [21, 25, 29, 33, 37, 41]);
        $this->assertSame(1, $matrix[0][0]);
        $this->assertSame(0, $matrix[1][1]);
        $this->assertSame(1, $matrix[3][3]);
        $this->assertSame(1, $matrix[$size - 1][$size - 1]);

        $this->assertNull(QrCodeMatrix::encode(str_repeat('x', 200)));
    }

    public function test_search_matches_format_names(): void
    {
        $groups = DesignFormats::grouped(null, 'hoodie');

        $names = collect($groups)->flatMap(fn (array $group) => $group['formats'])->pluck('name')->all();

        $this->assertNotEmpty($names);
        $this->assertStringContainsStringIgnoringCase('hoodie', implode(' ', $names));
    }
}
