<?php

/**
 * Original 64x64 mascot, animal crest, and emblem vector library.
 *
 * Authored cleanly for dual-tone palette recolouring (`tone` 1 = primary fill,
 * `tone` 2 = accent/detail fill), infinitely scalable on Fabric.js and server SVG.
 */
return [
    // --- 6 LION MARKS ---
    [
        'name' => 'Lion Mane Crest',
        'slug' => 'lion-crest',
        'category' => 'animals',
        'keywords' => ['lion', 'king', 'mane', 'feline', 'cat', 'safari', 'wildlife', 'royal', 'strength', 'mascot', 'animals'],
        'styles' => ['bold', 'mascot', 'badge', 'luxury'],
        'industries' => ['finance', 'consulting', 'law', 'fitness', 'gaming'],
        'premium' => false,
        'paths' => [
            // Starburst royal mane
            ['d' => 'M32 4 L39 10 L48 8 L50 17 L58 21 L54 29 L59 37 L51 43 L52 52 L42 52 L36 60 L32 54 L28 60 L22 52 L12 52 L13 43 L5 37 L10 29 L6 21 L14 17 L16 8 L25 10 Z'],
            // Sculpted lion face & snout
            ['d' => 'M22 20 L26 24 L38 24 L42 20 L44 27 L41 37 L36 45 L32 47 L28 45 L23 37 L20 27 Z M26 29 L29 31 L26 33 Z M38 29 L35 31 L38 33 Z M30 35 L34 35 L32 39 Z', 'tone' => 2, 'fill_rule' => 'evenodd'],
        ],
    ],
    [
        'name' => 'Geometric Lion',
        'slug' => 'lion-geometric',
        'category' => 'animals',
        'keywords' => ['lion', 'king', 'mane', 'feline', 'geometric', 'polygon', 'strength', 'mascot', 'animals'],
        'styles' => ['geometric', 'bold', 'modern', 'mascot'],
        'industries' => ['technology', 'finance', 'automotive', 'gaming'],
        'premium' => false,
        'paths' => [
            // Faceted geometric mane
            ['d' => 'M32 3 L45 10 L57 20 L55 37 L45 53 L32 61 L19 53 L9 37 L7 20 L19 10 Z M20 18 L15 26 L18 39 L26 48 L32 51 L38 48 L46 39 L49 26 L44 18 L37 22 L27 22 Z', 'fill_rule' => 'evenodd'],
            // Inner angular lion head
            ['d' => 'M23 23 L29 27 L35 27 L41 23 L44 31 L38 43 L32 46 L26 43 L20 31 Z M25 30 L30 32 L27 34 Z M39 30 L34 32 L37 34 Z M29 36 L35 36 L32 41 Z', 'tone' => 2, 'fill_rule' => 'evenodd'],
        ],
    ],
    [
        'name' => 'Royal Crowned Lion',
        'slug' => 'lion-royal',
        'category' => 'animals',
        'keywords' => ['lion', 'crown', 'royal', 'king', 'luxury', 'gold', 'mane', 'mascot', 'animals'],
        'styles' => ['luxury', 'badge', 'bold', 'mascot'],
        'industries' => ['finance', 'real-estate', 'law', 'fashion'],
        'premium' => false,
        'paths' => [
            // Crown atop mane
            ['d' => 'M20 14 L24 6 L29 11 L32 4 L35 11 L40 6 L44 14 Z M32 16 L47 21 L54 31 L50 45 L39 57 L32 60 L25 57 L14 45 L10 31 L17 21 Z M24 26 L27 24 L37 24 L40 26 L43 34 L38 44 L32 48 L26 44 L21 34 Z', 'fill_rule' => 'evenodd'],
            ['d' => 'M24 27 L32 25 L40 27 L41 35 L37 44 L32 47 L27 44 L23 35 Z M26 32 L30 33 L27 35 Z M38 32 L34 33 L37 35 Z M30 38 L34 38 L32 42 Z', 'tone' => 2, 'fill_rule' => 'evenodd'],
        ],
    ],
    [
        'name' => 'Lion Shield Crest',
        'slug' => 'lion-shield',
        'category' => 'animals',
        'keywords' => ['lion', 'shield', 'crest', 'heritage', 'guard', 'trust', 'mascot', 'animals'],
        'styles' => ['corporate', 'badge', 'vintage', 'mascot'],
        'industries' => ['law', 'finance', 'consulting', 'real-estate'],
        'premium' => false,
        'paths' => [
            ['d' => 'M10 10 L32 5 L54 10 L54 32 C54 46 43 55 32 60 C21 55 10 46 10 32 Z M32 14 L43 19 L47 29 L43 40 L32 48 L21 40 L17 29 L21 19 Z', 'fill_rule' => 'evenodd'],
            ['d' => 'M32 17 L40 21 L43 29 L39 38 L32 43 L25 38 L21 29 L24 21 Z M27 27 L30 29 L27 31 Z M37 27 L34 29 L37 31 Z M30 33 L34 33 L32 37 Z', 'tone' => 2, 'fill_rule' => 'evenodd'],
        ],
    ],
    [
        'name' => 'Minimal Lion Profile',
        'slug' => 'lion-profile',
        'category' => 'animals',
        'keywords' => ['lion', 'profile', 'roar', 'head', 'mane', 'minimal', 'mascot', 'animals'],
        'styles' => ['minimal', 'modern', 'bold', 'mascot'],
        'industries' => ['marketing', 'consulting', 'fashion', 'fitness'],
        'premium' => false,
        'paths' => [
            // Side-facing lion mane sweep
            ['d' => 'M16 14 C28 6 44 10 50 20 L42 22 C37 16 28 15 22 20 L18 30 L24 38 L18 52 L10 44 C8 32 10 21 16 14 Z'],
            // Facing snout profile
            ['d' => 'M26 20 L36 18 L42 22 L49 27 L47 34 L40 35 L42 40 L34 45 L26 42 L22 32 Z M35 24 A2 2 0 1 0 39 24 A2 2 0 1 0 35 24 Z', 'tone' => 2, 'fill_rule' => 'evenodd'],
        ],
    ],
    [
        'name' => 'Hexagon Lion Mark',
        'slug' => 'lion-minimal',
        'category' => 'animals',
        'keywords' => ['lion', 'hexagon', 'mane', 'modern', 'tech', 'king', 'mascot', 'animals'],
        'styles' => ['geometric', 'minimal', 'tech', 'mascot'],
        'industries' => ['technology', 'finance', 'consulting', 'architecture'],
        'premium' => false,
        'paths' => [
            ['d' => 'M32 5 L56 18 V46 L32 59 L8 46 V18 Z M32 13 L48 22 V42 L32 51 L16 42 V22 Z', 'fill_rule' => 'evenodd'],
            ['d' => 'M22 21 L27 25 H37 L42 21 L45 29 L41 39 L32 45 L23 39 L19 29 Z M26 29 L29 31 L26 32 Z M38 29 L35 31 L38 32 Z', 'tone' => 2, 'fill_rule' => 'evenodd'],
        ],
    ],

    // --- 6 ELEPHANT MARKS ---
    [
        'name' => 'Noble Elephant Head',
        'slug' => 'elephant-head',
        'category' => 'animals',
        'keywords' => ['elephant', 'tusk', 'trunk', 'safari', 'wildlife', 'wisdom', 'strength', 'memory', 'mascot', 'animals'],
        'styles' => ['bold', 'corporate', 'mascot', 'badge'],
        'industries' => ['consulting', 'finance', 'travel', 'education'],
        'premium' => false,
        'paths' => [
            // Ears framing head
            ['d' => 'M22 14 C10 14 4 22 6 34 C8 44 15 48 22 46 Z M42 14 C54 14 60 22 58 34 C56 44 49 48 42 46 Z'],
            // Forehead, brow, curling trunk & twin tusks
            ['d' => 'M24 12 C28 9 36 9 40 12 L43 24 L37 42 C36 49 40 54 45 52 C47 51 47 48 45 48 C43 48 42 50 41 51 C43 56 35 60 31 54 C28 50 28 43 27 38 L21 24 Z M19 36 L26 42 L25 46 C20 44 17 41 19 36 Z M45 36 C47 41 44 44 39 46 L38 42 Z', 'tone' => 2],
        ],
    ],
    [
        'name' => 'Geometric Elephant',
        'slug' => 'elephant-geometric',
        'category' => 'animals',
        'keywords' => ['elephant', 'geometric', 'tusk', 'trunk', 'modern', 'tech', 'strength', 'mascot', 'animals'],
        'styles' => ['geometric', 'modern', 'minimal', 'mascot'],
        'industries' => ['technology', 'software', 'consulting', 'architecture'],
        'premium' => false,
        'paths' => [
            ['d' => 'M18 14 L5 24 L9 43 L20 41 L18 14 Z M46 14 L59 24 L55 43 L44 41 L46 14 Z'],
            ['d' => 'M21 12 L43 12 L45 27 L38 40 L37 52 L44 55 L42 59 L31 56 L29 41 L19 27 Z M23 29 L29 37 L26 42 L20 35 Z M41 29 L44 35 L38 42 L35 37 Z', 'tone' => 2],
        ],
    ],
    [
        'name' => 'Walking Elephant Crest',
        'slug' => 'elephant-profile',
        'category' => 'animals',
        'keywords' => ['elephant', 'profile', 'walk', 'safari', 'trunk', 'wildlife', 'mascot', 'animals'],
        'styles' => ['organic', 'minimal', 'modern', 'mascot'],
        'industries' => ['travel', 'agriculture', 'education', 'fashion'],
        'premium' => false,
        'paths' => [
            // Full body silhouette walking left/right with raised trunk
            ['d' => 'M14 28 C14 16 24 12 36 12 C45 12 50 18 51 25 C55 25 58 21 57 17 L61 19 C61 28 55 34 48 33 L48 52 H40 V41 H29 V52 H21 V40 C16 39 14 34 14 28 Z'],
            // Sculpted ear flap + ivory tusk
            ['d' => 'M35 17 C41 17 44 22 43 29 C39 30 34 27 33 22 C33 19 34 17 35 17 Z M49 30 C54 31 58 29 59 25 C57 31 53 34 48 33 Z', 'tone' => 2],
        ],
    ],
    [
        'name' => 'Elephant Circle Emblem',
        'slug' => 'elephant-emblem',
        'category' => 'animals',
        'keywords' => ['elephant', 'emblem', 'badge', 'safari', 'wisdom', 'strength', 'mascot', 'animals'],
        'styles' => ['badge', 'vintage', 'corporate', 'mascot'],
        'industries' => ['coffee', 'travel', 'consulting', 'food'],
        'premium' => false,
        'paths' => [
            ['d' => 'M32 4 A28 28 0 1 0 32 60 A28 28 0 1 0 32 4 Z M32 9 A23 23 0 1 1 32 55 A23 23 0 1 1 32 9 Z', 'fill_rule' => 'evenodd'],
            ['d' => 'M24 16 H40 L48 24 L45 39 L37 36 L35 45 C35 49 39 51 42 49 L44 52 C38 56 30 53 30 45 L29 36 L19 39 L16 24 Z', 'tone' => 2],
        ],
    ],
    [
        'name' => 'Minimal Trunk Monogram',
        'slug' => 'elephant-minimal',
        'category' => 'animals',
        'keywords' => ['elephant', 'minimal', 'trunk', 'ear', 'modern', 'mark', 'mascot', 'animals'],
        'styles' => ['minimal', 'abstract', 'modern', 'mascot'],
        'industries' => ['software', 'technology', 'marketing', 'finance'],
        'premium' => false,
        'paths' => [
            ['d' => 'M18 14 C10 14 7 24 10 34 C12 41 18 44 23 41 C22 33 23 22 28 16 C25 14 21 14 18 14 Z'],
            ['d' => 'M30 14 C40 12 49 18 50 28 C51 35 47 41 42 44 C41 51 45 55 50 53 L52 57 C44 60 35 55 35 45 C35 37 38 30 33 25 L26 22 Z', 'tone' => 2],
        ],
    ],
    [
        'name' => 'Royal Tuscan Elephant',
        'slug' => 'elephant-royal',
        'category' => 'animals',
        'keywords' => ['elephant', 'royal', 'crown', 'tusks', 'heritage', 'luxury', 'mascot', 'animals'],
        'styles' => ['luxury', 'bold', 'vintage', 'mascot'],
        'industries' => ['hospitality', 'real-estate', 'finance', 'ecommerce'],
        'premium' => false,
        'paths' => [
            // Crown + sweeping ears
            ['d' => 'M23 12 L27 6 L32 10 L37 6 L41 12 Z M19 16 C8 17 4 27 8 38 C11 45 18 46 23 43 Z M45 16 C56 17 60 27 56 38 C53 45 46 46 41 43 Z'],
            // Raised victory trunk + tusks
            ['d' => 'M24 16 H40 L43 29 L38 39 L40 48 C44 50 49 48 49 43 L54 45 C52 54 43 58 36 53 C32 50 31 44 30 39 L21 29 Z M19 39 L27 42 L25 46 L18 43 Z M45 39 L46 43 L39 46 L37 42 Z', 'tone' => 2],
        ],
    ],

    // --- EAGLE & FALCON MARKS ---
    [
        'name' => 'Eagle Shield Crest',
        'slug' => 'eagle-crest',
        'category' => 'animals',
        'keywords' => ['eagle', 'falcon', 'hawk', 'bird', 'wing', 'crest', 'freedom', 'mascot', 'animals'],
        'styles' => ['bold', 'badge', 'corporate', 'mascot'],
        'industries' => ['finance', 'law', 'automotive', 'consulting'],
        'premium' => false,
        'paths' => [
            ['d' => 'M4 16 L24 22 L32 12 L40 22 L60 16 L50 32 L54 38 L38 40 L32 52 L26 40 L10 38 L14 32 Z'],
            ['d' => 'M27 16 L32 11 L37 16 L39 24 L32 30 L25 24 Z', 'tone' => 2],
        ],
    ],
    [
        'name' => 'Soaring Eagle Wings',
        'slug' => 'eagle-wings',
        'category' => 'animals',
        'keywords' => ['eagle', 'wing', 'flight', 'freedom', 'bird', 'fast', 'mascot', 'animals'],
        'styles' => ['modern', 'bold', 'minimal', 'mascot'],
        'industries' => ['travel', 'automotive', 'fitness', 'technology'],
        'premium' => false,
        'paths' => [
            ['d' => 'M4 20 L28 28 L18 36 L6 30 Z M60 20 L36 28 L46 36 L58 30 Z'],
            ['d' => 'M32 10 L39 24 L35 52 L32 56 L29 52 L25 24 Z', 'tone' => 2],
        ],
    ],
    [
        'name' => 'Minimal Falcon Dive',
        'slug' => 'falcon-minimal',
        'category' => 'animals',
        'keywords' => ['falcon', 'eagle', 'hawk', 'dive', 'speed', 'bird', 'mascot', 'animals'],
        'styles' => ['minimal', 'geometric', 'tech', 'mascot'],
        'industries' => ['technology', 'software', 'automotive', 'gaming'],
        'premium' => false,
        'paths' => [
            ['d' => 'M8 14 L38 22 L56 12 L42 36 L26 54 L28 36 Z'],
            ['d' => 'M36 22 L56 12 L48 26 Z', 'tone' => 2],
        ],
    ],

    // --- WOLF & FOX MARKS ---
    [
        'name' => 'Geometric Wolf',
        'slug' => 'wolf-geometric',
        'category' => 'animals',
        'keywords' => ['wolf', 'pack', 'wild', 'canine', 'husky', 'esports', 'mascot', 'animals'],
        'styles' => ['geometric', 'bold', 'mascot', 'tech'],
        'industries' => ['gaming', 'technology', 'fitness', 'automotive'],
        'premium' => false,
        'paths' => [
            ['d' => 'M14 8 L25 18 H39 L50 8 L54 27 L46 43 L32 58 L18 43 L10 27 Z'],
            ['d' => 'M18 24 L27 29 L23 36 Z M46 24 L37 29 L41 36 Z M25 40 L32 48 L39 40 L35 49 L32 54 L29 49 Z', 'tone' => 2],
        ],
    ],
    [
        'name' => 'Howling Moon Wolf',
        'slug' => 'wolf-howl',
        'category' => 'animals',
        'keywords' => ['wolf', 'howl', 'moon', 'wild', 'nature', 'mascot', 'animals'],
        'styles' => ['badge', 'vintage', 'mascot'],
        'industries' => ['travel', 'gaming', 'music', 'entertainment'],
        'premium' => false,
        'paths' => [
            ['d' => 'M32 6 A26 26 0 1 0 32 58 A26 26 0 1 0 32 6 Z M32 10 A22 22 0 1 1 32 54 A22 22 0 1 1 32 10 Z', 'fill_rule' => 'evenodd'],
            ['d' => 'M16 52 L24 34 L27 20 L33 14 L36 18 L43 13 L41 25 L36 30 L42 40 L46 52 Z', 'tone' => 2],
        ],
    ],
    [
        'name' => 'Origami Fox',
        'slug' => 'fox-geometric',
        'category' => 'animals',
        'keywords' => ['fox', 'clever', 'swift', 'origami', 'wild', 'mascot', 'animals'],
        'styles' => ['geometric', 'modern', 'minimal', 'mascot'],
        'industries' => ['marketing', 'software', 'ai', 'ecommerce'],
        'premium' => false,
        'paths' => [
            ['d' => 'M10 10 L26 22 H38 L54 10 L52 32 L32 54 L12 32 Z'],
            ['d' => 'M12 32 L32 36 L52 32 L32 54 Z M21 27 L27 30 L24 32 Z M43 27 L37 30 L40 32 Z', 'tone' => 2, 'fill_rule' => 'evenodd'],
        ],
    ],

    // --- TIGER & PANTHER MARKS ---
    [
        'name' => 'Bengal Tiger Head',
        'slug' => 'tiger-head',
        'category' => 'animals',
        'keywords' => ['tiger', 'cat', 'feline', 'fierce', 'jungle', 'wild', 'mascot', 'animals'],
        'styles' => ['bold', 'mascot', 'badge'],
        'industries' => ['gaming', 'fitness', 'entertainment', 'automotive'],
        'premium' => false,
        'paths' => [
            ['d' => 'M14 10 L23 18 H41 L50 10 L54 27 L49 43 L38 54 H26 L15 43 L10 27 Z M17 26 L27 29 L21 33 Z M47 26 L37 29 L43 33 Z', 'fill_rule' => 'evenodd'],
            ['d' => 'M28 19 H36 L32 27 Z M13 33 L21 36 L15 39 Z M51 33 L43 36 L49 39 Z M27 41 H37 L32 47 Z', 'tone' => 2],
        ],
    ],
    [
        'name' => 'Sleek Black Panther',
        'slug' => 'panther-sleek',
        'category' => 'animals',
        'keywords' => ['panther', 'leopard', 'jaguar', 'cat', 'feline', 'fast', 'luxury', 'mascot', 'animals'],
        'styles' => ['minimal', 'luxury', 'bold', 'mascot'],
        'industries' => ['automotive', 'fashion', 'fitness', 'gaming'],
        'premium' => false,
        'paths' => [
            ['d' => 'M8 22 L18 14 L32 16 L47 22 L56 20 L48 30 L39 32 L46 42 L38 46 L30 35 L20 35 L10 46 L6 42 L16 30 Z'],
            ['d' => 'M44 24 L50 24 L47 27 Z', 'tone' => 2],
        ],
    ],
    [
        'name' => 'Geometric Jaguar Mask',
        'slug' => 'tiger-stripe',
        'category' => 'animals',
        'keywords' => ['tiger', 'jaguar', 'leopard', 'mask', 'cat', 'power', 'mascot', 'animals'],
        'styles' => ['geometric', 'bold', 'mascot'],
        'industries' => ['fitness', 'gaming', 'technology'],
        'premium' => false,
        'paths' => [
            ['d' => 'M12 12 L24 20 H40 L52 12 L50 32 L32 56 L14 32 Z'],
            ['d' => 'M20 26 L28 30 L24 34 Z M44 26 L36 30 L40 34 Z M27 38 H37 L32 46 Z', 'tone' => 2],
        ],
    ],

    // --- BEAR & BULL MARKS ---
    [
        'name' => 'Grizzly Bear Crest',
        'slug' => 'bear-geometric',
        'category' => 'animals',
        'keywords' => ['bear', 'grizzly', 'wildlife', 'mountain', 'strong', 'outdoor', 'mascot', 'animals'],
        'styles' => ['geometric', 'bold', 'badge', 'mascot'],
        'industries' => ['construction', 'travel', 'fitness', 'finance'],
        'premium' => false,
        'paths' => [
            ['d' => 'M12 12 A7 7 0 0 1 24 14 H40 A7 7 0 0 1 52 12 C56 17 55 24 50 26 L52 38 L39 54 H25 L12 38 L14 26 C9 24 8 17 12 12 Z'],
            ['d' => 'M24 31 H40 L42 42 L32 49 L22 42 Z M28 35 H36 L32 41 Z', 'tone' => 2, 'fill_rule' => 'evenodd'],
        ],
    ],
    [
        'name' => 'Market Bull Horns',
        'slug' => 'bull-horns',
        'category' => 'animals',
        'keywords' => ['bull', 'ox', 'horns', 'market', 'strength', 'finance', 'mascot', 'animals'],
        'styles' => ['bold', 'corporate', 'geometric', 'mascot'],
        'industries' => ['finance', 'fitness', 'automotive', 'consulting'],
        'premium' => false,
        'paths' => [
            ['d' => 'M6 12 C8 22 14 26 21 24 L25 38 L32 54 L39 38 L43 24 C50 26 56 22 58 12 C60 24 53 33 44 33 L40 50 L32 58 L24 50 L20 33 C11 33 4 24 6 12 Z'],
            ['d' => 'M24 22 H40 L37 36 L32 42 L27 36 Z', 'tone' => 2],
        ],
    ],

    // --- DRAGON & PHOENIX MARKS ---
    [
        'name' => 'Mythic Dragon Crest',
        'slug' => 'dragon-crest',
        'category' => 'animals',
        'keywords' => ['dragon', 'mythic', 'flame', 'fire', 'esports', 'gaming', 'mascot', 'animals'],
        'styles' => ['bold', 'mascot', 'futuristic'],
        'industries' => ['gaming', 'entertainment', 'technology'],
        'premium' => false,
        'paths' => [
            ['d' => 'M18 10 L30 16 L42 6 L46 20 L56 24 L42 32 L50 42 L34 40 L26 56 L18 42 L24 30 L10 24 L20 18 Z'],
            ['d' => 'M28 22 L38 22 L34 28 Z M14 44 L22 40 L20 50 Z', 'tone' => 2],
        ],
    ],
    [
        'name' => 'Rising Phoenix Emblem',
        'slug' => 'phoenix-wings',
        'category' => 'animals',
        'keywords' => ['phoenix', 'flame', 'fire', 'wings', 'rise', 'bird', 'mascot', 'animals'],
        'styles' => ['bold', 'modern', 'abstract', 'mascot'],
        'industries' => ['marketing', 'fitness', 'gaming', 'entertainment'],
        'premium' => false,
        'paths' => [
            ['d' => 'M6 18 C14 14 22 20 28 30 C30 20 32 10 32 6 C32 10 34 20 36 30 C42 20 50 14 58 18 C52 28 44 36 36 42 L32 56 L28 42 C20 36 12 28 6 18 Z'],
            ['d' => 'M32 14 L36 30 L32 44 L28 30 Z', 'tone' => 2],
        ],
    ],

    // --- STAG & HORSE MARKS ---
    [
        'name' => 'Highland Stag Antlers',
        'slug' => 'stag-antlers',
        'category' => 'animals',
        'keywords' => ['deer', 'stag', 'antler', 'elk', 'forest', 'heritage', 'mascot', 'animals'],
        'styles' => ['vintage', 'luxury', 'organic', 'mascot'],
        'industries' => ['real-estate', 'travel', 'fashion', 'restaurant'],
        'premium' => false,
        'paths' => [
            ['d' => 'M10 8 L16 20 L12 22 L20 26 L26 22 V28 L18 26 L24 33 L28 32 L32 36 L36 32 L40 33 L46 26 L38 28 V22 L44 26 L52 22 L48 20 L54 8 L46 18 L36 18 L34 28 H30 L28 18 L18 18 Z'],
            ['d' => 'M24 32 H40 L37 46 L32 56 L27 46 Z', 'tone' => 2],
        ],
    ],
    [
        'name' => 'Stallion Knight Crest',
        'slug' => 'horse-crest',
        'category' => 'animals',
        'keywords' => ['horse', 'stallion', 'knight', 'equine', 'speed', 'luxury', 'mascot', 'animals'],
        'styles' => ['luxury', 'corporate', 'bold', 'mascot'],
        'industries' => ['automotive', 'finance', 'consulting', 'real-estate'],
        'premium' => false,
        'paths' => [
            ['d' => 'M22 10 L32 14 L44 12 L52 22 L46 28 L38 26 L44 38 L46 54 H18 L24 38 C16 32 14 20 22 10 Z'],
            ['d' => 'M22 10 C15 16 14 26 20 34 L14 38 C10 28 12 16 22 10 Z', 'tone' => 2],
        ],
    ],

    // --- EXTRA CURATED BRAND EMBLEMS (COFFEE / MOUNTAIN / TECH / ROYAL) ---
    [
        'name' => 'Roastery Coffee Crest',
        'slug' => 'coffee-crest',
        'category' => 'food',
        'keywords' => ['coffee', 'espresso', 'roastery', 'cafe', 'cup', 'bean', 'food'],
        'styles' => ['vintage', 'badge', 'organic'],
        'industries' => ['coffee', 'restaurant', 'food'],
        'premium' => false,
        'paths' => [
            ['d' => 'M12 24 H44 V38 C44 48 36 54 28 54 C20 54 12 48 12 38 Z M44 28 H50 C54 28 54 38 50 38 H44 Z', 'fill_rule' => 'evenodd'],
            ['d' => 'M21 8 C23 12 20 15 22 20 M28 6 C30 10 27 14 29 20 M35 8 C37 12 34 15 36 20 M10 56 H46 V59 H10 Z', 'tone' => 2],
        ],
    ],
    [
        'name' => 'Alpine Summit Badge',
        'slug' => 'mountain-badge',
        'category' => 'nature',
        'keywords' => ['mountain', 'alpine', 'summit', 'peak', 'everest', 'outdoor', 'nature'],
        'styles' => ['badge', 'minimal', 'organic'],
        'industries' => ['travel', 'coffee', 'construction', 'fitness'],
        'premium' => false,
        'paths' => [
            ['d' => 'M32 6 L56 48 H8 Z M18 42 L26 28 L33 38 L40 24 L50 42 Z', 'fill_rule' => 'evenodd'],
            ['d' => 'M32 6 L40 20 L35 23 L32 19 L29 23 L24 20 Z', 'tone' => 2],
        ],
    ],
    [
        'name' => 'Minimal Zen Lotus',
        'slug' => 'lotus-minimal',
        'category' => 'nature',
        'keywords' => ['lotus', 'yoga', 'spa', 'wellness', 'flower', 'zen', 'nature'],
        'styles' => ['minimal', 'elegant', 'organic'],
        'industries' => ['beauty', 'healthcare', 'fitness'],
        'premium' => false,
        'paths' => [
            ['d' => 'M32 10 C38 20 38 34 32 44 C26 34 26 20 32 10 Z M12 26 C22 26 28 34 30 46 C18 44 12 36 12 26 Z M52 26 C42 26 36 34 34 46 C46 44 52 36 52 26 Z'],
            ['d' => 'M8 42 C18 44 26 48 32 54 C38 48 46 44 56 42 C50 52 40 56 32 56 C24 56 14 52 8 42 Z', 'tone' => 2],
        ],
    ],
    [
        'name' => 'AI Neural Hexagon',
        'slug' => 'ai-hex-brain',
        'category' => 'tech',
        'keywords' => ['ai', 'neural', 'brain', 'hex', 'network', 'digital', 'tech'],
        'styles' => ['tech', 'futuristic', 'geometric'],
        'industries' => ['ai', 'technology', 'software'],
        'premium' => false,
        'paths' => [
            ['d' => 'M32 4 L56 18 V46 L32 60 L8 46 V18 Z M32 10 L50 20.5 V43.5 L32 54 L14 43.5 V20.5 Z', 'fill_rule' => 'evenodd'],
            ['d' => 'M32 18 L43 28 L37 42 H27 L21 28 Z M32 25 A4 4 0 1 0 32 33 A4 4 0 1 0 32 25 Z', 'tone' => 2, 'fill_rule' => 'evenodd'],
        ],
    ],
    [
        'name' => 'Cyber Shield Lock',
        'slug' => 'cyber-shield',
        'category' => 'tech',
        'keywords' => ['cyber', 'security', 'shield', 'vault', 'trust', 'tech'],
        'styles' => ['tech', 'corporate', 'bold'],
        'industries' => ['technology', 'software', 'finance', 'law'],
        'premium' => false,
        'paths' => [
            ['d' => 'M32 4 L54 12 V30 C54 44 44 54 32 60 C20 54 10 44 10 30 V12 Z'],
            ['d' => 'M24 28 V24 C24 19 40 19 40 24 V28 H43 V42 H21 V28 Z M28 28 H36 V24 C36 22 28 22 28 24 Z', 'tone' => 2, 'fill_rule' => 'evenodd'],
        ],
    ],
    [
        'name' => 'Prism Optic Cube',
        'slug' => 'prism-cube',
        'category' => 'abstract',
        'keywords' => ['prism', 'cube', 'dimension', '3d', 'modern', 'abstract'],
        'styles' => ['geometric', 'modern', 'abstract'],
        'industries' => ['architecture', 'technology', 'marketing'],
        'premium' => false,
        'paths' => [
            ['d' => 'M32 6 L56 20 L32 34 L8 20 Z'],
            ['d' => 'M8 24 L30 37 V58 L8 45 Z M56 24 L34 37 V58 L56 45 Z', 'tone' => 2],
        ],
    ],
    [
        'name' => 'Heritage Laurel Crown',
        'slug' => 'crown-emblem',
        'category' => 'badges',
        'keywords' => ['crown', 'laurel', 'luxury', 'royal', 'king', 'badges'],
        'styles' => ['luxury', 'vintage', 'badge'],
        'industries' => ['fashion', 'beauty', 'finance', 'law'],
        'premium' => false,
        'paths' => [
            ['d' => 'M16 42 C10 34 10 22 18 14 C16 24 18 32 24 38 Z M48 42 C54 34 54 22 46 14 C48 24 46 32 40 38 Z'],
            ['d' => 'M20 26 L26 16 L32 22 L38 16 L44 26 L41 36 H23 Z M21 38 H43 V42 H21 Z', 'tone' => 2],
        ],
    ],
    [
        'name' => 'Botanical Atelier Crest',
        'slug' => 'floral-crest',
        'category' => 'nature',
        'keywords' => ['floral', 'bloom', 'rose', 'botanical', 'fashion', 'beauty', 'nature'],
        'styles' => ['elegant', 'luxury', 'organic'],
        'industries' => ['beauty', 'fashion', 'ecommerce'],
        'premium' => false,
        'paths' => [
            ['d' => 'M32 8 C39 14 39 24 32 30 C25 24 25 14 32 8 Z M10 30 C16 23 26 23 32 30 C26 37 16 37 10 30 Z M54 30 C48 23 38 23 32 30 C38 37 48 37 54 30 Z'],
            ['d' => 'M32 30 C39 36 39 46 32 52 C25 46 25 36 32 30 Z M32 24 A6 6 0 1 0 32 36 A6 6 0 1 0 32 24 Z', 'tone' => 2],
        ],
    ],
    [
        'name' => 'Monarch Wing Crest',
        'slug' => 'monarch-wing',
        'category' => 'animals',
        'keywords' => ['butterfly', 'monarch', 'wing', 'beauty', 'fashion', 'transform', 'animals'],
        'styles' => ['elegant', 'modern', 'organic'],
        'industries' => ['beauty', 'fashion', 'healthcare'],
        'premium' => false,
        'paths' => [
            ['d' => 'M30 28 C20 12 6 12 8 26 C9 34 18 36 28 34 C18 40 16 52 24 52 C29 52 31 44 31 36 Z M34 28 C44 12 58 12 56 26 C55 34 46 36 36 34 C46 40 48 52 40 52 C35 52 33 44 33 36 Z'],
            ['d' => 'M31 18 H33 V46 H31 Z', 'tone' => 2],
        ],
    ],
];
