<?php

namespace App\Services\Logo;

/**
 * Parses customer intent from both business-name prompts (e.g. "Manoj with lion, elephant")
 * and keyword strings, applying typo correction, synonym expansion, and style/colour inference.
 */
class KeywordNormalizer
{
    private const STOPWORDS = [
        'a', 'an', 'the', 'logo', 'logos', 'icon', 'icons', 'symbol', 'symbols',
        'design', 'designs', 'style', 'with', 'and', 'for', 'of', 'in', 'on',
        'my', 'brand', 'company', 'business', 'please', 'make', 'create', 'featuring',
        'feat', 'using', 'having', 'including',
    ];

    /** Style adjectives mapped cleanly to canonical LogoForge style slugs. */
    private const STYLE_MAP = [
        'minimalist' => 'minimal',
        'minimal' => 'minimal',
        'simple' => 'minimal',
        'clean' => 'minimal',
        'modern' => 'modern',
        'sleek' => 'modern',
        'contemporary' => 'modern',
        'geometric' => 'geometric',
        'poly' => 'geometric',
        'polygonal' => 'geometric',
        'luxury' => 'luxury',
        'luxurious' => 'luxury',
        'royal' => 'luxury',
        'premium' => 'luxury',
        'vintage' => 'vintage',
        'retro' => 'retro',
        'classic' => 'elegant',
        'elegant' => 'elegant',
        'corporate' => 'corporate',
        'professional' => 'corporate',
        'playful' => 'playful',
        'fun' => 'playful',
        'cute' => 'playful',
        'bold' => 'bold',
        'strong' => 'bold',
        'futuristic' => 'futuristic',
        'tech' => 'tech',
        'digital' => 'tech',
        'cyber' => 'tech',
        'organic' => 'organic',
        'natural' => 'organic',
        'eco' => 'organic',
        'abstract' => 'abstract',
        'monogram' => 'monogram',
        'mascot' => 'mascot',
        'badge' => 'badge',
        'emblem' => 'badge',
        'crest' => 'badge',
    ];

    /** Adjectives handled purely as style/colour hints rather than locking the symbol subject pool. */
    private const PURE_STYLE_MODIFIERS = [
        'minimalist', 'minimal', 'simple', 'clean', 'modern', 'sleek', 'contemporary',
        'vintage', 'retro', 'classic', 'elegant', 'corporate', 'professional',
        'playful', 'fun', 'cute', 'bold', 'futuristic', 'luxury', 'luxurious', 'premium',
    ];

    private const COLOR_MAP = [
        'gold' => 'orange',
        'golden' => 'orange',
        'amber' => 'orange',
        'yellow' => 'orange',
        'orange' => 'orange',
        'crimson' => 'red',
        'scarlet' => 'red',
        'maroon' => 'red',
        'red' => 'red',
        'navy' => 'blue',
        'cyan' => 'blue',
        'teal' => 'blue',
        'azure' => 'blue',
        'blue' => 'blue',
        'emerald' => 'green',
        'olive' => 'green',
        'forest' => 'green',
        'green' => 'green',
        'violet' => 'purple',
        'indigo' => 'purple',
        'lavender' => 'purple',
        'purple' => 'purple',
        'magenta' => 'pink',
        'rose' => 'pink',
        'pink' => 'pink',
        'silver' => 'grayscale',
        'grey' => 'grayscale',
        'gray' => 'grayscale',
        'mono' => 'grayscale',
        'monochrome' => 'grayscale',
        'grayscale' => 'grayscale',
        'white' => 'grayscale',
        'dark' => 'black',
        'charcoal' => 'black',
        'black' => 'black',
    ];

    private const SYNONYMS = [
        'kitty' => ['cat'],
        'kitten' => ['cat'],
        'feline' => ['cat', 'lion', 'tiger'],
        'puppy' => ['dog'],
        'pup' => ['dog'],
        'canine' => ['dog', 'wolf'],
        'cafe' => ['coffee'],
        'espresso' => ['coffee'],
        'latte' => ['coffee'],
        'barista' => ['coffee'],
        'safari' => ['lion', 'elephant'],
        'jungle' => ['lion', 'elephant', 'tiger'],
        'savanna' => ['lion', 'elephant'],
        'wildlife' => ['lion', 'elephant', 'bear'],
        'raptor' => ['eagle'],
        'hawk' => ['eagle'],
        'falcon' => ['eagle', 'falcon'],
        'gym' => ['dumbbell'],
        'workout' => ['dumbbell'],
        'fitness' => ['dumbbell'],
        'house' => ['home'],
        'property' => ['home'],
        'realty' => ['home'],
        'realestate' => ['home'],
        'legal' => ['scales'],
        'attorney' => ['scales'],
        'lawyer' => ['scales'],
        'bakery' => ['cake'],
        'pastry' => ['cake'],
        'animal' => ['animals'],
        'beast' => ['lion', 'tiger', 'bear'],
        'crests' => ['crest'],
    ];

    /**
     * Split natural-language brand prompt ("Manoj with lion, elephant") into clean business_name + raw keyword tokens.
     *
     * @return array{business_name: string, raw_tokens: array<int, string>, keywords_string: string}
     */
    public function parseInput(string $rawBrandName, ?string $rawKeywords = null): array
    {
        $cleanBrand = trim(preg_replace('/\s+/u', ' ', $rawBrandName) ?? '');
        $extractedClause = '';

        if (preg_match('/^(.+?)\s+(?:logo\s+with|with|featuring|feat\.?|using|having|including|icon|symbol)\s+(.+)$/iu', $cleanBrand, $matches)) {
            $left = trim($matches[1], " \t\n\r\0\x0B,.;:-");
            $right = trim($matches[2]);

            if (mb_strlen($left) >= 2 && $right !== '') {
                $cleanBrand = $left;
                $extractedClause = $right;
            }
        }

        if (mb_strlen($cleanBrand) > 60) {
            $cleanBrand = trim(mb_substr($cleanBrand, 0, 60));
        }

        $combined = trim(implode(' ', array_filter([$extractedClause, (string) $rawKeywords])));
        $rawParts = $combined === '' ? [] : (preg_split('/[\s,;\/+&|]+/u', $combined) ?: []);

        $tokens = [];
        foreach ($rawParts as $part) {
            $token = mb_strtolower(trim($part, " \t\n\r\0\x0B\"'`.!?:;()[]{}"));

            if ($token === '' || mb_strlen($token) < 2 || in_array($token, self::STOPWORDS, true)) {
                continue;
            }

            $tokens[] = $token;
        }

        $tokens = array_values(array_unique($tokens));

        return [
            'business_name' => $cleanBrand,
            'raw_tokens' => $tokens,
            'keywords_string' => implode(', ', $tokens),
        ];
    }

    /**
     * Resolve raw tokens against catalogue vocabulary with singular stems, synonyms, and typo tolerance.
     *
     * @param  array<int, string>  $rawTokens
     * @param  array<int, string>  $vocabulary
     * @return array{
     *     terms: array<int, string>,
     *     corrections: array<string, string>,
     *     inferred_styles: array<int, string>,
     *     inferred_colors: array<int, string>
     * }
     */
    public function resolve(array $rawTokens, array $vocabulary): array
    {
        $vocabSet = [];
        $vocabList = [];

        foreach ($vocabulary as $word) {
            $clean = mb_strtolower(trim((string) $word));
            if ($clean === '' || mb_strlen($clean) < 2 || isset($vocabSet[$clean])) {
                continue;
            }
            $vocabSet[$clean] = true;
            $vocabList[] = $clean;
        }

        $terms = [];
        $corrections = [];
        $inferredStyles = [];
        $inferredColors = [];

        foreach ($rawTokens as $token) {
            if (isset(self::STYLE_MAP[$token])) {
                $inferredStyles[] = self::STYLE_MAP[$token];
                if (in_array($token, self::PURE_STYLE_MODIFIERS, true)) {
                    continue;
                }
            }

            if (isset(self::COLOR_MAP[$token])) {
                $inferredColors[] = self::COLOR_MAP[$token];
                continue;
            }

            if (isset(self::SYNONYMS[$token])) {
                foreach (self::SYNONYMS[$token] as $expanded) {
                    $terms[] = $expanded;
                }
                continue;
            }

            if (isset($vocabSet[$token])) {
                $terms[] = $token;
                continue;
            }

            $stem = $this->stem($token);
            if ($stem !== $token && isset($vocabSet[$stem])) {
                $terms[] = $stem;
                continue;
            }

            if (mb_strlen($token) >= 4) {
                $prefixMatch = $this->prefixMatch($token, $vocabList);
                if ($prefixMatch !== null) {
                    $terms[] = $prefixMatch;
                    $corrections[$token] = $prefixMatch;
                    continue;
                }
            }

            $fuzzy = $this->fuzzyMatch($token, $vocabList);
            if ($fuzzy !== null) {
                $terms[] = $fuzzy;
                $corrections[$token] = $fuzzy;
                continue;
            }

            // Also check fuzzy match against known styles/colours/synonyms if not in symbol vocab.
            $specialPool = array_unique(array_merge(
                array_keys(self::STYLE_MAP),
                array_keys(self::COLOR_MAP),
                array_keys(self::SYNONYMS),
            ));
            $fuzzySpecial = $this->fuzzyMatch($token, $specialPool);
            if ($fuzzySpecial !== null) {
                $corrections[$token] = $fuzzySpecial;
                if (isset(self::STYLE_MAP[$fuzzySpecial])) {
                    $inferredStyles[] = self::STYLE_MAP[$fuzzySpecial];
                } elseif (isset(self::COLOR_MAP[$fuzzySpecial])) {
                    $inferredColors[] = self::COLOR_MAP[$fuzzySpecial];
                } elseif (isset(self::SYNONYMS[$fuzzySpecial])) {
                    foreach (self::SYNONYMS[$fuzzySpecial] as $expanded) {
                        $terms[] = $expanded;
                    }
                }
                continue;
            }

            $terms[] = $token;
        }

        return [
            'terms' => array_values(array_unique($terms)),
            'corrections' => $corrections,
            'inferred_styles' => array_values(array_unique($inferredStyles)),
            'inferred_colors' => array_values(array_unique($inferredColors)),
        ];
    }

    private function stem(string $word): string
    {
        if (str_ends_with($word, 'ies') && strlen($word) > 4) {
            return substr($word, 0, -3).'y';
        }

        if (str_ends_with($word, 'ches') || str_ends_with($word, 'shes') || str_ends_with($word, 'xes') || str_ends_with($word, 'ses')) {
            return substr($word, 0, -2);
        }

        if (str_ends_with($word, 's') && ! str_ends_with($word, 'ss') && strlen($word) > 3) {
            return substr($word, 0, -1);
        }

        return $word;
    }

    /**
     * @param  array<int, string>  $vocabList
     */
    private function prefixMatch(string $token, array $vocabList): ?string
    {
        $best = null;

        foreach ($vocabList as $candidate) {
            if (! str_starts_with($candidate, $token)) {
                continue;
            }

            if ($best === null || strlen($candidate) < strlen($best)) {
                $best = $candidate;
            }
        }

        return $best;
    }

    /**
     * @param  array<int, string>  $vocabList
     */
    private function fuzzyMatch(string $token, array $vocabList): ?string
    {
        $len = strlen($token);
        if ($len < 4 || $len > 30) {
            return null;
        }

        $maxDist = $len <= 5 ? 1 : ($len <= 9 ? 2 : 3);
        $bestCandidate = null;
        $bestScore = -1.0;

        foreach ($vocabList as $candidate) {
            $candLen = strlen($candidate);
            if ($candLen < 3 || $candLen > 30 || abs($candLen - $len) > $maxDist) {
                continue;
            }

            $dist = levenshtein($token, $candidate);
            if ($dist > $maxDist) {
                continue;
            }

            $pct = 0.0;
            similar_text($token, $candidate, $pct);
            $sameFirst = $token[0] === $candidate[0];

            if ($pct < 68.0 || (! $sameFirst && $dist > 1 && $pct < 78.0)) {
                continue;
            }

            $score = $pct - $dist * 12.0 + ($sameFirst ? 10.0 : 0.0);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestCandidate = $candidate;
            }
        }

        return $bestCandidate;
    }
}
