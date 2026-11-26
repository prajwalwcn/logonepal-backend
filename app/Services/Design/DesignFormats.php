<?php

namespace App\Services\Design;

/**
 * The design-format catalogue.
 *
 * Every generator in the studio is a row here: a canvas size, a layout family
 * and the copy fields the user fills in. Adding a new generator is a data
 * change, never a new controller or a new rendering path.
 */
final class DesignFormats
{
    /**
     * Global fallback copy. Formats may override any key.
     *
     * @var array<string, string>
     */
    public const FIELD_DEFAULTS = [
        'brand' => '',
        'headline' => 'Make it memorable',
        'subhead' => 'Thoughtful design, made simple.',
        'body' => 'A short paragraph of supporting copy that explains the offer and gives the reader a reason to act.',
        'cta' => 'Learn more',
        'website' => 'yourbrand.com',
        'phone' => '+1 (555) 010-2030',
        'email' => 'hello@yourbrand.com',
        'address' => '24 Market Street, Brooklyn',
        'date' => '12 June 2027',
        'time' => '6:00 PM',
        'location' => 'The Grand Hall',
        'price' => '$49',
        'discount' => '20% OFF',
        'code' => 'SAVE20',
        'role' => 'Creative Director',
        'recipient' => 'Jordan Ellis',
        'issuer' => 'Studio Director',
        'author' => 'Avery Lane',
        'quote' => 'Simplicity is the ultimate sophistication.',
        'attribution' => 'Leonardo da Vinci',
        'items' => "Espresso|3.50\nFlat white|4.20\nCold brew|4.80",
        'steps' => "Discover\nDesign\nDeliver",
        'notes' => 'Valid until 30 June. One per customer.',
        'handle' => '@yourbrand',
        'tagline' => 'Independent studio',
        'qr_url' => 'https://yourbrand.com',
        'initials' => '',
    ];

    /**
     * @var array<string, array{label: string, type: string, placeholder: string, max: int}>
     */
    public const FIELD_DEFINITIONS = [
        'brand' => ['label' => 'Business or brand name', 'type' => 'text', 'placeholder' => 'e.g. Everest Coffee', 'max' => 80],
        'headline' => ['label' => 'Headline', 'type' => 'text', 'placeholder' => 'The main message', 'max' => 120],
        'subhead' => ['label' => 'Supporting line', 'type' => 'text', 'placeholder' => 'A short supporting sentence', 'max' => 160],
        'body' => ['label' => 'Body copy', 'type' => 'textarea', 'placeholder' => 'Details, description or list', 'max' => 600],
        'cta' => ['label' => 'Call to action', 'type' => 'text', 'placeholder' => 'Shop now', 'max' => 40],
        'website' => ['label' => 'Website', 'type' => 'text', 'placeholder' => 'yourbrand.com', 'max' => 80],
        'phone' => ['label' => 'Phone', 'type' => 'tel', 'placeholder' => '+1 (555) 010-2030', 'max' => 40],
        'email' => ['label' => 'Email', 'type' => 'email', 'placeholder' => 'hello@yourbrand.com', 'max' => 80],
        'address' => ['label' => 'Address', 'type' => 'text', 'placeholder' => '24 Market Street, Brooklyn', 'max' => 120],
        'date' => ['label' => 'Date', 'type' => 'text', 'placeholder' => '12 June 2027', 'max' => 60],
        'time' => ['label' => 'Time', 'type' => 'text', 'placeholder' => '6:00 PM', 'max' => 40],
        'location' => ['label' => 'Venue', 'type' => 'text', 'placeholder' => 'The Grand Hall', 'max' => 80],
        'price' => ['label' => 'Price', 'type' => 'text', 'placeholder' => '$49', 'max' => 24],
        'discount' => ['label' => 'Discount', 'type' => 'text', 'placeholder' => '20% OFF', 'max' => 24],
        'code' => ['label' => 'Code', 'type' => 'text', 'placeholder' => 'SAVE20', 'max' => 32],
        'role' => ['label' => 'Job title', 'type' => 'text', 'placeholder' => 'Creative Director', 'max' => 60],
        'recipient' => ['label' => 'Recipient', 'type' => 'text', 'placeholder' => 'Jordan Ellis', 'max' => 80],
        'issuer' => ['label' => 'Signed by', 'type' => 'text', 'placeholder' => 'Studio Director', 'max' => 80],
        'author' => ['label' => 'Author or artist', 'type' => 'text', 'placeholder' => 'Avery Lane', 'max' => 80],
        'quote' => ['label' => 'Quote', 'type' => 'textarea', 'placeholder' => 'A line worth sharing', 'max' => 240],
        'attribution' => ['label' => 'Attribution', 'type' => 'text', 'placeholder' => 'Author name', 'max' => 80],
        'items' => ['label' => 'Menu items', 'type' => 'textarea', 'placeholder' => "Item|Price\nItem|Price", 'max' => 600],
        'steps' => ['label' => 'Steps', 'type' => 'textarea', 'placeholder' => "Step one\nStep two\nStep three", 'max' => 400],
        'notes' => ['label' => 'Small print', 'type' => 'textarea', 'placeholder' => 'Terms or fine print', 'max' => 240],
        'handle' => ['label' => 'Social handle', 'type' => 'text', 'placeholder' => '@yourbrand', 'max' => 40],
        'tagline' => ['label' => 'Tagline', 'type' => 'text', 'placeholder' => 'Independent studio', 'max' => 100],
        'qr_url' => ['label' => 'QR link', 'type' => 'text', 'placeholder' => 'https://yourbrand.com', 'max' => 200],
        'initials' => ['label' => 'Initials', 'type' => 'text', 'placeholder' => 'Leave empty to derive', 'max' => 4],
    ];

    /**
     * @var array<string, array{label: string, blurb: string}>
     */
    public const CATEGORIES = [
        'covers' => ['label' => 'Social media covers', 'blurb' => 'Channel art, banners and event covers sized for every platform.'],
        'posts' => ['label' => 'Social media posts', 'blurb' => 'Feed posts, stories, ads and video thumbnails.'],
        'profiles' => ['label' => 'Profile pictures', 'blurb' => 'Crisp 800 × 800 avatars that stay sharp at every size.'],
        'brand' => ['label' => 'Brand & stationery', 'blurb' => 'Identity, stationery and company collateral.'],
        'social' => ['label' => 'More social templates', 'blurb' => 'Carousels, quote posts, announcements and giveaways.'],
        'apparel' => ['label' => 'Apparel & merch', 'blurb' => 'Print-ready artwork for garments and products.'],
        'marketing' => ['label' => 'Marketing & ads', 'blurb' => 'Posters, flyers, banners and campaign creative.'],
        'events' => ['label' => 'Events & personal', 'blurb' => 'Invitations, certificates, covers and personal work.'],
        'creative' => ['label' => 'Creative & illustration', 'blurb' => 'Marks, mascots, patterns and line art.'],
        'product' => ['label' => 'Product & e-commerce', 'blurb' => 'Packaging, listings and product marketing.'],
        'video' => ['label' => 'Video & audio', 'blurb' => 'Thumbnails, covers, overlays and channel art.'],
        'web' => ['label' => 'Web & UI', 'blurb' => 'Heroes, icons, slides and interface illustration.'],
        'documents' => ['label' => 'Documents & decks', 'blurb' => 'Decks, planners, covers and long-form layouts.'],
        'logos' => ['label' => 'Logo styles & production', 'blurb' => 'Typographic marks plus print and cut production files.'],
    ];

    /**
     * Fonts available to the studio, grouped by classification.
     *
     * @var array<string, array<int, array{family: string, category: string, weight: int}>>
     */
    public const FONTS = [
        'Poppins' => ['family' => 'Poppins', 'category' => 'sans', 'weight' => 700],
        'Inter' => ['family' => 'Inter', 'category' => 'sans', 'weight' => 700],
        'Space Grotesk' => ['family' => 'Space Grotesk', 'category' => 'sans', 'weight' => 700],
        'Montserrat' => ['family' => 'Montserrat', 'category' => 'sans', 'weight' => 800],
        'Outfit' => ['family' => 'Outfit', 'category' => 'sans', 'weight' => 700],
        'Manrope' => ['family' => 'Manrope', 'category' => 'sans', 'weight' => 700],
        'Sora' => ['family' => 'Sora', 'category' => 'sans', 'weight' => 700],
        'DM Sans' => ['family' => 'DM Sans', 'category' => 'sans', 'weight' => 500],
        'Josefin Sans' => ['family' => 'Josefin Sans', 'category' => 'sans', 'weight' => 400],
        'Archivo Black' => ['family' => 'Archivo Black', 'category' => 'display', 'weight' => 400],
        'Anton' => ['family' => 'Anton', 'category' => 'display', 'weight' => 400],
        'Bebas Neue' => ['family' => 'Bebas Neue', 'category' => 'display', 'weight' => 400],
        'Oswald' => ['family' => 'Oswald', 'category' => 'display', 'weight' => 600],
        'Syne' => ['family' => 'Syne', 'category' => 'display', 'weight' => 800],
        'Space Mono' => ['family' => 'Space Mono', 'category' => 'sans', 'weight' => 700],
        'Playfair Display' => ['family' => 'Playfair Display', 'category' => 'serif', 'weight' => 700],
        'DM Serif Display' => ['family' => 'DM Serif Display', 'category' => 'serif', 'weight' => 400],
        'Abril Fatface' => ['family' => 'Abril Fatface', 'category' => 'serif', 'weight' => 400],
        'Cormorant Garamond' => ['family' => 'Cormorant Garamond', 'category' => 'serif', 'weight' => 600],
        'Cinzel' => ['family' => 'Cinzel', 'category' => 'serif', 'weight' => 600],
        'Dancing Script' => ['family' => 'Dancing Script', 'category' => 'script', 'weight' => 700],
        'Caveat' => ['family' => 'Caveat', 'category' => 'script', 'weight' => 600],
        'Lobster' => ['family' => 'Lobster', 'category' => 'script', 'weight' => 400],
    ];

    /**
     * @var array<string, array<int, string>>
     */
    public const PALETTES = [
        ['#0F172A', '#2563EB', '#06B6D4'],
        ['#111827', '#7C3AED', '#C4B5FD'],
        ['#111827', '#DC2626', '#FBBF24'],
        ['#14532D', '#16A34A', '#FDE68A'],
        ['#2B1707', '#92400E', '#F5DEB3'],
        ['#111111', '#A16207', '#FDE68A'],
        ['#111827', '#4B5563', '#E5E7EB'],
        ['#082F49', '#0284C7', '#BAE6FD'],
        ['#3F1D2B', '#DB2777', '#FBCFE8'],
        ['#1A2E05', '#EA580C', '#FDE047'],
        ['#1E1B4B', '#4338CA', '#C7D2FE'],
        ['#0B0B14', '#22D3EE', '#A5F3FC'],
    ];

    /**
     * Fields each layout asks for when a format does not override them.
     *
     * @var array<string, array<int, string>>
     */
    private const LAYOUT_FIELDS = [
        'lockup' => ['tagline'],
        'kit' => ['tagline', 'body', 'website'],
        'card' => ['headline', 'role', 'phone', 'email', 'website'],
        'document' => ['headline', 'body', 'website'],
        'invoice' => ['headline', 'body', 'website', 'email', 'address'],
        'menu' => ['headline', 'body', 'website', 'address', 'phone'],
        'social' => ['headline', 'subhead', 'cta', 'handle'],
        'story' => ['headline', 'subhead', 'cta', 'handle'],
        'banner' => ['headline', 'subhead', 'cta', 'website'],
        'poster' => ['headline', 'subhead', 'date', 'location', 'cta'],
        'promo' => ['headline', 'subhead', 'price', 'cta', 'code'],
        'cover' => ['headline', 'subhead', 'author'],
        'slide' => ['headline', 'subhead', 'body'],
        'certificate' => ['recipient', 'headline', 'body', 'date', 'issuer'],
        'invitation' => ['headline', 'subhead', 'date', 'time', 'location'],
        'stamp' => ['headline', 'tagline', 'location', 'code', 'date'],
        'grid' => ['headline', 'subhead', 'body'],
        'apparel' => ['headline', 'subhead'],
        'mockup' => ['headline', 'subhead'],
        'icon' => ['headline'],
        'pattern' => ['headline'],
        'illustration' => ['headline', 'subhead'],
        'lineart' => ['headline'],
        'info' => ['headline', 'body'],
        'qr' => ['headline', 'body', 'cta'],
        'barcode' => ['headline', 'code'],
        'label' => ['headline', 'subhead', 'body', 'price'],
        'sticker' => ['headline'],
    ];

    /**
     * Suggested accent colours per category, used by the studio defaults.
     *
     * @return array<int, string>
     */
    public static function suggestedColors(string $category): array
    {
        return match ($category) {
            'covers' => ['#18181B', '#B45309', '#FDE68A'],
            'posts' => ['#111827', '#FF1F8E', '#FBBF24'],
            'profiles' => ['#0F172A', '#2563EB', '#06B6D4'],
            'social' => ['#111827', '#FF1F8E', '#FBBF24'],
            'apparel' => ['#111827', '#EA580C', '#FDE047'],
            'marketing' => ['#1C1917', '#DC2626', '#FBBF24'],
            'events' => ['#3F1D2B', '#B45309', '#FDE68A'],
            'creative' => ['#2E1065', '#9333EA', '#F0ABFC'],
            'product' => ['#0C4A6E', '#0284C7', '#BAE6FD'],
            'video' => ['#0B0B14', '#22D3EE', '#A5F3FC'],
            'web' => ['#0F172A', '#4F46E5', '#C7D2FE'],
            'documents' => ['#111827', '#0D9488', '#99F6E4'],
            'logos' => ['#111111', '#A16207', '#FDE68A'],
            default => ['#0F172A', '#2563EB', '#06B6D4'],
        };
    }

    public static function suggestedFont(string $category): string
    {
        return match ($category) {
            'covers' => 'Archivo Black',
            'posts' => 'Archivo Black',
            'profiles' => 'Poppins',
            'social' => 'Archivo Black',
            'apparel' => 'Anton',
            'marketing' => 'Bebas Neue',
            'events' => 'Cormorant Garamond',
            'creative' => 'Space Grotesk',
            'product' => 'Inter',
            'video' => 'Syne',
            'web' => 'Inter',
            'documents' => 'Playfair Display',
            'logos' => 'Playfair Display',
            default => 'Poppins',
        };
    }

    public static function describe(string $name, string $category): string
    {
        $label = self::CATEGORIES[$category]['label'] ?? 'Design';

        return "{$name} — a {$label} template that starts from your brand name, colours and type, "
            .'then exports as SVG, PNG or PDF.';
    }

    /**
     * @return array{label: string, type: string, placeholder: string, max: int}|null
     */
    public static function fieldDefinition(string $key): ?array
    {
        return self::FIELD_DEFINITIONS[$key] ?? null;
    }

    /**
     * The 33 social formats grouped into the three BrandCrowd-style pillars.
     * These slugs keep their canonical entries in the raw catalogue; the
     * pillar mapping decides which category they surface under.
     *
     * @var array<string, array<int, string>>
     */
    public const SOCIAL_PILLARS = [
        'covers' => [
            'facebook-cover', 'youtube-banner', 'twitch-banner', 'linkedin-banner',
            'facebook-event-cover', 'soundcloud-banner', 'twitter-header',
            'pinterest-board-cover', 'zoom-background', 'etsy-banner', 'tumblr-banner',
        ],
        'posts' => [
            'instagram-post', 'instagram-story', 'facebook-post', 'facebook-story',
            'facebook-ad', 'twitter-post', 'linkedin-post', 'pinterest-pin',
            'tiktok-cover', 'reel-cover', 'youtube-short', 'youtube-thumbnail',
            'whatsapp-story', 'snapchat-design',
        ],
        'profiles' => [
            'facebook-profile-picture', 'instagram-profile-picture', 'linkedin-profile-picture',
            'pinterest-profile-picture', 'soundcloud-profile-picture', 'tumblr-profile-picture',
            'twitch-profile-picture', 'youtube-channel-icon',
        ],
    ];

    public static function pillarFor(string $slug): ?string
    {
        foreach (self::SOCIAL_PILLARS as $pillar => $slugs) {
            if (in_array($slug, $slugs, true)) {
                return $pillar;
            }
        }

        return null;
    }

    /**
     * @return array<string, DesignFormat>
     */
    public static function all(): array
    {
        static $formats = null;

        if ($formats !== null) {
            return $formats;
        }

        $formats = [];

        foreach (self::raw() as $category => $rows) {
            foreach ($rows as $row) {
                [$slug, $name, $width, $height, $layout, $variant] = $row;
                $fields = $row[6] ?? self::LAYOUT_FIELDS[$layout] ?? [];
                $defaults = $row[7] ?? [];

                $formats[$slug] = new DesignFormat(
                    slug: $slug,
                    name: $name,
                    category: self::pillarFor($slug) ?? $category,
                    width: (int) $width,
                    height: (int) $height,
                    layout: $layout,
                    variant: $variant,
                    fieldKeys: $fields,
                    defaults: $defaults,
                );
            }
        }

        return $formats;
    }

    public static function find(string $slug): ?DesignFormat
    {
        $aliases = [
            'stamp' => 'company-stamp',
            'stamps' => 'company-stamp',
            'facebook-covers' => 'facebook-cover',
            'youtube-banners' => 'youtube-banner',
            'twitch-banners' => 'twitch-banner',
            'linkedin-banners' => 'linkedin-banner',
            'twitter-headers' => 'twitter-header',
            'facebook-events' => 'facebook-event-cover',
            'facebook-event-covers' => 'facebook-event-cover',
            'soundcloud-banners' => 'soundcloud-banner',
            'pinterest-covers' => 'pinterest-board-cover',
            'pinterest-board-covers' => 'pinterest-board-cover',
            'zoom-backgrounds' => 'zoom-background',
            'etsy-banners' => 'etsy-banner',
            'tumblr-banners' => 'tumblr-banner',
            'instagram-posts' => 'instagram-post',
            'instagram-stories' => 'instagram-story',
            'facebook-posts' => 'facebook-post',
            'facebook-stories' => 'facebook-story',
            'facebook-ads' => 'facebook-ad',
            'twitter-posts' => 'twitter-post',
            'linkedin-posts' => 'linkedin-post',
            'pinterest-pins' => 'pinterest-pin',
            'tiktok-videos' => 'tiktok-cover',
            'instagram-reels' => 'reel-cover',
            'instagram-reel' => 'reel-cover',
            'tiktok-video' => 'tiktok-cover',
            'youtube-video-thumbnail' => 'youtube-thumbnail',
            'youtube-shorts' => 'youtube-short',
            'youtube-videos' => 'youtube-thumbnail',
            'whatsapp-stories' => 'whatsapp-story',
            'snapchat-designs' => 'snapchat-design',
            'facebook-profile-pictures' => 'facebook-profile-picture',
            'instagram-profile-pictures' => 'instagram-profile-picture',
            'linkedin-profile-pictures' => 'linkedin-profile-picture',
            'pinterest-profile-pictures' => 'pinterest-profile-picture',
            'soundcloud-profile-pictures' => 'soundcloud-profile-picture',
            'tumblr-profile-pictures' => 'tumblr-profile-picture',
            'twitch-profile-pictures' => 'twitch-profile-picture',
            'youtube-channel-icons' => 'youtube-channel-icon',
        ];

        $slug = $aliases[$slug] ?? $slug;

        return self::all()[$slug] ?? null;
    }

    /**
     * @return array<string, array{slug: string, label: string, blurb: string, count: int, formats: array<int, array<string, mixed>>}>
     */
    public static function grouped(?string $category = null, ?string $query = null, bool $summary = false): array
    {
        $query = $query !== null ? mb_strtolower(trim($query)) : '';
        $groups = [];

        foreach (self::CATEGORIES as $slug => $meta) {
            $groups[$slug] = [
                'slug' => $slug,
                'label' => $meta['label'],
                'blurb' => $meta['blurb'],
                'count' => 0,
                'formats' => [],
            ];
        }

        foreach (self::all() as $format) {
            if ($category !== null && $category !== '' && $format->category !== $category) {
                continue;
            }

            if ($query !== '' && ! str_contains(mb_strtolower($format->name.' '.$format->slug), $query)) {
                continue;
            }

            $groups[$format->category]['formats'][] = $summary ? $format->toSummaryArray() : $format->toArray();
            $groups[$format->category]['count']++;
        }

        return array_values(array_filter(
            $groups,
            fn (array $group) => $group['count'] > 0
        ));
    }

    /**
     * Compact catalogue used by the studio hub (no field schemas).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function summary(): array
    {
        return array_values(array_map(fn (DesignFormat $format) => [
            'slug' => $format->slug,
            'name' => $format->name,
            'category' => $format->category,
            'width' => $format->width,
            'height' => $format->height,
            'aspect' => $format->aspect(),
            'layout' => $format->layout,
            'variant' => $format->variant,
        ], self::all()));
    }

    /**
     * Raw catalogue rows: [slug, name, width, height, layout, variant, fields?, defaults?].
     *
     * @return array<string, array<int, array<int, mixed>>>
     */
    private static function raw(): array
    {
        return [
            'brand' => [
                ['logo', 'Logo generator', 1080, 1080, 'lockup', 'logo', ['tagline'], ['headline' => 'Your brand']],
                ['brand-identity-kit', 'Brand identity kit generator', 1600, 1200, 'kit', 'kit', ['tagline', 'body', 'website'], ['headline' => 'Brand identity']],
                ['business-card', 'Business card generator', 1050, 600, 'card', 'card', null, ['headline' => '', 'role' => '']],
                ['company-stamp', 'Company stamp generator', 1080, 1080, 'stamp', 'seal', ['headline', 'tagline', 'location', 'code'], ['headline' => '', 'tagline' => 'PVT. LTD.', 'location' => 'Authorised Signatory', 'code' => 'REG. 2026']],
                ['rubber-stamp', 'Rubber stamp generator', 1080, 1080, 'stamp', 'rubber', ['headline', 'date', 'code', 'location'], ['headline' => 'APPROVED', 'date' => '23 NOV 2026', 'code' => 'QC PASSED', 'location' => 'OFFICIAL COPY']],
                ['letterhead', 'Letterhead generator', 1240, 1754, 'document', 'letterhead', ['headline', 'body', 'website', 'phone', 'email', 'address'], ['headline' => 'Company letterhead']],
                ['invoice', 'Invoice design generator', 1240, 1754, 'invoice', 'invoice', null, ['headline' => 'Invoice']],
                ['brand-guidelines', 'Brand guidelines generator', 1240, 1754, 'document', 'guidelines', ['headline', 'body', 'website'], ['headline' => 'Brand guidelines']],
                ['company-profile', 'Company profile generator', 1240, 1754, 'document', 'profile', ['headline', 'body', 'website', 'email'], ['headline' => 'Company profile']],
                ['business-flyer', 'Business flyer generator', 1240, 1754, 'poster', 'flyer', ['headline', 'subhead', 'date', 'location', 'cta', 'website'], ['headline' => 'A better way to work']],
                ['brochure', 'Brochure generator', 1748, 1240, 'document', 'brochure', ['headline', 'body', 'website', 'phone', 'email'], ['headline' => 'What we do']],
                ['menu', 'Menu design generator', 1050, 1500, 'menu', 'menu', null, ['headline' => 'Menu']],
                ['packaging', 'Packaging design generator', 1200, 1200, 'label', 'packaging', null, ['headline' => 'Product name', 'subhead' => 'Naturally made']],
                ['product-label', 'Product label generator', 1000, 1400, 'label', 'product', null, ['headline' => 'Product name', 'price' => '$24']],
                ['website-banner', 'Website banner generator', 1600, 600, 'banner', 'cover', null, ['headline' => 'Build something people remember', 'cta' => 'Start free']],
                ['business-presentation', 'Business presentation generator', 1920, 1080, 'slide', 'presentation', null, ['headline' => 'The next chapter', 'subhead' => 'Company update · 2026']],
            ],
            'social' => [
                ['instagram-post', 'Instagram post generator', 1080, 1080, 'social', 'post', null, ['headline' => 'New drop, new season']],
                ['instagram-story', 'Instagram story generator', 1080, 1920, 'story', 'story', null, ['headline' => 'Behind the scenes', 'cta' => 'Swipe up']],
                ['instagram-carousel', 'Instagram carousel generator', 1080, 1080, 'social', 'carousel', ['headline', 'subhead', 'cta', 'handle', 'notes'], ['headline' => 'Three things we learned', 'notes' => '1 / 5']],
                ['facebook-post', 'Facebook post generator', 1200, 630, 'social', 'post', null, ['headline' => 'Now open on Market Street']],
                ['facebook-cover', 'Facebook cover generator', 1640, 624, 'banner', 'cover', null, ['headline' => 'Welcome to our page', 'subhead' => 'Open daily · In-store & online']],
                ['facebook-ad', 'Facebook ad generator', 1080, 1080, 'promo', 'ad', null, ['headline' => 'Meet your new favourite', 'cta' => 'Shop now']],
                ['youtube-thumbnail', 'YouTube thumbnail generator', 1280, 720, 'social', 'thumbnail', ['headline', 'subhead'], ['headline' => 'This changes everything', 'subhead' => 'Episode 12']],
                ['youtube-banner', 'YouTube banner generator', 2560, 1440, 'banner', 'cover', null, ['headline' => 'New videos every Tuesday', 'subhead' => 'Design, tools and process']],
                ['tiktok-cover', 'TikTok cover generator', 1080, 1920, 'story', 'story', null, ['headline' => 'Wait for it', 'subhead' => 'Part 03']],
                ['linkedin-post', 'LinkedIn post generator', 1200, 1200, 'social', 'post', null, ['headline' => 'What we learned this quarter']],
                ['linkedin-banner', 'LinkedIn banner generator', 1584, 396, 'banner', 'cover', null, ['headline' => 'Design partner for growing teams']],
                ['pinterest-pin', 'Pinterest pin generator', 1000, 1500, 'story', 'story', null, ['headline' => '10 ideas for a calmer home', 'subhead' => 'Save this for later', 'cta' => 'Read more']],
                ['social-media-ad', 'Social media ad generator', 1080, 1350, 'promo', 'ad', null, ['headline' => 'Made for everyday use', 'cta' => 'Shop the collection']],
                ['reel-cover', 'Reel cover generator', 1080, 1920, 'story', 'story', null, ['headline' => 'Save this recipe', 'subhead' => '60 seconds']],
                ['quote-post', 'Quote post generator', 1080, 1080, 'social', 'quote', ['quote', 'attribution', 'handle'], ['headline' => 'Words to live by']],
                ['announcement-post', 'Announcement post generator', 1080, 1080, 'social', 'announcement', null, ['headline' => 'We are open', 'subhead' => 'New studio, same team', 'cta' => 'Visit us']],
                ['giveaway-post', 'Giveaway post generator', 1080, 1080, 'social', 'giveaway', ['headline', 'subhead', 'cta', 'handle', 'notes'], ['headline' => 'Win a year of free design', 'subhead' => 'Three winners announced Friday', 'cta' => 'Enter now']],
                ['event-post', 'Event post generator', 1080, 1350, 'social', 'event', ['headline', 'subhead', 'date', 'time', 'location', 'cta', 'handle'], ['headline' => 'Design week', 'date' => '18—20 September']],
                ['twitter-header', 'Twitter header generator', 1500, 500, 'banner', 'cover', null, ['headline' => 'Design & Technology', 'subhead' => 'Follow along for updates']],
                ['facebook-event-cover', 'Facebook event cover generator', 1920, 1005, 'banner', 'cover', ['headline', 'date', 'location', 'subhead'], ['headline' => 'Annual Community Summit', 'date' => '24-26 NOV 2026', 'location' => 'Main Auditorium']],
                ['soundcloud-banner', 'SoundCloud banner generator', 2480, 520, 'banner', 'cover', null, ['headline' => 'New Album Out Everywhere', 'subhead' => 'Stream now']],
                ['pinterest-board-cover', 'Pinterest board cover generator', 1000, 1000, 'banner', 'cover', null, ['headline' => 'Design Inspiration']],
                ['zoom-background', 'Zoom background generator', 1920, 1080, 'banner', 'cover', null, ['headline' => 'Your company workspace', 'subhead' => 'Remote team']],
                ['etsy-banner', 'Etsy banner generator', 2400, 600, 'banner', 'cover', null, ['headline' => 'Handcrafted Goods & Design', 'subhead' => 'Free worldwide shipping']],
                ['tumblr-banner', 'Tumblr banner generator', 3000, 1055, 'banner', 'cover', null, ['headline' => 'Visual Archives']],
                ['facebook-story', 'Facebook story generator', 1080, 1920, 'story', 'story', null, ['headline' => 'Today at the studio']],
                ['twitter-post', 'Twitter post generator', 1600, 900, 'social', 'post', null, ['headline' => 'Big announcement coming Friday']],
                ['youtube-short', 'YouTube Short generator', 1080, 1920, 'story', 'story', null, ['headline' => 'Behind the scenes']],
                ['whatsapp-story', 'WhatsApp story generator', 1080, 1920, 'story', 'story', null, ['headline' => 'New arrivals in store']],
                ['snapchat-design', 'Snapchat design generator', 1080, 1920, 'story', 'story', null, ['headline' => 'Exclusive filter drop']],
                ['facebook-profile-picture', 'Facebook profile picture generator', 800, 800, 'icon', 'avatar', null, ['headline' => '']],
                ['instagram-profile-picture', 'Instagram profile picture generator', 800, 800, 'icon', 'avatar', null, ['headline' => '']],
                ['linkedin-profile-picture', 'LinkedIn profile picture generator', 800, 800, 'icon', 'avatar', null, ['headline' => '']],
                ['pinterest-profile-picture', 'Pinterest profile picture generator', 800, 800, 'icon', 'avatar', null, ['headline' => '']],
                ['soundcloud-profile-picture', 'SoundCloud profile picture generator', 800, 800, 'icon', 'avatar', null, ['headline' => '']],
                ['tumblr-profile-picture', 'Tumblr profile picture generator', 800, 800, 'icon', 'avatar', null, ['headline' => '']],
                ['twitch-profile-picture', 'Twitch profile picture generator', 800, 800, 'icon', 'avatar', null, ['headline' => '']],
                ['youtube-channel-icon', 'YouTube channel icon generator', 800, 800, 'icon', 'avatar', null, ['headline' => '']],
            ],
            'apparel' => [
                ['tshirt-design', 'T-shirt design generator', 1400, 1400, 'apparel', 'apparel', null, ['headline' => 'Made for the long way home']],
                ['hoodie-design', 'Hoodie design generator', 1400, 1400, 'apparel', 'apparel', null, ['headline' => 'Stay out longer']],
                ['sweatshirt-design', 'Sweatshirt design generator', 1400, 1400, 'apparel', 'apparel', null, ['headline' => 'Slow Sundays']],
                ['jersey-design', 'Jersey design generator', 1200, 1400, 'apparel', 'apparel', null, ['headline' => 'Northside FC', 'subhead' => 'Est. 2026']],
                ['cap-design', 'Cap design generator', 1200, 600, 'apparel', 'apparel', null, ['headline' => 'Field notes']],
                ['tote-bag-design', 'Tote bag design generator', 1200, 1200, 'apparel', 'apparel', null, ['headline' => 'Bring your own']],
                ['mug-design', 'Mug design generator', 1600, 800, 'apparel', 'apparel', null, ['headline' => 'Good morning, sunshine']],
                ['phone-case-design', 'Phone case design generator', 1000, 2000, 'apparel', 'apparel', null, ['headline' => 'Do not disturb']],
                ['sticker', 'Sticker generator', 1000, 1000, 'sticker', 'sticker', null, ['headline' => 'Good things take time']],
                ['badge-design', 'Badge design generator', 1000, 1000, 'sticker', 'badge', null, ['headline' => 'Team member']],
                ['clothing-brand', 'Clothing brand design generator', 1600, 1200, 'kit', 'kit', null, ['headline' => 'Clothing brand', 'tagline' => 'Everyday essentials']],
                ['apparel-mockup', 'Apparel mockup generator', 1400, 1600, 'mockup', 'tee', null, ['headline' => 'Your brand here']],
                ['print-on-demand', 'Print-on-demand design generator', 1400, 1400, 'apparel', 'apparel', null, ['headline' => 'Original goods']],
            ],
            'marketing' => [
                ['poster', 'Poster generator', 1240, 1754, 'poster', 'poster', null, ['headline' => 'A night of new ideas']],
                ['advertisement', 'Advertisement generator', 1080, 1350, 'promo', 'ad', null, ['headline' => 'Built to last']],
                ['promotional-flyer', 'Promotional flyer generator', 1240, 1754, 'poster', 'flyer', null, ['headline' => 'Two weeks only']],
                ['sale-banner', 'Sale banner generator', 1600, 600, 'promo', 'sale', null, ['headline' => 'Season sale', 'discount' => '50% OFF', 'code' => 'SAVE50']],
                ['discount-coupon', 'Discount coupon generator', 1400, 700, 'promo', 'coupon', ['headline', 'discount', 'code', 'notes', 'date'], ['headline' => 'A little thank you']],
                ['billboard', 'Billboard design generator', 1920, 720, 'poster', 'billboard', null, ['headline' => 'See it everywhere']],
                ['newspaper-ad', 'Newspaper ad generator', 1200, 900, 'poster', 'newspaper', null, ['headline' => 'Grand opening']],
                ['google-ads-creative', 'Google Ads creative generator', 1200, 628, 'banner', 'ads', null, ['headline' => 'Try it today', 'subhead' => 'The simple way to start', 'cta' => 'Get started']],
                ['web-ad-banner', 'Web ad banner generator', 728, 90, 'banner', 'ads', null, ['headline' => 'Start your free trial', 'subhead' => 'No card required', 'cta' => 'Try free']],
                ['product-promotion', 'Product promotion graphic generator', 1080, 1080, 'promo', 'product', null, ['headline' => 'New arrival', 'price' => '$49']],
                ['event-poster', 'Event poster generator', 1240, 1754, 'poster', 'event', null, ['headline' => 'Live at the Grand', 'date' => 'Saturday 12 June']],
                ['restaurant-promotion', 'Restaurant promotion generator', 1080, 1350, 'promo', 'restaurant', null, ['headline' => 'Chef’s table', 'subhead' => 'Five courses, one evening']],
                ['real-estate-ad', 'Real estate ad generator', 1080, 1350, 'promo', 'realestate', null, ['headline' => 'Open house', 'subhead' => 'A light-filled family home', 'price' => '$625,000']],
            ],
            'events' => [
                ['invitation-card', 'Invitation card generator', 1240, 1754, 'invitation', 'invitation', null, ['headline' => 'You are invited']],
                ['wedding-invitation', 'Wedding invitation generator', 1240, 1754, 'invitation', 'wedding', null, ['headline' => 'Avery & Jordan', 'subhead' => 'Together with their families']],
                ['birthday-invitation', 'Birthday invitation generator', 1080, 1350, 'invitation', 'birthday', null, ['headline' => 'You’re invited', 'subhead' => 'Join us for cake and celebration']],
                ['certificate', 'Certificate generator', 1754, 1240, 'certificate', 'certificate', null, ['headline' => 'Certificate of Achievement', 'recipient' => 'Jordan Ellis']],
                ['greeting-card', 'Greeting card generator', 1400, 1000, 'invitation', 'greeting', null, ['headline' => 'With love']],
                ['thank-you-card', 'Thank you card generator', 1400, 1000, 'invitation', 'thanks', null, ['headline' => 'Thank you']],
                ['calendar', 'Calendar generator', 1240, 1754, 'grid', 'calendar', null, ['headline' => 'Year at a glance']],
                ['book-cover', 'Book cover generator', 1600, 2400, 'cover', 'book', null, ['headline' => 'The Quiet Hours', 'author' => 'Avery Lane']],
                ['ebook-cover', 'Ebook cover generator', 1600, 2400, 'cover', 'ebook', null, ['headline' => 'The Practical Guide', 'author' => 'Avery Lane']],
                ['magazine-cover', 'Magazine cover generator', 1600, 2400, 'cover', 'magazine', ['headline', 'subhead', 'author', 'date', 'price'], ['headline' => 'The New Creative', 'subhead' => 'Issue 04']],
                ['album-cover', 'Album cover generator', 1600, 1600, 'cover', 'album', null, ['headline' => 'Midnight Signals', 'author' => 'Nova Ray']],
                ['resume', 'Resume/CV generator', 1240, 1754, 'document', 'resume', ['headline', 'body', 'role', 'phone', 'email', 'website', 'address'], ['headline' => 'Avery Lane', 'role' => 'Product Designer']],
                ['portfolio', 'Portfolio generator', 1240, 1754, 'document', 'portfolio', ['headline', 'body', 'email', 'website'], ['headline' => 'Selected work']],
            ],
            'creative' => [
                ['ai-icon', 'AI icon generator', 1080, 1080, 'icon', 'app', null, ['headline' => '']],
                ['ai-vector-illustration', 'AI vector illustration generator', 1200, 1200, 'illustration', 'illustration', null, ['headline' => 'Made from simple shapes']],
                ['ai-mascot', 'AI mascot generator', 1200, 1200, 'illustration', 'mascot', null, ['headline' => 'Meet Bolt', 'subhead' => 'Your friendly studio assistant']],
                ['character-design', 'Character design generator', 1200, 1600, 'illustration', 'character', null, ['headline' => 'Character sheet']],
                ['cartoon-avatar', 'Cartoon avatar generator', 1080, 1080, 'illustration', 'avatar', null, ['headline' => '']],
                ['sticker-pack', 'Sticker pack generator', 1600, 1600, 'sticker', 'pack', null, ['headline' => 'Sticker pack']],
                ['pattern', 'Pattern generator', 1200, 1200, 'pattern', 'pattern', null, ['headline' => '']],
                ['seamless-pattern', 'Seamless pattern generator', 1200, 1200, 'pattern', 'seamless', null, ['headline' => '']],
                ['abstract-art', 'Abstract art generator', 1600, 2000, 'pattern', 'art', null, ['headline' => '']],
                ['wall-art', 'Wall art generator', 1600, 2000, 'pattern', 'art', null, ['headline' => 'Slow living']],
                ['minimalist-illustration', 'Minimalist illustration generator', 1200, 1500, 'illustration', 'minimal', null, ['headline' => 'Less, but better']],
                ['tattoo-design', 'Tattoo design generator', 1200, 1500, 'lineart', 'tattoo', null, ['headline' => 'Stay wild']],
                ['coloring-page', 'Coloring page generator', 1240, 1754, 'lineart', 'coloring', null, ['headline' => 'Colour me in']],
            ],
            'product' => [
                ['product-image', 'Product image generator', 1200, 1200, 'label', 'product', null, ['headline' => 'Product name', 'price' => '$49']],
                ['product-mockup', 'Product mockup generator', 1400, 1400, 'mockup', 'bottle', null, ['headline' => 'Your product']],
                ['product-packaging', 'Product packaging generator', 1200, 1600, 'label', 'box', null, ['headline' => 'Product name', 'subhead' => 'Made with care']],
                ['amazon-listing-image', 'Amazon listing image generator', 2000, 2000, 'social', 'listing', ['headline', 'subhead', 'price', 'cta'], ['headline' => 'Product name', 'price' => '$49', 'cta' => 'Shop now']],
                ['product-infographic', 'Product infographic generator', 1200, 1800, 'info', 'product', null, ['headline' => 'Why it works', 'body' => "01 Lightweight\n02 Durable\n03 Recycled"]],
                ['product-promotional-banner', 'Product promotional banner generator', 1600, 600, 'promo', 'product', null, ['headline' => 'New arrival', 'price' => '$49']],
                ['shopify-banner', 'Shopify banner generator', 1600, 600, 'banner', 'shopify', null, ['headline' => 'The everyday collection', 'cta' => 'Shop now']],
                ['product-comparison', 'Product comparison graphic generator', 1400, 1000, 'info', 'comparison', ['headline', 'body'], ['headline' => 'Compare the plans', 'body' => "Starter|$0|1 project|Email support\nStudio|$19|Unlimited|Priority support"]],
                ['product-advertisement', 'Product advertisement generator', 1080, 1350, 'promo', 'product', null, ['headline' => 'Built for daily use', 'price' => '$49']],
            ],
            'video' => [
                ['podcast-cover', 'Podcast cover generator', 1400, 1400, 'cover', 'podcast', null, ['headline' => 'The Long Form', 'subhead' => 'Conversations on craft']],
                ['stream-overlay', 'Stream overlay generator', 1920, 1080, 'banner', 'overlay', ['headline', 'subhead', 'handle'], ['headline' => 'Live now', 'subhead' => 'Come say hi in chat']],
                ['twitch-banner', 'Twitch banner generator', 1920, 480, 'banner', 'twitch', null, ['headline' => 'Streaming Tuesdays & Fridays']],
                ['twitch-panel', 'Twitch panel generator', 320, 400, 'document', 'panel', ['headline', 'body'], ['headline' => 'About me']],
                ['video-intro-graphic', 'Video intro graphic generator', 1920, 1080, 'slide', 'intro', null, ['headline' => 'Welcome back']],
                ['channel-logo', 'Channel logo generator', 800, 800, 'icon', 'channel', null, ['headline' => '']],
                ['podcast-episode-cover', 'Podcast episode cover generator', 1400, 1400, 'cover', 'episode', null, ['headline' => 'Episode 42', 'subhead' => 'Making room for better work']],
            ],
            'web' => [
                ['website-hero-banner', 'Website hero banner generator', 1920, 900, 'banner', 'hero', null, ['headline' => 'Design that works harder', 'subhead' => 'Everything your brand needs, in one place.', 'cta' => 'Start free']],
                ['landing-page-graphic', 'Landing page graphic generator', 1600, 900, 'banner', 'hero', null, ['headline' => 'Less busywork. More good work.', 'subhead' => 'One workspace for the whole team.', 'cta' => 'Get started']],
                ['app-icon', 'App icon generator', 1024, 1024, 'icon', 'app', null, ['headline' => '']],
                ['ui-illustration', 'UI illustration generator', 1200, 900, 'illustration', 'ui', null, ['headline' => 'Built for focus']],
                ['dashboard-illustration', 'Dashboard illustration generator', 1400, 900, 'illustration', 'dashboard', null, ['headline' => 'Your week at a glance']],
                ['presentation-slide', 'Presentation slide generator', 1920, 1080, 'slide', 'presentation', null, ['headline' => 'A clear next step']],
                ['infographic', 'Infographic generator', 1240, 2200, 'info', 'infographic', null, ['headline' => 'The state of design', 'body' => "01 Research\n02 Prototype\n03 Ship"]],
                ['flowchart', 'Flowchart generator', 1600, 1000, 'info', 'flowchart', ['headline', 'steps'], ['headline' => 'How it works']],
                ['diagram', 'Diagram generator', 1600, 1000, 'info', 'diagram', ['headline', 'steps'], ['headline' => 'How the pieces fit']],
                ['favicon', 'Favicon generator', 512, 512, 'icon', 'favicon', null, ['headline' => '']],
            ],
            'documents' => [
                ['powerpoint-template', 'PowerPoint template generator', 1920, 1080, 'slide', 'template', null, ['headline' => 'Presentation title']],
                ['pitch-deck', 'Pitch deck generator', 1920, 1080, 'slide', 'pitch', null, ['headline' => 'The opportunity', 'subhead' => 'Seed round · 2026']],
                ['proposal-template', 'Proposal template generator', 1240, 1754, 'document', 'proposal', null, ['headline' => 'Project proposal']],
                ['report-cover', 'Report cover generator', 1240, 1754, 'cover', 'report', null, ['headline' => 'Annual report', 'subhead' => '2026']],
                ['notion-cover', 'Notion cover generator', 1500, 600, 'banner', 'notion', null, ['headline' => 'Project hub']],
                ['planner', 'Planner generator', 1240, 1754, 'grid', 'planner', null, ['headline' => 'Weekly planner']],
                ['digital-planner', 'Digital planner generator', 1240, 1754, 'grid', 'digital', null, ['headline' => 'Today']],
                ['worksheet', 'Worksheet generator', 1240, 1754, 'grid', 'worksheet', null, ['headline' => 'Worksheet']],
                ['ebook-layout', 'Ebook layout generator', 1240, 1754, 'document', 'ebook', null, ['headline' => 'Chapter one']],
            ],
            'logos' => [
                ['qr-code', 'QR code design generator', 1000, 1200, 'qr', 'qr', null, ['headline' => 'Scan to visit', 'body' => 'https://yourbrand.com', 'cta' => 'Scan me']],
                ['barcode', 'Barcode generator', 1000, 600, 'barcode', 'barcode', null, ['headline' => 'Product barcode', 'code' => 'BRAND-001']],
                ['signature-logo', 'Signature logo generator', 1200, 600, 'lockup', 'signature', null, ['headline' => 'Avery Lane', 'subhead' => 'Design studio']],
                ['monogram', 'Monogram generator', 1080, 1080, 'lockup', 'monogram', null, ['headline' => 'Avery Lane']],
                ['typography-logo', 'Typography logo generator', 1400, 800, 'lockup', 'typography', null, ['headline' => 'Avery Lane']],
                ['wordmark', 'Wordmark generator', 1600, 600, 'lockup', 'wordmark', null, ['headline' => 'Avery Lane']],
                ['3d-logo-mockup', '3D logo mockup generator', 1400, 1000, 'mockup', '3d', null, ['headline' => 'Avery Lane']],
                ['neon-sign', 'Neon sign generator', 1400, 800, 'lockup', 'neon', null, ['headline' => 'Open late']],
                ['embroidery-design', 'Embroidery design generator', 1000, 1000, 'lineart', 'craft', null, ['headline' => 'Your brand']],
                ['laser-cut-design', 'Laser cut design generator', 1000, 1000, 'lineart', 'craft', null, ['headline' => 'Your brand']],
                ['svg-cutting-file', 'SVG cutting file generator', 1000, 1000, 'lineart', 'craft', null, ['headline' => 'Your brand']],
                ['cnc-design', 'CNC design generator', 1000, 1000, 'lineart', 'craft', null, ['headline' => 'Your brand']],
            ],
        ];
    }
}
