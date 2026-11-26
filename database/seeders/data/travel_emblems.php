<?php

/**
 * Original 128x128 multi-layer Travel, Aviation, Globe & Adventure Emblem Library.
 *
 * Framed scene emblems include bright internal sky/horizon backdrops so silhouettes
 * remain sharp on both light and dark studio cards. Iconic open marks use 3-4 palette
 * tones so LogoComposer automatically applies metallic/brand gradients.
 */
return [
    // --- 1. Circular Sunset Badge with Plane, Mountains & Palms ---
    [
        'name' => 'Sunset Horizon Flight',
        'slug' => 'travel-sunset-circle',
        'view_box' => '0 0 128 128',
        'category' => 'travel',
        'keywords' => ['travel', 'travels', 'tour', 'tours', 'flight', 'airplane', 'plane', 'sunset', 'mountain', 'palm', 'vacation', 'holiday', 'trip', 'badge'],
        'styles' => ['badge', 'vintage', 'modern', 'organic'],
        'industries' => ['travel'],
        'premium' => false,
        'paths' => [
            // Outer navy ring
            ['d' => 'M64 6 A58 58 0 1 0 64 122 A58 58 0 1 0 64 6 Z M64 12 A52 52 0 1 1 64 116 A52 52 0 1 1 64 12 Z', 'color' => '#0B1F3A', 'preserve_color' => true, 'fill_rule' => 'evenodd', 'tone' => 1],
            // Golden sunset sky disc
            [
                'd' => 'M64 12 A52 52 0 1 0 64 116 A52 52 0 1 0 64 12 Z',
                'tone' => 2,
                'gradient' => [
                    'type' => 'linear',
                    'angle' => 180,
                    'stops' => [
                        ['offset' => 0, 'color' => '#FDE68A'],
                        ['offset' => 0.6, 'color' => '#FB923C'],
                        ['offset' => 1, 'color' => '#EA580C'],
                    ],
                ],
            ],
            // Twin mountains + lower dark water base
            ['d' => 'M14 78 L40 48 L56 66 L74 44 L114 78 A52 52 0 0 1 14 78 Z', 'color' => '#0F172A', 'preserve_color' => true, 'tone' => 3],
            // Highlight mountain ridge + ocean shimmer
            ['d' => 'M40 48 L47 58 L41 59 L36 55 Z M74 44 L82 56 L75 58 L69 53 Z M34 92 H94 V96 H34 Z M46 101 H82 V104 H46 Z', 'color' => '#FDE68A', 'preserve_color' => true, 'tone' => 4],
            // Soaring jet silhouette across sunset sky
            ['d' => 'M44 38 L58 34 L72 22 L77 24 L68 35 L86 32 L91 26 L94 28 L89 35 L95 38 L92 40 L84 38 L66 42 L74 52 L69 53 L58 43 L46 44 Z', 'color' => '#0B1F3A', 'preserve_color' => true, 'tone' => 1],
        ],
    ],

    // --- 2. Hexagonal Beach Sunset with Airplane & Palm Trees ---
    [
        'name' => 'Hexagon Beach Flight',
        'slug' => 'travel-hex-beach',
        'view_box' => '0 0 128 128',
        'category' => 'travel',
        'keywords' => ['travel', 'travels', 'beach', 'palm', 'sunset', 'airplane', 'plane', 'flight', 'island', 'vacation', 'holiday', 'tour', 'hexagon'],
        'styles' => ['modern', 'badge', 'playful', 'geometric'],
        'industries' => ['travel'],
        'premium' => false,
        'paths' => [
            // Outer hexagon frame
            ['d' => 'M64 16 L112 40 V88 L64 112 L16 88 V40 Z M64 22 L106 43 V85 L64 106 L22 85 V43 Z', 'color' => '#0C4A6E', 'preserve_color' => true, 'fill_rule' => 'evenodd', 'tone' => 1],
            // Sky & bright sun disc inside hex
            [
                'd' => 'M64 22 L106 43 V74 H22 V43 Z',
                'tone' => 2,
                'gradient' => [
                    'type' => 'linear',
                    'angle' => 180,
                    'stops' => [
                        ['offset' => 0, 'color' => '#E0F2FE'],
                        ['offset' => 1, 'color' => '#7DD3FC'],
                    ],
                ],
            ],
            // Warm sun orb
            ['d' => 'M44 68 A20 20 0 0 1 84 68 Z', 'color' => '#F97316', 'preserve_color' => true, 'tone' => 3],
            // Ocean waves lower half
            [
                'd' => 'M22 72 Q43 66 64 72 Q85 78 106 72 V85 L64 106 L22 85 Z',
                'tone' => 4,
                'gradient' => [
                    'type' => 'linear',
                    'angle' => 90,
                    'stops' => [
                        ['offset' => 0, 'color' => '#0284C7'],
                        ['offset' => 1, 'color' => '#0369A1'],
                    ],
                ],
            ],
            // Twin palms + soaring plane across top edge
            ['d' => 'M42 74 C42 64 46 56 49 52 C44 52 38 55 35 58 C38 53 43 49 48 49 C44 46 41 44 37 44 C43 42 48 44 52 47 C55 44 60 44 64 46 C59 48 56 50 53 52 C56 54 58 57 59 60 C55 58 53 56 51 54 C48 59 46 66 47 74 Z M38 24 L56 21 L72 10 L78 12 L68 22 L88 20 L93 15 L96 17 L91 23 L97 26 L93 28 L85 26 L66 29 L73 38 L68 39 L57 30 L40 31 Z', 'color' => '#0F172A', 'preserve_color' => true, 'tone' => 1],
        ],
    ],

    // --- 3. Heritage Travel Shield with Plane, Sun & Peaks ---
    [
        'name' => 'Alpine Flight Shield',
        'slug' => 'travel-shield-flight',
        'view_box' => '0 0 128 128',
        'category' => 'travel',
        'keywords' => ['travel', 'travels', 'shield', 'flight', 'airplane', 'mountain', 'sun', 'adventure', 'tour', 'expedition', 'badge'],
        'styles' => ['badge', 'vintage', 'bold', 'corporate'],
        'industries' => ['travel'],
        'premium' => false,
        'paths' => [
            // Outer shield
            ['d' => 'M64 8 L112 24 V66 C112 94 90 112 64 122 C38 112 16 94 16 66 V24 Z M64 15 L105 29 V65 C105 89 86 105 64 114 C42 105 23 89 23 65 V29 Z', 'color' => '#451A03', 'preserve_color' => true, 'fill_rule' => 'evenodd', 'tone' => 1],
            // Golden sky inside shield
            [
                'd' => 'M64 15 L105 29 V65 C105 89 86 105 64 114 C42 105 23 89 23 65 V29 Z',
                'tone' => 2,
                'gradient' => [
                    'type' => 'linear',
                    'angle' => 180,
                    'stops' => [
                        ['offset' => 0, 'color' => '#FEF08A'],
                        ['offset' => 0.55, 'color' => '#F59E0B'],
                        ['offset' => 1, 'color' => '#B45309'],
                    ],
                ],
            ],
            // Sun orb + mountain ridges
            ['d' => 'M42 46 A12 12 0 1 0 42 70 A12 12 0 1 0 42 46 Z', 'color' => '#DC2626', 'preserve_color' => true, 'tone' => 3],
            ['d' => 'M23 76 L46 56 L64 72 L84 52 L105 74 C101 94 84 106 64 114 C43 106 26 94 23 76 Z', 'color' => '#451A03', 'preserve_color' => true, 'tone' => 1],
            // Crisp white jet soaring across center
            ['d' => 'M34 54 L54 48 L72 34 L79 36 L67 49 L92 45 L98 39 L102 41 L95 49 L102 52 L98 55 L88 53 L64 58 L74 68 L68 70 L54 59 L38 61 Z', 'color' => '#FFFFFF', 'preserve_color' => true, 'tone' => 4],
        ],
    ],

    // --- 4. Vintage Coastal Resort Circle with Palms, Skyline & Ribbon ---
    [
        'name' => 'Coastal Resort Crest',
        'slug' => 'travel-island-horizon',
        'view_box' => '0 0 128 128',
        'category' => 'travel',
        'keywords' => ['travel', 'travels', 'resort', 'beach', 'palm', 'sunset', 'city', 'ocean', 'vacation', 'holiday', 'tour', 'emblem'],
        'styles' => ['vintage', 'badge', 'organic', 'modern'],
        'industries' => ['travel'],
        'premium' => false,
        'paths' => [
            // Outer dark circle frame
            ['d' => 'M64 8 A54 54 0 1 0 64 116 A54 54 0 1 0 64 8 Z M64 14 A48 48 0 1 1 64 110 A48 48 0 1 1 64 14 Z', 'color' => '#0F172A', 'preserve_color' => true, 'fill_rule' => 'evenodd', 'tone' => 1],
            // Sunset upper half
            [
                'd' => 'M16 64 A48 48 0 0 1 112 64 V74 H16 Z',
                'tone' => 2,
                'gradient' => [
                    'type' => 'linear',
                    'angle' => 180,
                    'stops' => [
                        ['offset' => 0, 'color' => '#FDE047'],
                        ['offset' => 1, 'color' => '#F97316'],
                    ],
                ],
            ],
            // Ocean lower half
            ['d' => 'M16 74 H112 A48 48 0 0 1 16 74 Z', 'color' => '#0EA5E9', 'preserve_color' => true, 'tone' => 3],
            // Skyline buildings + twin palm trees + overhead plane
            ['d' => 'M24 74 V54 H32 V74 Z M34 74 V48 H42 V74 Z M86 74 V56 H94 V74 Z M96 74 V60 H104 V74 Z M54 74 C54 62 56 54 58 48 C53 48 48 50 45 53 C49 48 53 45 58 45 C54 42 51 40 48 40 C53 39 58 41 61 44 C64 41 68 41 72 43 C67 45 64 47 62 49 C64 51 66 54 66 57 C63 55 61 53 59 51 C57 57 57 65 58 74 Z M74 74 C74 64 76 58 77 52 C73 52 69 54 67 56 C70 52 73 50 77 50 C74 47 72 46 69 46 C73 45 77 47 79 49 C82 47 85 47 88 49 C84 50 82 52 80 54 C82 56 83 58 83 60 C81 58 79 57 78 55 C77 60 77 66 78 74 Z M52 28 L62 26 L69 20 L72 21 L67 27 L76 26 L79 23 L81 24 L78 28 L81 29 L79 31 L75 29 L66 31 L69 36 L66 36 L61 31 L53 32 Z', 'color' => '#0F172A', 'preserve_color' => true, 'tone' => 1],
            // Lower navy ribbon banner
            ['d' => 'M14 86 Q64 76 114 86 L108 104 Q64 94 20 104 Z', 'color' => '#0B1F3A', 'preserve_color' => true, 'tone' => 4],
        ],
    ],

    // --- 5. Aqua Tropical Island Badge ---
    [
        'name' => 'Aqua Paradise Island',
        'slug' => 'travel-aqua-paradise',
        'view_box' => '0 0 128 128',
        'category' => 'travel',
        'keywords' => ['travel', 'travels', 'island', 'palm', 'ocean', 'aqua', 'tropical', 'beach', 'vacation', 'holiday', 'airplane', 'tour'],
        'styles' => ['organic', 'modern', 'playful', 'badge'],
        'industries' => ['travel'],
        'premium' => false,
        'paths' => [
            // Soft aqua circle backdrop
            [
                'd' => 'M64 10 A54 54 0 1 0 64 118 A54 54 0 1 0 64 10 Z',
                'tone' => 1,
                'gradient' => [
                    'type' => 'linear',
                    'angle' => 180,
                    'stops' => [
                        ['offset' => 0, 'color' => '#CCFBF1'],
                        ['offset' => 1, 'color' => '#5EEAD4'],
                    ],
                ],
            ],
            // Teal mountain & wave ribbons
            ['d' => 'M18 82 L46 54 L66 74 L84 58 L110 82 A54 54 0 0 1 18 82 Z', 'color' => '#0F766E', 'preserve_color' => true, 'tone' => 2],
            // White wave cuts
            ['d' => 'M24 90 Q44 83 64 90 Q84 97 104 90 V96 Q84 103 64 96 Q44 89 24 96 Z M34 102 Q50 97 64 102 Q78 107 94 102 V106 Q78 111 64 106 Q50 101 34 106 Z', 'color' => '#FFFFFF', 'preserve_color' => true, 'tone' => 3],
            // Sun orb + curved palm + flying plane
            ['d' => 'M78 26 A9 9 0 1 0 78 44 A9 9 0 1 0 78 26 Z', 'color' => '#0D9488', 'preserve_color' => true, 'tone' => 2],
            ['d' => 'M34 80 C34 64 38 50 42 42 C34 42 26 46 22 50 C26 42 33 38 41 37 C36 33 31 30 26 30 C34 28 41 31 46 36 C51 31 58 31 63 35 C56 37 52 40 48 44 C52 47 55 51 56 55 C51 52 48 49 45 46 C41 55 40 68 41 80 Z M66 46 L76 43 L83 35 L86 36 L81 44 L92 42 L95 39 L97 40 L94 44 L98 46 L95 48 L90 46 L80 49 L84 55 L81 56 L75 50 L67 51 Z', 'color' => '#115E59', 'preserve_color' => true, 'tone' => 4],
        ],
    ],

    // --- 6. Alpine Trekking Circular Badge ---
    [
        'name' => 'Alpine Trekking Crest',
        'slug' => 'travel-mountain-trek',
        'view_box' => '0 0 128 128',
        'category' => 'travel',
        'keywords' => ['travel', 'travels', 'trek', 'trekking', 'mountain', 'alpine', 'everest', 'adventure', 'hiking', 'tour', 'expedition', 'badge'],
        'styles' => ['badge', 'vintage', 'organic', 'bold'],
        'industries' => ['travel'],
        'premium' => false,
        'paths' => [
            ['d' => 'M64 6 A58 58 0 1 0 64 122 A58 58 0 1 0 64 6 Z M64 13 A51 51 0 1 1 64 115 A51 51 0 1 1 64 13 Z', 'color' => '#1E293B', 'preserve_color' => true, 'fill_rule' => 'evenodd', 'tone' => 1],
            [
                'd' => 'M64 13 A51 51 0 1 0 64 115 A51 51 0 1 0 64 13 Z',
                'tone' => 2,
                'gradient' => [
                    'type' => 'linear',
                    'angle' => 180,
                    'stops' => [
                        ['offset' => 0, 'color' => '#FEF3C7'],
                        ['offset' => 1, 'color' => '#FDBA74'],
                    ],
                ],
            ],
            ['d' => 'M64 26 A14 14 0 1 0 64 54 A14 14 0 1 0 64 26 Z', 'color' => '#F97316', 'preserve_color' => true, 'tone' => 3],
            ['d' => 'M15 80 L48 42 L68 66 L86 48 L113 80 A51 51 0 0 1 15 80 Z', 'color' => '#0F172A', 'preserve_color' => true, 'tone' => 1],
            ['d' => 'M48 42 L57 56 L50 57 L45 53 L40 57 Z M86 48 L94 59 L88 60 L82 56 Z M30 96 L36 82 L42 96 Z M46 98 L53 80 L60 98 Z M72 98 L79 80 L86 98 Z M90 96 L96 82 L102 96 Z', 'color' => '#FFFFFF', 'preserve_color' => true, 'tone' => 4],
        ],
    ],

    // --- 7. Orbiting Jet Airplane Swoosh (Palette/Gradient Adaptive) ---
    [
        'name' => 'Orbit Jet Swoosh',
        'slug' => 'travel-orbit-jet',
        'view_box' => '0 0 128 128',
        'category' => 'travel',
        'keywords' => ['travel', 'travels', 'airplane', 'jet', 'flight', 'orbit', 'swoosh', 'airline', 'airways', 'tour', 'trip'],
        'styles' => ['modern', 'bold', 'tech', 'minimal'],
        'industries' => ['travel'],
        'premium' => false,
        'paths' => [
            // Dynamic sweeping orbit ring
            ['d' => 'M20 82 C8 54 32 22 68 20 C96 18 116 34 114 54 C112 72 94 88 66 96 C44 102 26 98 20 82 Z M29 78 C34 90 50 93 67 88 C89 82 104 68 106 54 C107 40 91 27 68 29 C38 31 19 56 29 78 Z', 'fill_rule' => 'evenodd', 'tone' => 1],
            // Sleek ascending commercial jet
            ['d' => 'M28 74 L58 60 L80 28 L90 31 L76 58 L104 49 L112 39 L118 42 L108 55 L118 61 L113 65 L100 61 L72 72 L86 92 L77 95 L56 75 L34 81 Z', 'tone' => 2],
            // Accent trail & star
            ['d' => 'M18 96 Q44 92 70 80 L74 86 Q46 100 16 102 Z M98 22 L101 28 L107 31 L101 34 L98 40 L95 34 L89 31 L95 28 Z', 'tone' => 3],
        ],
    ],

    // --- 8. Luxury Ring & Rising Jet Trail with Stars ---
    [
        'name' => 'Celestial Flight Ring',
        'slug' => 'travel-ring-trail',
        'view_box' => '0 0 128 128',
        'category' => 'travel',
        'keywords' => ['travel', 'travels', 'luxury', 'flight', 'jet', 'ring', 'stars', 'airways', 'voyage', 'tour', 'airline'],
        'styles' => ['luxury', 'elegant', 'modern', 'minimal'],
        'industries' => ['travel'],
        'premium' => false,
        'paths' => [
            // Sleek open ring
            ['d' => 'M64 16 A48 48 0 1 0 112 64 A48 48 0 0 0 64 16 Z M64 25 A39 39 0 1 1 25 64 A39 39 0 0 1 64 25 Z', 'fill_rule' => 'evenodd', 'tone' => 1],
            // Sweeping golden jet swoosh rising across ring
            ['d' => 'M14 82 C36 82 62 68 88 42 L80 34 L108 24 L98 52 L90 44 C64 74 36 92 14 88 Z', 'tone' => 2],
            // Twin luxury sparkles
            ['d' => 'M28 30 L30 36 L36 38 L30 40 L28 46 L26 40 L20 38 L26 36 Z M104 82 L106 87 L111 89 L106 91 L104 96 L102 91 L97 89 L102 87 Z', 'tone' => 3],
        ],
    ],

    // --- 9. Multi-Tone Location Pin with Centered Airplane ---
    [
        'name' => 'Destination Pin Flight',
        'slug' => 'travel-pin-plane',
        'view_box' => '0 0 128 128',
        'category' => 'travel',
        'keywords' => ['travel', 'travels', 'pin', 'location', 'destination', 'airplane', 'plane', 'flight', 'map', 'tour', 'trip'],
        'styles' => ['modern', 'playful', 'minimal', 'bold'],
        'industries' => ['travel'],
        'premium' => false,
        'paths' => [
            // Outer teardrop/pin flame
            ['d' => 'M64 10 C38 36 22 56 22 76 C22 99 41 118 64 118 C87 118 106 99 106 76 C106 56 90 36 64 10 Z M64 26 C81 45 92 60 92 76 C92 92 79 104 64 104 C49 104 36 92 36 76 C36 60 47 45 64 26 Z', 'fill_rule' => 'evenodd', 'tone' => 1],
            // Inner contrasting pin arc
            ['d' => 'M64 32 C78 48 87 61 87 76 C87 89 77 99 64 99 C51 99 41 89 41 76 C41 61 50 48 64 32 Z M64 42 C54 54 48 64 48 76 C48 85 55 92 64 92 C73 92 80 85 80 76 C80 64 74 54 64 42 Z', 'fill_rule' => 'evenodd', 'tone' => 2],
            // Centered upward-soaring airplane
            ['d' => 'M64 44 L70 62 L88 74 L88 80 L70 74 L70 88 L78 94 L78 98 L64 94 L50 98 L50 94 L58 88 L58 74 L40 80 L40 74 L58 62 Z', 'tone' => 3],
        ],
    ],

    // --- 10. Double Metallic Ring with Orbiting Plane ---
    [
        'name' => 'Executive Flight Crest',
        'slug' => 'travel-double-ring-jet',
        'view_box' => '0 0 128 128',
        'category' => 'travel',
        'keywords' => ['travel', 'travels', 'airways', 'airline', 'flight', 'airplane', 'ring', 'corporate', 'luxury', 'crest', 'tour'],
        'styles' => ['corporate', 'luxury', 'badge', 'modern'],
        'industries' => ['travel'],
        'premium' => false,
        'paths' => [
            // Outer thick ring
            ['d' => 'M64 12 A52 52 0 1 0 64 116 A52 52 0 1 0 64 12 Z M64 22 A42 42 0 1 1 64 106 A42 42 0 1 1 64 22 Z', 'fill_rule' => 'evenodd', 'tone' => 1],
            // Inner fine ring
            ['d' => 'M64 28 A36 36 0 1 0 64 100 A36 36 0 1 0 64 28 Z M64 33 A31 31 0 1 1 64 95 A31 31 0 1 1 64 33 Z', 'fill_rule' => 'evenodd', 'tone' => 2],
            // Diagonal vapor trail + sleek plane
            ['d' => 'M12 78 Q50 76 82 54 L86 60 Q52 84 10 84 Z M54 64 L68 58 L80 42 L86 44 L78 57 L96 52 L101 46 L104 48 L98 56 L104 59 L101 62 L93 59 L76 65 L84 78 L79 80 L67 67 L56 69 Z', 'tone' => 3],
        ],
    ],

    // --- 11. World Globe Wrapped by Orbiting Airplane ---
    [
        'name' => 'Global Airways Swoosh',
        'slug' => 'travel-globe-swoosh',
        'view_box' => '0 0 128 128',
        'category' => 'travel',
        'keywords' => ['travel', 'travels', 'globe', 'world', 'global', 'airplane', 'flight', 'airways', 'international', 'tour', 'trip'],
        'styles' => ['luxury', 'corporate', 'modern', 'bold'],
        'industries' => ['travel'],
        'premium' => false,
        'paths' => [
            // Globe outline + meridian arcs
            ['d' => 'M64 14 A50 50 0 1 0 64 114 A50 50 0 1 0 64 14 Z M64 22 A42 42 0 0 1 97 38 H31 A42 42 0 0 1 64 22 Z M26 46 H102 A42 42 0 0 1 104 60 H24 A42 42 0 0 1 26 46 Z M24 68 H104 A42 42 0 0 1 100 84 H28 A42 42 0 0 1 24 68 Z M34 92 H94 A42 42 0 0 1 64 106 A42 42 0 0 1 34 92 Z', 'fill_rule' => 'evenodd', 'tone' => 1],
            // Sweeping orbital swoosh + airplane
            ['d' => 'M10 92 C32 96 68 78 96 42 L88 38 L116 20 L106 50 L98 46 C72 84 36 106 10 98 Z', 'tone' => 2],
            ['d' => 'M56 64 L74 56 L92 34 L100 37 L86 58 L108 52 L114 45 L118 48 L110 58 L117 62 L113 66 L103 63 L82 70 L91 84 L84 86 L70 72 L56 72 Z', 'tone' => 3],
        ],
    ],

    // --- 12. Sunburst Rays with Airplane & Ocean Waves ---
    [
        'name' => 'Sunburst Horizon Flight',
        'slug' => 'travel-sunburst-flight',
        'view_box' => '0 0 128 128',
        'category' => 'travel',
        'keywords' => ['travel', 'travels', 'sunburst', 'sun', 'rays', 'airplane', 'flight', 'ocean', 'wave', 'holiday', 'vacation', 'tour'],
        'styles' => ['bold', 'vintage', 'modern', 'playful'],
        'industries' => ['travel'],
        'premium' => false,
        'paths' => [
            // Radiant sunburst rays
            ['d' => 'M64 12 L70 50 H58 Z M34 20 L56 52 L46 56 Z M94 20 L82 56 L72 52 Z M16 42 L48 58 L42 66 Z M112 42 L86 66 L80 58 Z M12 68 L44 68 L44 76 Z M116 68 L84 76 L84 68 Z', 'tone' => 1],
            // Stylized ocean wave crest below
            ['d' => 'M18 82 Q40 70 64 82 Q88 94 110 82 C104 102 86 114 64 114 C42 114 24 102 18 82 Z M32 94 Q48 86 64 94 Q80 102 96 94 V100 Q80 108 64 100 Q48 92 32 100 Z', 'fill_rule' => 'evenodd', 'tone' => 2],
            // Crisp aircraft crossing center
            ['d' => 'M30 64 L54 60 L70 46 L78 48 L67 60 L92 57 L98 51 L102 53 L96 61 L103 64 L99 67 L89 65 L66 69 L75 80 L69 81 L54 70 L32 71 Z', 'tone' => 3],
        ],
    ],

    // --- 13. Stylized World Globe & Orbiting Jet ---
    [
        'name' => 'World Explorer Orbit',
        'slug' => 'travel-world-orbit',
        'view_box' => '0 0 128 128',
        'category' => 'travel',
        'keywords' => ['travel', 'travels', 'world', 'globe', 'earth', 'orbit', 'airplane', 'flight', 'explore', 'tours', 'destination'],
        'styles' => ['modern', 'futuristic', 'bold', 'tech'],
        'industries' => ['travel'],
        'premium' => false,
        'paths' => [
            // Globe sphere with stylized continents cutouts
            ['d' => 'M64 18 A46 46 0 1 0 64 110 A46 46 0 1 0 64 18 Z M46 34 C54 32 62 36 60 44 C58 50 50 52 46 58 C42 64 48 74 44 82 C36 78 30 66 32 54 C34 44 38 36 46 34 Z M74 42 C84 40 94 48 94 60 C94 72 86 84 74 88 C72 80 76 72 72 64 C68 58 68 46 74 42 Z', 'fill_rule' => 'evenodd', 'tone' => 1],
            // Angled orbital ring swoosh
            ['d' => 'M12 88 C4 68 30 42 70 28 C100 18 122 24 118 42 C115 54 98 70 72 82 L76 88 C108 74 126 54 124 38 C121 16 96 10 64 22 C24 36 -2 66 8 92 C14 106 34 108 58 100 L54 94 C34 100 18 98 12 88 Z', 'tone' => 2],
            // Soaring plane + star
            ['d' => 'M62 62 L76 55 L90 36 L98 38 L87 56 L108 50 L114 44 L118 47 L110 56 L116 60 L112 63 L103 60 L84 67 L92 80 L86 82 L72 69 L60 70 Z M26 24 L28 29 L33 31 L28 33 L26 38 L24 33 L19 31 L24 29 Z', 'tone' => 3],
        ],
    ],

    // --- 14. Precision Compass Rose & Flight Arc ---
    [
        'name' => 'Compass Explorer Flight',
        'slug' => 'travel-compass-flight',
        'view_box' => '0 0 128 128',
        'category' => 'travel',
        'keywords' => ['travel', 'travels', 'compass', 'explore', 'adventure', 'direction', 'flight', 'tour', 'journey', 'navigation'],
        'styles' => ['minimal', 'modern', 'badge', 'corporate'],
        'industries' => ['travel'],
        'premium' => false,
        'paths' => [
            // Outer compass ring with cardinal ticks
            ['d' => 'M64 10 A54 54 0 1 0 64 118 A54 54 0 1 0 64 10 Z M64 18 A46 46 0 1 1 64 110 A46 46 0 1 1 64 18 Z', 'fill_rule' => 'evenodd', 'tone' => 1],
            // 4-point compass star
            ['d' => 'M64 24 L74 54 L104 64 L74 74 L64 104 L54 74 L24 64 L54 54 Z M64 56 A8 8 0 1 0 64 72 A8 8 0 1 0 64 56 Z', 'fill_rule' => 'evenodd', 'tone' => 2],
            // Diagonal inner needle highlight
            ['d' => 'M40 40 L58 58 L54 64 L40 40 Z M88 88 L70 70 L74 64 L88 88 Z M88 40 L74 64 L70 58 Z M40 88 L54 64 L58 70 Z', 'tone' => 3],
        ],
    ],

    // --- 15. Coastal Yacht & Sunset Waves ---
    [
        'name' => 'Voyage Sailboat Crest',
        'slug' => 'travel-sail-horizon',
        'view_box' => '0 0 128 128',
        'category' => 'travel',
        'keywords' => ['travel', 'travels', 'sail', 'sailboat', 'yacht', 'cruise', 'voyage', 'ocean', 'sea', 'holiday', 'tour', 'vacation'],
        'styles' => ['elegant', 'minimal', 'organic', 'modern'],
        'industries' => ['travel'],
        'premium' => false,
        'paths' => [
            // Main billowing sail
            ['d' => 'M62 16 C84 36 94 58 94 82 H62 Z', 'tone' => 1],
            // Front jib sail + sun orb
            ['d' => 'M56 30 C42 46 34 64 32 82 H56 Z M94 24 A12 12 0 1 0 94 48 A12 12 0 1 0 94 24 Z', 'tone' => 2],
            // Hull & ocean wave ribbons
            ['d' => 'M22 86 H106 L96 98 H32 Z M14 106 Q38 98 64 106 Q90 114 114 106 V113 Q90 121 64 113 Q38 105 14 113 Z', 'tone' => 3],
        ],
    ],

    // --- 16. Winged Aviation Globe Crest ---
    [
        'name' => 'Winged Aviation Globe',
        'slug' => 'travel-wings-globe',
        'view_box' => '0 0 128 128',
        'category' => 'travel',
        'keywords' => ['travel', 'travels', 'wings', 'aviation', 'airways', 'airline', 'flight', 'globe', 'luxury', 'crest', 'tour'],
        'styles' => ['luxury', 'corporate', 'bold', 'badge'],
        'industries' => ['travel'],
        'premium' => false,
        'paths' => [
            // Spread symmetrical aviation wings
            ['d' => 'M6 42 L44 48 L48 56 L14 54 Z M14 58 L48 60 L50 68 L22 68 Z M24 72 L52 72 L54 80 L34 80 Z M122 42 L84 48 L80 56 L114 54 Z M114 58 L80 60 L78 68 L106 68 Z M104 72 L76 72 L74 80 L94 80 Z', 'tone' => 1],
            // Center globe emblem
            ['d' => 'M64 34 A28 28 0 1 0 64 90 A28 28 0 1 0 64 34 Z M64 41 A21 21 0 1 1 64 83 A21 21 0 1 1 64 41 Z', 'fill_rule' => 'evenodd', 'tone' => 2],
            // Center ascending flight chevron
            ['d' => 'M64 44 L78 72 L64 64 L50 72 Z', 'tone' => 3],
        ],
    ],
];
