<?php

/**
 * Original vector symbol library.
 *
 * Each symbol is authored in a 64x64 box and stored as path data so it can be
 * recoloured per path in the editor and rendered server-side without any
 * external icon dependency. Tone 2 paths use the palette's accent colour.
 */
return [
    ['name' => 'Orbit', 'slug' => 'orbit', 'keywords' => ['orbit', 'circle', 'space', 'digital'], 'styles' => ['tech', 'futuristic', 'modern'], 'industries' => ['technology', 'ai', 'software'], 'premium' => false,
        'paths' => [['d' => 'M32 5a27 27 0 1 0 0 54a27 27 0 1 0 0-54Zm0 7a20 20 0 1 1 0 40a20 20 0 1 1 0-40Z', 'fill_rule' => 'evenodd'], ['d' => 'M50 10a5 5 0 1 1-10 0a5 5 0 1 1 10 0Z', 'tone' => 2]]],

    ['name' => 'Hexagon', 'slug' => 'hexagon', 'keywords' => ['geometric', 'structure', 'tech', 'frame'], 'styles' => ['geometric', 'tech', 'modern'], 'industries' => ['technology', 'construction', 'software'], 'premium' => false,
        'paths' => [['d' => 'M32 3l25 14.5v29L32 61 7 46.5v-29z'], ['d' => 'M32 15l15 8.5v17L32 49 17 40.5v-17z', 'tone' => 2]]],

    ['name' => 'Spark', 'slug' => 'spark', 'keywords' => ['spark', 'energy', 'star', 'idea'], 'styles' => ['bold', 'modern', 'playful'], 'industries' => ['marketing', 'ai', 'entertainment'], 'premium' => false,
        'paths' => [['d' => 'M32 2l7.5 22.5L62 32l-22.5 7.5L32 62l-7.5-22.5L2 32l22.5-7.5z']]],

    ['name' => 'Nodes', 'slug' => 'nodes', 'keywords' => ['network', 'connect', 'nodes', 'data'], 'styles' => ['tech', 'abstract', 'geometric'], 'industries' => ['technology', 'ai', 'software', 'consulting'], 'premium' => false,
        'paths' => [['d' => 'M32 8L57 52H7ZM32 24L45 46H19Z', 'fill_rule' => 'evenodd'], ['d' => 'M32 12a4 4 0 1 1 0 8a4 4 0 1 1 0-8ZM55 48a4 4 0 1 1 0 8a4 4 0 1 1 0-8ZM9 48a4 4 0 1 1 0 8a4 4 0 1 1 0-8Z', 'tone' => 2]]],

    ['name' => 'Layers', 'slug' => 'layers', 'keywords' => ['layers', 'stack', 'depth', 'platform'], 'styles' => ['modern', 'geometric', 'corporate'], 'industries' => ['software', 'technology', 'finance'], 'premium' => false,
        'paths' => [['d' => 'M32 4l26 14-26 14L6 18z'], ['d' => 'M6 30l26 14 26-14v7L32 51 6 37z'], ['d' => 'M6 40l26 14 26-14v7L32 61 6 47z', 'tone' => 2]]],

    ['name' => 'Cube', 'slug' => 'cube', 'keywords' => ['cube', 'block', 'product', 'structure'], 'styles' => ['geometric', 'modern', 'tech'], 'industries' => ['technology', 'architecture', 'ecommerce'], 'premium' => false,
        'paths' => [['d' => 'M32 3l25 14v30L32 61 7 47V17z'], ['d' => 'M32 20l13 7.5v15L32 50 19 42.5v-15z', 'tone' => 2]]],

    ['name' => 'Bolt', 'slug' => 'bolt', 'keywords' => ['speed', 'power', 'energy', 'fast'], 'styles' => ['bold', 'tech', 'modern'], 'industries' => ['fitness', 'technology', 'automotive', 'gaming'], 'premium' => false,
        'paths' => [['d' => 'M38 2L12 36h14l-4 26 30-38H38z']]],

    ['name' => 'Leaf', 'slug' => 'leaf', 'keywords' => ['leaf', 'nature', 'organic', 'growth'], 'styles' => ['organic', 'minimal', 'elegant'], 'industries' => ['agriculture', 'food', 'beauty', 'healthcare'], 'premium' => false,
        'paths' => [['d' => 'M56 6C30 8 12 24 12 42c0 10 7 16 16 16 18 0 32-22 28-52Z'], ['d' => 'M26 54c4-14 12-26 24-34-10 14-16 26-18 38z', 'tone' => 2]]],

    ['name' => 'Mountain', 'slug' => 'mountain', 'keywords' => ['peak', 'outdoor', 'travel', 'summit'], 'styles' => ['minimal', 'organic', 'badge'], 'industries' => ['travel', 'photography', 'agriculture'], 'premium' => false,
        'paths' => [['d' => 'M2 54L24 16l11 19 9-13 18 32z'], ['d' => 'M24 16l4 7-4 6-4-6z', 'tone' => 2]]],

    ['name' => 'Waveform', 'slug' => 'waveform', 'keywords' => ['sound', 'wave', 'audio', 'signal'], 'styles' => ['modern', 'bold', 'abstract'], 'industries' => ['music', 'technology', 'entertainment'], 'premium' => false,
        'paths' => [['d' => 'M8 36h6v20H8zM20 20h6v36h-6zM32 28h6v28h-6zM44 12h6v44h-6zM56 32h4v20h-4z']]],

    ['name' => 'Compass', 'slug' => 'compass', 'keywords' => ['compass', 'travel', 'direction', 'explore'], 'styles' => ['badge', 'vintage', 'modern'], 'industries' => ['travel', 'real-estate', 'consulting'], 'premium' => false,
        'paths' => [['d' => 'M32 4a28 28 0 1 0 0 56a28 28 0 1 0 0-56Zm0 6a22 22 0 1 1 0 44a22 22 0 1 1 0-44Z', 'fill_rule' => 'evenodd'], ['d' => 'M32 14l7 18-7 18-7-18z', 'tone' => 2]]],

    ['name' => 'Shield', 'slug' => 'shield', 'keywords' => ['shield', 'protect', 'trust', 'secure'], 'styles' => ['corporate', 'bold', 'badge'], 'industries' => ['finance', 'law', 'healthcare', 'automotive'], 'premium' => false,
        'paths' => [['d' => 'M32 4l24 9v20c0 12-10 22-24 27C18 55 8 45 8 33V13z'], ['d' => 'M32 16l13 5v12c0 7-6 13-13 17-7-4-13-10-13-17V21z', 'tone' => 2]]],

    ['name' => 'Cross', 'slug' => 'cross', 'keywords' => ['health', 'medical', 'care', 'plus'], 'styles' => ['minimal', 'corporate', 'modern'], 'industries' => ['healthcare', 'beauty'], 'premium' => false,
        'paths' => [['d' => 'M26 6h12v20h20v12H38v20H26V38H6V26h20z']]],

    ['name' => 'Target', 'slug' => 'target', 'keywords' => ['target', 'focus', 'goal', 'precision'], 'styles' => ['geometric', 'minimal', 'tech'], 'industries' => ['marketing', 'consulting', 'fitness'], 'premium' => false,
        'paths' => [['d' => 'M32 4a28 28 0 1 0 0 56a28 28 0 1 0 0-56Zm0 6a22 22 0 1 1 0 44a22 22 0 1 1 0-44Z', 'fill_rule' => 'evenodd'], ['d' => 'M32 20a12 12 0 1 1 0 24a12 12 0 1 1 0-24Z', 'tone' => 2]]],

    ['name' => 'Droplet', 'slug' => 'droplet', 'keywords' => ['water', 'drop', 'pure', 'liquid'], 'styles' => ['minimal', 'organic', 'modern'], 'industries' => ['beauty', 'healthcare', 'food'], 'premium' => false,
        'paths' => [['d' => 'M32 4c10 14 18 23 18 33a18 18 0 1 1-36 0C14 27 22 18 32 4Z']]],

    ['name' => 'Flame', 'slug' => 'flame', 'keywords' => ['fire', 'flame', 'heat', 'passion'], 'styles' => ['bold', 'vintage', 'modern'], 'industries' => ['restaurant', 'fitness', 'entertainment'], 'premium' => false,
        'paths' => [['d' => 'M32 4c6 12 14 16 14 28a14 14 0 0 1-28 0c0-8 4-12 8-18 2 4 4 6 6 6 0-6 0-10 0-16Z'], ['d' => 'M32 30c3 5 6 8 6 13a6 6 0 0 1-12 0c0-5 3-8 6-13Z', 'tone' => 2]]],

    ['name' => 'Cup', 'slug' => 'cup', 'keywords' => ['coffee', 'cup', 'cafe', 'drink'], 'styles' => ['vintage', 'badge', 'playful'], 'industries' => ['coffee', 'restaurant', 'food'], 'premium' => false,
        'paths' => [['d' => 'M10 18h36v16a18 18 0 0 1-36 0z'], ['d' => 'M46 22h6a7 7 0 0 1 0 14h-6z', 'tone' => 2], ['d' => 'M20 6c0 3 2 4 2 7M30 4c0 3 2 4 2 7M40 6c0 3 2 4 2 7', 'tone' => 2]]],

    ['name' => 'Bean', 'slug' => 'bean', 'keywords' => ['coffee', 'bean', 'roast', 'craft'], 'styles' => ['vintage', 'organic', 'badge'], 'industries' => ['coffee', 'food'], 'premium' => false,
        'paths' => [['d' => 'M32 8a24 24 0 1 1 0 48a24 24 0 1 1 0-48Z'], ['d' => 'M40 14c-8 6-12 14-14 24 6 6 12 8 18 8-4-4-6-8-6-14 0-6 2-12 6-18z', 'tone' => 2]]],

    ['name' => 'Wheat', 'slug' => 'wheat', 'keywords' => ['wheat', 'harvest', 'grain', 'farm'], 'styles' => ['vintage', 'organic', 'badge'], 'industries' => ['agriculture', 'food', 'restaurant'], 'premium' => false,
        'paths' => [['d' => 'M30 16h4v42h-4z'], ['d' => 'M32 18c-4-4-10-4-14 0 4 6 10 6 14 2zM32 18c4-4 10-4 14 0-4 6-10 6-14 2zM32 30c-4-4-10-4-14 0 4 6 10 6 14 2zM32 30c4-4 10-4 14 0-4 6-10 6-14 2zM32 42c-4-4-10-4-14 0 4 6 10 6 14 2zM32 42c4-4 10-4 14 0-4 6-10 6-14 2z', 'tone' => 2]]],

    ['name' => 'Utensils', 'slug' => 'utensils', 'keywords' => ['food', 'restaurant', 'dining', 'kitchen'], 'styles' => ['vintage', 'badge', 'minimal'], 'industries' => ['restaurant', 'food'], 'premium' => false,
        'paths' => [['d' => 'M12 6h4v16h4V6h4v20c0 5-2 7-4 9v23h-4V35c-2-2-4-4-4-9z'], ['d' => 'M50 6c5 8 5 18-2 24v28h-4V6z', 'tone' => 2]]],

    ['name' => 'Paw', 'slug' => 'paw', 'keywords' => ['pet', 'animal', 'paw', 'care'], 'styles' => ['playful', 'organic', 'badge'], 'industries' => ['pet', 'healthcare'], 'premium' => false,
        'paths' => [['d' => 'M32 34c8 0 15 6 15 13 0 6-6 9-15 9s-15-3-15-9c0-7 7-13 15-13Z'], ['d' => 'M18 16a6 7 0 1 1 0 14 6 7 0 1 1 0-14ZM27 10a6 7 0 1 1 0 14 6 7 0 1 1 0-14ZM37 10a6 7 0 1 1 0 14 6 7 0 1 1 0-14ZM46 16a6 7 0 1 1 0 14 6 7 0 1 1 0-14Z', 'tone' => 2]]],

    ['name' => 'Aperture', 'slug' => 'aperture', 'keywords' => ['camera', 'photo', 'lens', 'creative'], 'styles' => ['geometric', 'modern', 'minimal'], 'industries' => ['photography', 'marketing', 'entertainment'], 'premium' => false,
        'paths' => [['d' => 'M32 4a28 28 0 1 0 0 56a28 28 0 1 0 0-56Zm0 7a21 21 0 1 1 0 42a21 21 0 1 1 0-42Z', 'fill_rule' => 'evenodd'], ['d' => 'M32 32L18 10h12zM32 32L54 20v12zM32 32L54 44v12zM32 32L46 54H34zM32 32L10 44V32zM32 32L10 20V8h12z', 'tone' => 2]]],

    ['name' => 'Lens', 'slug' => 'lens', 'keywords' => ['lens', 'focus', 'photo', 'vision'], 'styles' => ['minimal', 'modern', 'badge'], 'industries' => ['photography', 'technology', 'healthcare'], 'premium' => false,
        'paths' => [['d' => 'M32 4a28 28 0 1 0 0 56a28 28 0 1 0 0-56Zm0 7a21 21 0 1 1 0 42a21 21 0 1 1 0-42Z', 'fill_rule' => 'evenodd'], ['d' => 'M32 22a10 10 0 1 1 0 20a10 10 0 1 1 0-20Z', 'tone' => 2]]],

    ['name' => 'Bag', 'slug' => 'bag', 'keywords' => ['shop', 'bag', 'retail', 'store'], 'styles' => ['modern', 'playful', 'minimal'], 'industries' => ['ecommerce', 'fashion'], 'premium' => false,
        'paths' => [['d' => 'M12 22h40l-4 36H16z'], ['d' => 'M22 22v-2a10 10 0 0 1 20 0v2h-5v-2a5 5 0 0 0-10 0v2z', 'tone' => 2]]],

    ['name' => 'Arrow', 'slug' => 'arrow', 'keywords' => ['arrow', 'growth', 'direction', 'up'], 'styles' => ['bold', 'modern', 'minimal'], 'industries' => ['marketing', 'finance', 'consulting'], 'premium' => false,
        'paths' => [['d' => 'M12 44L38 18H24V10h30v30h-8V26L20 52z']]],

    ['name' => 'Triangle', 'slug' => 'triangle', 'keywords' => ['triangle', 'peak', 'geometry', 'balance'], 'styles' => ['geometric', 'minimal', 'modern'], 'industries' => ['architecture', 'technology', 'consulting'], 'premium' => false,
        'paths' => [['d' => 'M32 6l28 48H4z'], ['d' => 'M32 24l13 22H19z', 'tone' => 2]]],

    ['name' => 'Arch', 'slug' => 'arch', 'keywords' => ['arch', 'architecture', 'doorway', 'space'], 'styles' => ['minimal', 'geometric', 'elegant'], 'industries' => ['architecture', 'real-estate', 'construction'], 'premium' => false,
        'paths' => [['d' => 'M8 56V32a24 24 0 0 1 48 0v24h-10V32a14 14 0 0 0-28 0v24z']]],

    ['name' => 'Column', 'slug' => 'column', 'keywords' => ['column', 'law', 'institution', 'classic'], 'styles' => ['corporate', 'elegant', 'badge'], 'industries' => ['law', 'finance', 'education'], 'premium' => true,
        'paths' => [['d' => 'M12 10h40v6H12zM18 16h28v4H18zM22 20h4v26h-4zM38 20h4v26h-4zM30 20h4v26h-4zM12 46h40v4H12zM8 50h48v6H8z']]],

    ['name' => 'Book', 'slug' => 'book', 'keywords' => ['book', 'learn', 'education', 'study'], 'styles' => ['elegant', 'vintage', 'corporate'], 'industries' => ['education', 'law', 'consulting'], 'premium' => false,
        'paths' => [['d' => 'M30 10c-7-3-14-4-22-4v40c8 0 15 1 22 4z'], ['d' => 'M34 10c7-3 14-4 22-4v40c-8 0-15 1-22 4z', 'tone' => 2]]],

    ['name' => 'Graduation', 'slug' => 'graduation', 'keywords' => ['education', 'school', 'academy', 'graduate'], 'styles' => ['corporate', 'badge', 'modern'], 'industries' => ['education'], 'premium' => false,
        'paths' => [['d' => 'M32 8l28 14-28 14L4 22z'], ['d' => 'M12 30v12c0 4 9 8 20 8s20-4 20-8V30L32 40z', 'tone' => 2]]],

    ['name' => 'Heart', 'slug' => 'heart', 'keywords' => ['heart', 'love', 'care', 'health'], 'styles' => ['playful', 'elegant', 'minimal'], 'industries' => ['healthcare', 'beauty', 'pet'], 'premium' => false,
        'paths' => [['d' => 'M32 56C12 42 4 32 4 22a13 13 0 0 1 24-7l4 5 4-5a13 13 0 0 1 24 7c0 10-8 20-28 34z']]],

    ['name' => 'Petal', 'slug' => 'petal', 'keywords' => ['flower', 'petal', 'beauty', 'bloom'], 'styles' => ['elegant', 'organic', 'luxury'], 'industries' => ['beauty', 'fashion', 'healthcare'], 'premium' => true,
        'paths' => [['d' => 'M32 4c8 8 8 18 0 26-8-8-8-18 0-26Z'], ['d' => 'M32 34c8 8 8 18 0 26-8-8-8-18 0-26Z', 'tone' => 2], ['d' => 'M4 32c8-8 18-8 26 0-8 8-18 8-26 0Z', 'tone' => 2], ['d' => 'M34 32c8-8 18-8 26 0-8 8-18 8-26 0Z', 'tone' => 2]]],

    ['name' => 'Sun', 'slug' => 'sun', 'keywords' => ['sun', 'solar', 'energy', 'day'], 'styles' => ['playful', 'badge', 'modern'], 'industries' => ['agriculture', 'travel', 'fitness'], 'premium' => false,
        'paths' => [['d' => 'M32 20a12 12 0 1 1 0 24a12 12 0 1 1 0-24Z'], ['d' => 'M30 2h4v11h-4zM30 51h4v11h-4zM2 30h11v4H2zM51 30h11v4H51zM10 10l3-3 8 8-3 3zM43 43l3-3 8 8-3 3zM54 10l3 3-8 8-3-3zM19 43l3 3-8 8-3-3z', 'tone' => 2]]],

    ['name' => 'Play', 'slug' => 'play', 'keywords' => ['play', 'video', 'media', 'start'], 'styles' => ['bold', 'modern', 'minimal'], 'industries' => ['entertainment', 'music', 'gaming'], 'premium' => false,
        'paths' => [['d' => 'M14 8l38 24-38 24z']]],

    ['name' => 'Star', 'slug' => 'star', 'keywords' => ['star', 'quality', 'premium', 'rating'], 'styles' => ['badge', 'vintage', 'bold'], 'industries' => ['entertainment', 'beauty', 'restaurant'], 'premium' => false,
        'paths' => [['d' => 'M32 3l9 19 21 3-15 15 4 21-19-10-19 10 4-21L2 25l21-3z']]],

    ['name' => 'Diamond', 'slug' => 'diamond', 'keywords' => ['luxury', 'diamond', 'premium', 'value'], 'styles' => ['luxury', 'elegant', 'minimal'], 'industries' => ['fashion', 'beauty', 'finance'], 'premium' => true,
        'paths' => [['d' => 'M18 8h28l14 18-28 32L4 26z'], ['d' => 'M4 26h56L32 58z', 'tone' => 2]]],

    ['name' => 'Chevrons', 'slug' => 'chevrons', 'keywords' => ['motion', 'forward', 'fast', 'progress'], 'styles' => ['bold', 'tech', 'modern'], 'industries' => ['automotive', 'fitness', 'marketing'], 'premium' => false,
        'paths' => [['d' => 'M6 12l16 20-16 20h10l16-20-16-20zM30 12l16 20-16 20h10l16-20-16-20z']]],

    ['name' => 'Pin', 'slug' => 'pin', 'keywords' => ['location', 'place', 'map', 'local'], 'styles' => ['modern', 'minimal', 'playful'], 'industries' => ['travel', 'real-estate', 'restaurant'], 'premium' => false,
        'paths' => [['d' => 'M32 4a20 20 0 0 1 20 20c0 14-20 36-20 36S12 38 12 24A20 20 0 0 1 32 4Z'], ['d' => 'M32 16a8 8 0 1 1 0 16a8 8 0 1 1 0-16Z', 'tone' => 2]]],

    ['name' => 'Cloud', 'slug' => 'cloud', 'keywords' => ['cloud', 'saas', 'data', 'sky'], 'styles' => ['modern', 'minimal', 'tech'], 'industries' => ['software', 'technology', 'ai'], 'premium' => false,
        'paths' => [['d' => 'M20 50a13 13 0 0 1-2-26 17 17 0 0 1 32-4 12 12 0 0 1 2 30z']]],

    ['name' => 'Brackets', 'slug' => 'brackets', 'keywords' => ['code', 'developer', 'software', 'build'], 'styles' => ['tech', 'modern', 'minimal'], 'industries' => ['software', 'technology', 'gaming'], 'premium' => false,
        'paths' => [['d' => 'M24 14L6 32l18 18 4-5-13-13 13-13zM40 14l18 18-18 18-4-5 13-13-13-13z'], ['d' => 'M36 10l-8 44h-4l8-44z', 'tone' => 2]]],

    ['name' => 'Grid', 'slug' => 'grid', 'keywords' => ['grid', 'structure', 'system', 'design'], 'styles' => ['geometric', 'minimal', 'corporate'], 'industries' => ['architecture', 'software', 'consulting'], 'premium' => false,
        'paths' => [['d' => 'M6 6h14v14H6zM25 6h14v14H25zM44 6h14v14H44zM6 25h14v14H6zM25 25h14v14H25zM44 25h14v14H44zM6 44h14v14H6zM25 44h14v14H25zM44 44h14v14H44z']]],

    ['name' => 'Ring', 'slug' => 'ring', 'keywords' => ['ring', 'circle', 'unity', 'simple'], 'styles' => ['minimal', 'geometric', 'modern'], 'industries' => ['finance', 'consulting', 'technology'], 'premium' => false,
        'paths' => [['d' => 'M32 6a26 26 0 1 0 0 52a26 26 0 1 0 0-52Zm0 9a17 17 0 1 1 0 34a17 17 0 1 1 0-34Z', 'fill_rule' => 'evenodd']]],

    ['name' => 'Flow', 'slug' => 'flow', 'keywords' => ['flow', 'motion', 'wave', 'rhythm'], 'styles' => ['organic', 'modern', 'abstract'], 'industries' => ['music', 'marketing', 'fitness'], 'premium' => true,
        'paths' => [['d' => 'M4 34c8 0 10-16 18-16s10 28 18 28 10-14 18-14v10c-8 0-10 14-18 14S30 28 22 28 14 44 6 44z']]],
];
