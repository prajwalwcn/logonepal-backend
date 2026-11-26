<?php

namespace App\Services\Design;

use App\Services\Logo\SvgRenderingService;

/**
 * Produces a small, varied set of concepts for a format: four art directions
 * across the palette and type system, all composed from the same document.
 */
class FormatVariationEngine
{
    private const STYLES = ['split', 'spotlight', 'retro', 'kinetic', 'gradient', 'editorial', 'bold', 'minimal'];

    private const CARD_STYLES = [
        'wave-qr',
        'arch-stripes-qr',
        'midnight-cyan',
        'ribbon-navy',
        'executive-gold',
        'hatch-badge',
        'forest-editorial',
        'blush-rose',
        'stepped-white-qr',
        'lowpoly-qr',
        'diagonal-qr',
        'olive-sage',
        'sand-editorial',
        'sky-arcs',
        'brush-noir',
        'bracket-frame',
        'split-contrast',
        'dot-matrix',
    ];

    private const STAMP_STYLES = [
        'round-authorised-signatory',
        'corporate-scalloped-seal',
        'common-seal-pte-ltd',
        'rectangular-signatory-line',
        'head-institution-box',
        'multi-line-address-box',
        'oval-signatory-stamp',
        'concentric-oval-corporate',
        'round-director-seal',
        'round-proprietor-stamp',
        'received-date-stamp',
        'paid-finance-stamp',
        'approved-rubber-stamp',
        'qc-inspection-passed',
        'notary-triple-ring',
        'certified-true-copy',
        'confidential-urgent-box',
        'traditional-hanko-square',
    ];

    private const COVER_STYLES = [
        'bites-brews-doodle',
        'were-open-diamond',
        'yes-we-are-back',
        'menu-qr-plum',
        'menu-qr-ribbon',
        'open-daily-damask',
        'brush-stroke-grunge',
        'kinetic-giveaway',
        'dream-believe-build',
        'geometric-cube-open',
        'welcome-clean',
        'agency-split',
        'esports-cyber',
        'corporate-checklist',
        'leadgen-qr',
        'fitness-neon',
        'retail-promo',
        'y2k-streetwear',
    ];

    private const POST_STYLES = [
        'post-emblem-showcase',
        'post-bites-brews-cup',
        'post-special-discount',
        'post-were-open-diamond',
        'post-customer-review',
        'post-menu-qr',
        'post-giveaway-bold',
        'post-luxury-dark',
        'post-vintage-frappe-sketch',
        'post-todays-special',
        'post-minimal-editorial',
        'post-weekend-brunch',
    ];

    private const PROFILE_STYLES = [
        'profile-clean-white',
        'profile-dark-circle',
        'profile-gold-ring',
        'profile-brand-solid',
        'profile-gradient-radial',
        'profile-minimal-framed',
        'profile-scalloped-seal',
        'profile-luxury-glow',
    ];

    private const STORY_STYLES = [
        'story-framed-crest',
        'story-polaroid-strip',
        'story-spotlight-emblem',
        'story-daily-cup',
        'story-were-open',
        'story-menu-qr',
        'story-giveaway-swipe',
        'story-minimal-progress',
    ];

    private const LABELS = [
        'bites-brews-doodle' => 'Bites & Brews Doodle',
        'were-open-diamond' => "Diamond We're Open",
        'yes-we-are-back' => 'Megaphone Yes We Are Back',
        'menu-qr-plum' => 'Luxury Menu QR Plum',
        'menu-qr-ribbon' => 'Curved Ribbon Menu QR',
        'open-daily-damask' => 'Classic Open Daily Damask',
        'brush-stroke-grunge' => 'Dry Brush Stroke Grunge',
        'kinetic-giveaway' => 'Kinetic Giveaway Promo',
        'dream-believe-build' => 'Minimalist Dream Believe Build',
        'geometric-cube-open' => 'Isometric 3D Cube Open',
        'welcome-clean' => 'Welcome To Our Page',
        'agency-split' => 'Agency Split Showcase',
        'esports-cyber' => 'E-Sports Cyber Gaming',
        'corporate-checklist' => 'Corporate Services Checklist',
        'leadgen-qr' => 'Lead-Gen Connect QR',
        'fitness-neon' => 'High-Impact Fitness Ribbon',
        'retail-promo' => 'Retail Clearance Deals',
        'y2k-streetwear' => 'Y2K Cyber Streetwear',
        'post-emblem-showcase' => 'Emblem Showcase',
        'post-bites-brews-cup' => 'Fresh Brew & Bakery',
        'post-special-discount' => 'Special Discount Offer',
        'post-were-open-diamond' => "Diamond We're Open",
        'post-customer-review' => 'Customer Review Stars',
        'post-menu-qr' => 'Menu QR Code',
        'post-giveaway-bold' => 'Giveaway Contest',
        'post-luxury-dark' => 'Luxury Dark Spotlight',
        'post-vintage-frappe-sketch' => 'Vintage Frappe Sketch',
        'post-todays-special' => "Today's Chef Special",
        'post-minimal-editorial' => 'Minimal Editorial',
        'post-weekend-brunch' => 'Weekend Brunch Club',
        'profile-clean-white' => 'Clean White Medallion',
        'profile-dark-circle' => 'Onyx Dark Circle',
        'profile-gold-ring' => 'Concentric Gold Ring',
        'profile-brand-solid' => 'Brand Solid Accent',
        'profile-gradient-radial' => 'Radial Gradient Disc',
        'profile-minimal-framed' => 'Minimal Framed',
        'profile-scalloped-seal' => 'Corporate Scalloped Seal',
        'profile-luxury-glow' => 'Midnight Luxury Glow',
        'story-framed-crest' => 'Framed Editorial Story',
        'story-polaroid-strip' => 'Polaroid Film Strip',
        'story-spotlight-emblem' => 'Spotlight Emblem Ring',
        'story-daily-cup' => 'Daily Coffee Roast',
        'story-were-open' => "We're Open Story",
        'story-menu-qr' => 'Menu QR Story',
        'story-giveaway-swipe' => 'Giveaway Swipe Story',
        'story-minimal-progress' => 'Minimal Progress Story',
        'creator-community' => 'Creator Community Milestone',
        'festival-event' => 'Festival Weekend Event',
        'realestate-showcase' => 'Modern Architecture Showcase',
        'grunge-music' => 'Alternative Distressed Drop',
        'popart-giveaway' => 'Pop Art Contest Giveaway',
        'split' => 'Split Hero',
        'spotlight' => 'Night Spotlight',
        'retro' => 'Vintage Frame',
        'kinetic' => 'Kinetic Studio',
        'gradient' => 'Gradient Glow',
        'editorial' => 'Editorial Cream',
        'bold' => 'Bold Block',
        'minimal' => 'Minimal Gallery',
        'wave-qr' => 'Wave QR',
        'arch-stripes-qr' => 'Architectural QR',
        'midnight-cyan' => 'Midnight Cyan',
        'ribbon-navy' => 'Navy Ribbon',
        'executive-gold' => 'Executive Gold QR',
        'hatch-badge' => 'Studio Badge',
        'forest-editorial' => 'Forest Lime',
        'blush-rose' => 'Blush Editorial',
        'stepped-white-qr' => 'Notched White QR',
        'lowpoly-qr' => 'Geometric Mosaic QR',
        'diagonal-qr' => 'Cobalt Diagonal QR',
        'olive-sage' => 'Matte Sage',
        'sand-editorial' => 'Warm Parchment',
        'sky-arcs' => 'Sky Circle Arcs',
        'brush-noir' => 'Artistic Brush Noir',
        'bracket-frame' => 'Executive Inset Bracket',
        'split-contrast' => 'Dual-Tone Split QR',
        'dot-matrix' => 'Modern Dot Matrix',
        'round-authorised-signatory' => 'Authorised Signatory Round Seal',
        'corporate-scalloped-seal' => 'Corporate Scalloped Rosette Seal',
        'common-seal-pte-ltd' => 'Commonwealth Common Seal Cog',
        'rectangular-signatory-line' => 'Official Signatory Line Box',
        'head-institution-box' => 'Institutional Head 4-Line Stamp',
        'multi-line-address-box' => 'Commercial Business Address Stamp',
        'oval-signatory-stamp' => 'Executive Oval Signatory Stamp',
        'concentric-oval-corporate' => 'Concentric Double Oval Corporate',
        'round-director-seal' => 'Director Official Round Seal',
        'round-proprietor-stamp' => 'Proprietor Business Stamp',
        'received-date-stamp' => 'Office Received Date Stamp',
        'paid-finance-stamp' => 'Finance Paid Invoice Stamp',
        'qc-inspection-passed' => 'QC Inspection Passed Stamp',
        'certified-true-copy' => 'Certified True Copy Stamp',
        'confidential-urgent-box' => 'Confidential Document Box',
        'traditional-hanko-square' => 'Traditional Inkan Square Seal',
        'round-corporate-seal' => 'Round Corporate Seal',
        'notary-triple-ring' => 'Notary Triple Ring',
        'rectangular-office-box' => 'Official Corporate Box',
        'approved-rubber-stamp' => 'Approved Rubber Stamp',
        'certified-rosette' => 'Certified Rosette Seal',
        'postage-airmail' => 'Airmail Postage Mark',
        'wax-seal-heraldic' => 'Heraldic Wax Seal',
        'oval-notary-seal' => 'Consular Oval Notary',
        'hanko-inkan-square' => 'Hanko Inkan Square',
        'distressed-vintage-badge' => 'Distressed Vintage Badge',
        'verified-shield-stamp' => 'Verified Shield Emblem',
        'inspection-passed-stamp' => 'QC Inspection Passed',
        'diamond-crest-stamp' => 'Diamond Crest Seal',
        'ex-libris-archival' => 'Archival Bookplate Ex-Libris',
        'confidential-urgent' => 'Confidential Urgent Stamp',
        'monogram-embosser' => 'Monogram Embosser Seal',
        'paid-invoice-stamp' => 'Paid Invoice Finance Stamp',
        'border-bracket-cert' => 'Certificate Bracket Seal',
    ];

    /**
     * 18 genuinely distinct Display + Body font pairings so every card has its own unmistakable typographic voice.
     *
     * @var array<int, array{font: string, body: string, weight: int, case: string, tracking: int, scale: float}>
     */
    private const CARD_TYPE_PAIRINGS = [
        ['font' => 'Playfair Display', 'body' => 'Inter', 'weight' => 700, 'case' => 'title', 'tracking' => 10, 'scale' => 1.04],
        ['font' => 'Bebas Neue', 'body' => 'Montserrat', 'weight' => 400, 'case' => 'upper', 'tracking' => 65, 'scale' => 1.18],
        ['font' => 'Dancing Script', 'body' => 'Poppins', 'weight' => 700, 'case' => 'title', 'tracking' => 0, 'scale' => 1.24],
        ['font' => 'Cinzel', 'body' => 'Manrope', 'weight' => 600, 'case' => 'upper', 'tracking' => 105, 'scale' => 0.94],
        ['font' => 'Abril Fatface', 'body' => 'DM Sans', 'weight' => 400, 'case' => 'title', 'tracking' => 12, 'scale' => 1.06],
        ['font' => 'Josefin Sans', 'body' => 'Inter', 'weight' => 400, 'case' => 'upper', 'tracking' => 185, 'scale' => 0.88],
        ['font' => 'Oswald', 'body' => 'Inter', 'weight' => 600, 'case' => 'upper', 'tracking' => 50, 'scale' => 1.12],
        ['font' => 'Cormorant Garamond', 'body' => 'Montserrat', 'weight' => 600, 'case' => 'title', 'tracking' => 18, 'scale' => 1.16],
        ['font' => 'Archivo Black', 'body' => 'Inter', 'weight' => 400, 'case' => 'upper', 'tracking' => 8, 'scale' => 0.92],
        ['font' => 'Lobster', 'body' => 'Poppins', 'weight' => 400, 'case' => 'title', 'tracking' => 0, 'scale' => 1.16],
        ['font' => 'Space Mono', 'body' => 'DM Sans', 'weight' => 700, 'case' => 'upper', 'tracking' => 25, 'scale' => 0.86],
        ['font' => 'DM Serif Display', 'body' => 'DM Sans', 'weight' => 400, 'case' => 'title', 'tracking' => 10, 'scale' => 1.08],
        ['font' => 'Syne', 'body' => 'Inter', 'weight' => 700, 'case' => 'upper', 'tracking' => 15, 'scale' => 0.90],
        ['font' => 'Montserrat', 'body' => 'Inter', 'weight' => 800, 'case' => 'upper', 'tracking' => 45, 'scale' => 0.96],
        ['font' => 'Caveat', 'body' => 'DM Sans', 'weight' => 700, 'case' => 'title', 'tracking' => 0, 'scale' => 1.30],
        ['font' => 'Cinzel', 'body' => 'Montserrat', 'weight' => 600, 'case' => 'upper', 'tracking' => 120, 'scale' => 0.92],
        ['font' => 'Anton', 'body' => 'Inter', 'weight' => 400, 'case' => 'upper', 'tracking' => 30, 'scale' => 1.14],
        ['font' => 'Space Mono', 'body' => 'Inter', 'weight' => 700, 'case' => 'upper', 'tracking' => 40, 'scale' => 0.88],
    ];

    /**
     * 18 distinct Display + Body font pairings for official and vintage stamps.
     *
     * @var array<int, array{font: string, body: string, weight: int, case: string, tracking: int, scale: float}>
     */
    private const STAMP_TYPE_PAIRINGS = [
        ['font' => 'Cinzel', 'body' => 'Montserrat', 'weight' => 700, 'case' => 'upper', 'tracking' => 120, 'scale' => 1.0],
        ['font' => 'Bebas Neue', 'body' => 'Inter', 'weight' => 400, 'case' => 'upper', 'tracking' => 80, 'scale' => 1.15],
        ['font' => 'Playfair Display', 'body' => 'Inter', 'weight' => 700, 'case' => 'upper', 'tracking' => 90, 'scale' => 1.05],
        ['font' => 'Archivo Black', 'body' => 'Inter', 'weight' => 400, 'case' => 'upper', 'tracking' => 40, 'scale' => 0.95],
        ['font' => 'Space Mono', 'body' => 'Space Mono', 'weight' => 700, 'case' => 'upper', 'tracking' => 60, 'scale' => 0.9],
        ['font' => 'Oswald', 'body' => 'Montserrat', 'weight' => 600, 'case' => 'upper', 'tracking' => 70, 'scale' => 1.1],
        ['font' => 'Montserrat', 'body' => 'Inter', 'weight' => 800, 'case' => 'upper', 'tracking' => 110, 'scale' => 0.95],
        ['font' => 'Anton', 'body' => 'Inter', 'weight' => 400, 'case' => 'upper', 'tracking' => 50, 'scale' => 1.15],
        ['font' => 'Josefin Sans', 'body' => 'Montserrat', 'weight' => 600, 'case' => 'upper', 'tracking' => 150, 'scale' => 0.9],
        ['font' => 'DM Serif Display', 'body' => 'DM Sans', 'weight' => 400, 'case' => 'upper', 'tracking' => 80, 'scale' => 1.05],
        ['font' => 'Syne', 'body' => 'Inter', 'weight' => 700, 'case' => 'upper', 'tracking' => 60, 'scale' => 0.92],
        ['font' => 'Cormorant Garamond', 'body' => 'Inter', 'weight' => 700, 'case' => 'upper', 'tracking' => 100, 'scale' => 1.1],
        ['font' => 'Abril Fatface', 'body' => 'Montserrat', 'weight' => 400, 'case' => 'upper', 'tracking' => 60, 'scale' => 1.05],
        ['font' => 'Poppins', 'body' => 'Inter', 'weight' => 700, 'case' => 'upper', 'tracking' => 90, 'scale' => 0.95],
        ['font' => 'Dancing Script', 'body' => 'Montserrat', 'weight' => 700, 'case' => 'title', 'tracking' => 10, 'scale' => 1.25],
        ['font' => 'Caveat', 'body' => 'Inter', 'weight' => 700, 'case' => 'title', 'tracking' => 10, 'scale' => 1.25],
        ['font' => 'Lobster', 'body' => 'Montserrat', 'weight' => 400, 'case' => 'title', 'tracking' => 15, 'scale' => 1.15],
        ['font' => 'Outfit', 'body' => 'Inter', 'weight' => 700, 'case' => 'upper', 'tracking' => 100, 'scale' => 0.96],
    ];

    /**
     * 18 curated authentic stamp ink colorways (paperBg, ink, darkInk, accent, washBg).
     */
    private const STAMP_THEMES = [
        ['#FFFFFF', '#0038A8', '#002570', '#2563EB', '#F0F6FF'], // Exact Office Blue from User Image
        ['#FFFFFF', '#1D4ED8', '#1E40AF', '#3B82F6', '#EFF6FF'], // Classic Royal Stamp Blue
        ['#FFFFFF', '#5B21B6', '#4C1D95', '#7C3AED', '#F5F3FF'], // Traditional Stamp Violet
        ['#FFFFFF', '#0F3E8A', '#0B2D64', '#1D4ED8', '#EFF6FF'], // Executive Navy Blue
        ['#FFFFFF', '#DC2626', '#B91C1C', '#EF4444', '#FEF2F2'], // Official Stamp Red
        ['#FFFFFF', '#111827', '#030712', '#374151', '#F9FAFB'], // Permanent Carbon Black
        ['#FFFFFF', '#15803D', '#166534', '#22C55E', '#F0FDF4'], // Government Green
        ['#FFFFFF', '#0369A1', '#075985', '#38BDF8', '#F0F9FF'], // Steel Blue Notary
        ['#FFFFFF', '#0E7490', '#155E75', '#06B6D4', '#ECFEFF'], // Teal Inspection Ink
        ['#FFFFFF', '#7C2D12', '#451A03', '#9A3412', '#FEF3C7'], // Antique Walnut / Sepia
        ['#FFFFFF', '#BE123C', '#9F1239', '#E11D48', '#FFF1F2'], // Cinnabar Vermilion
        ['#FFFFFF', '#1E3A8A', '#172554', '#2563EB', '#EFF6FF'], // Dark Cobalt
        ['#FFFFFF', '#6B21A8', '#581C87', '#9333EA', '#FAF5FF'], // Royal Purple
        ['#FFFFFF', '#047857', '#065F46', '#10B981', '#ECFDF5'], // Rich Emerald
        ['#FFFFFF', '#18181B', '#09090B', '#27272A', '#FAFAFA'], // Dense Charcoal
        ['#FFFFFF', '#831843', '#701A75', '#9D174D', '#FDF2F8'], // Deep Burgundy
        ['#FFFFFF', '#B45309', '#92400E', '#D97706', '#FEF3C7'], // Golden Ochre
        ['#FFFFFF', '#1F2937', '#111827', '#4B5563', '#F3F4F6'], // Archival Slate
    ];

    /**
     * 18 curated high-contrast studio themes for 364+ unique Business Card combinations.
     * Each entry: [darkBg, darkAccent, darkSub, lightSurface, lightPrimary, lightAccent]
     */
    private const CARD_THEMES = [
        ['#0B1E45', '#F59E0B', '#FDE68A', '#FAF9F6', '#1D417A', '#2563EB'],
        ['#113E0E', '#84CC16', '#BEF264', '#F4F5F7', '#0F172A', '#16A34A'],
        ['#0D0838', '#38BDF8', '#7DD3FC', '#FFF1F2', '#E11D48', '#F43F5E'],
        ['#2D0A1B', '#F472B6', '#FBCFE8', '#EFE8D8', '#5B6E1D', '#84CC16'],
        ['#1C1917', '#D97706', '#FDE68A', '#EDF7FF', '#1E3A8A', '#3B82F6'],
        ['#0F292E', '#2DD4BF', '#99F6E4', '#F0FDF4', '#065F46', '#10B981'],
        ['#1E1B4B', '#A78BFA', '#DDD6FE', '#FDF4FF', '#6B21A8', '#9333EA'],
        ['#3B0D0C', '#FB923C', '#FED7AA', '#FFFBEB', '#92400E', '#D97706'],
        ['#111827', '#E5E7EB', '#9CA3AF', '#FFFFFF', '#111827', '#4B5563'],
        ['#082F49', '#38BDF8', '#BAE6FD', '#F0F9FF', '#0369A1', '#0284C7'],
        ['#1A2E05', '#A3E635', '#D9F99D', '#F7FEE7', '#365314', '#65A30D'],
        ['#31102F', '#FB7185', '#FECDD3', '#FFF5F5', '#9F1239', '#E11D48'],
        ['#172554', '#60A5FA', '#BFDBFE', '#EFF6FF', '#1E40AF', '#2563EB'],
        ['#27272A', '#FBBF24', '#FEF08A', '#FAFAF9', '#292524', '#B45309'],
        ['#042F2E', '#34D399', '#A7F3D0', '#ECFDF5', '#064E3B', '#059669'],
        ['#3F4F3E', '#FFFFFF', '#D6D3CD', '#F5F3EE', '#3F4F3E', '#6B7F59'],
        ['#2E1065', '#F472B6', '#F5D0FE', '#F5F3FF', '#4C1D95', '#7C3AED'],
        ['#1E293B', '#38BDF8', '#CBD5E1', '#F8FAFC', '#0F172A', '#0EA5E9'],
    ];

    public function __construct(
        private readonly FormatComposer $composer,
        private readonly SvgRenderingService $renderer,
    ) {
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<int, array<string, mixed>>
     */
    public function concepts(DesignFormat $format, array $input, int $count = 8): array
    {
        $count = max(1, min(24, $count));
        $page = max(1, (int) ($input['page'] ?? 1));
        $offset = isset($input['offset']) ? max(0, (int) $input['offset']) : ($page - 1) * $count;
        $palettes = $this->palettes($format, $input);
        $isCard = $format->layout === 'card';
        $isStamp = $format->layout === 'stamp';
        $isCover = $format->layout === 'banner';
        $isPost = in_array($format->slug, [
            'instagram-post', 'facebook-post', 'twitter-post', 'linkedin-post',
            'facebook-ad', 'social-media-ad', 'quote-post', 'announcement-post',
            'giveaway-post', 'event-post', 'instagram-carousel', 'youtube-thumbnail',
        ], true) || ($format->layout === 'social' && in_array($format->variant, ['post', 'carousel', 'ad', 'thumbnail'], true));
        $isProfile = in_array($format->slug, [
            'facebook-profile-picture', 'instagram-profile-picture', 'linkedin-profile-picture',
            'pinterest-profile-picture', 'soundcloud-profile-picture', 'tumblr-profile-picture',
            'twitch-profile-picture', 'youtube-channel-icon',
        ], true) || ($format->layout === 'icon' && in_array($format->variant, ['avatar', 'channel'], true));
        $isStory = in_array($format->slug, [
            'instagram-story', 'facebook-story', 'whatsapp-story', 'snapchat-design',
            'tiktok-cover', 'reel-cover', 'youtube-short', 'pinterest-pin',
        ], true) || $format->layout === 'story';

        $fonts = $this->fonts($format, $input);
        $concepts = [];
        $styles = $isCard
            ? self::CARD_STYLES
            : ($isStamp
                ? self::STAMP_STYLES
                : ($isCover
                    ? self::COVER_STYLES
                    : ($isPost
                        ? self::POST_STYLES
                        : ($isProfile
                            ? self::PROFILE_STYLES
                            : ($isStory
                                ? self::STORY_STYLES
                                : self::STYLES)))));

        // Mark consistency: every template on the page uses the same primary
        // emblem (or uploaded logo) instead of rotating through the pool.
        $primarySymbol = is_array($input['symbol'] ?? null) ? $input['symbol'] : null;

        for ($index = 0; $index < $count; $index++) {
            $globalIndex = $offset + $index;
            $style = $styles[$globalIndex % count($styles)];
            $palette = $palettes[$globalIndex % count($palettes)];
            $symbol = $primarySymbol;
            $cardTheme = $isCard && $globalIndex >= count(self::CARD_STYLES)
                ? self::CARD_THEMES[($globalIndex * 5 + intdiv($globalIndex, count(self::CARD_STYLES))) % count(self::CARD_THEMES)]
                : null;
            $stampTheme = $isStamp
                ? ($globalIndex < 8
                    ? self::STAMP_THEMES[0]
                    : self::STAMP_THEMES[($globalIndex * 7 + intdiv($globalIndex, count(self::STAMP_STYLES))) % count(self::STAMP_THEMES)])
                : null;

            $pairing = null;
            if ($isCard && empty($input['font'])) {
                $pairing = self::CARD_TYPE_PAIRINGS[($globalIndex + intdiv($globalIndex, count(self::CARD_TYPE_PAIRINGS)) * 3) % count(self::CARD_TYPE_PAIRINGS)];
                $font = $pairing['font'];
            } elseif ($isStamp && empty($input['font'])) {
                $pairing = self::STAMP_TYPE_PAIRINGS[($globalIndex + intdiv($globalIndex, count(self::STAMP_TYPE_PAIRINGS)) * 3) % count(self::STAMP_TYPE_PAIRINGS)];
                $font = $pairing['font'];
            } else {
                $font = $fonts[($globalIndex * 3 + intdiv($globalIndex, count($styles))) % count($fonts)];
            }

            $composeInput = array_merge($input, [
                'style' => $style,
                'colors' => $palette,
                'font' => $font,
                'symbol' => $symbol,
                'card_theme' => $cardTheme,
                'stamp_theme' => $stampTheme,
                'variant_seed' => $globalIndex,
            ]);
            if ($pairing !== null) {
                $composeInput['body_font'] = $pairing['body'];
                $composeInput['headline_weight'] = $pairing['weight'];
                $composeInput['headline_case'] = $pairing['case'];
                $composeInput['headline_tracking'] = $pairing['tracking'];
                $composeInput['headline_scale'] = $pairing['scale'];
            }

            $design = $this->composer->compose($format, $composeInput);

            $concepts[] = [
                'id' => $format->slug.'-'.($globalIndex + 1),
                'variant' => $style,
                'label' => self::LABELS[$style] ?? ucfirst($style),
                'width' => $format->width,
                'height' => $format->height,
                'palette' => $palette,
                'font' => $font,
                'body_font' => $pairing['body'] ?? $font,
                'design' => $design,
                'svg' => $this->renderer->sanitize($this->renderer->render($design)),
            ];
        }

        return $concepts;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<int, array<int, string>>
     */
    private function palettes(DesignFormat $format, array $input): array
    {
        $requested = array_values(array_filter(
            array_map(fn ($color) => is_string($color) ? trim($color) : '', $input['colors'] ?? []),
            fn (string $color) => (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $color)
        ));

        if (count($requested) >= 2) {
            while (count($requested) < 3) {
                $requested[] = $requested[0];
            }

            return [array_slice($requested, 0, 3)];
        }

        $suggested = DesignFormats::suggestedColors($format->category);
        $pool = array_merge([$suggested], DesignFormats::PALETTES);
        $unique = [];

        foreach ($pool as $palette) {
            $key = implode('', $palette);

            if (! isset($unique[$key])) {
                $unique[$key] = $palette;
            }
        }

        return array_slice(array_values($unique), 0, 12);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<int, string>
     */
    private function fonts(DesignFormat $format, array $input): array
    {
        $requested = is_string($input['font'] ?? null) ? trim($input['font']) : '';

        if ($requested !== '' && isset(DesignFormats::FONTS[$requested])) {
            return [$requested];
        }

        $category = match (true) {
            in_array($format->category, ['events', 'documents', 'logos'], true) => 'serif',
            in_array($format->category, ['marketing', 'apparel', 'social', 'video', 'covers', 'posts', 'profiles'], true) => 'display',
            default => 'sans',
        };

        $sets = [
            'serif' => ['Cormorant Garamond', 'Playfair Display', 'DM Serif Display', 'Cinzel'],
            'display' => ['Archivo Black', 'Anton', 'Bebas Neue', 'Syne'],
            'sans' => ['Poppins', 'Inter', 'Space Grotesk', 'Montserrat'],
        ];

        $fonts = $sets[$category];
        $suggested = DesignFormats::suggestedFont($format->category);

        if (! in_array($suggested, $fonts, true)) {
            array_unshift($fonts, $suggested);
        }

        return array_slice(array_values(array_unique($fonts)), 0, 4);
    }
}
