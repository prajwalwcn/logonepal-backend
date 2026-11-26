"""Craft 36 original geometric SVG emblems across 6 major business categories
(tech, food, real-estate, luxury, fitness, finance) following logo-design skill
principles: 256x256 square grid, crisp silhouettes that read at 16px,
harmonious 2-3 colour palettes, and zero live text.
"""

from pathlib import Path

BASE = Path(r"c:\Projects\logo-forge\api\storage\app\svg-library")


def svg(body: str, defs: str = "") -> str:
    d = f"<defs>{defs}</defs>" if defs else ""
    return f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" width="256" height="256">{d}{body}</svg>'


PACKS: dict[str, list[tuple[str, str]]] = {
    "tech": [
        ("tech-prism-cube", svg('<polygon points="128,22 220,74 128,126 36,74" fill="#38BDF8"/><polygon points="36,74 128,126 128,234 36,182" fill="#1E3A8A"/><polygon points="220,74 128,126 128,234 220,182" fill="#2563EB"/>')),
        ("tech-neural-hex", svg('<polygon points="128,20 220,74 220,182 128,236 36,182 36,74" fill="#0F172A"/><circle cx="128" cy="128" r="24" fill="#38BDF8"/><circle cx="128" cy="64" r="14" fill="#818CF8"/><circle cx="182" cy="158" r="14" fill="#34D399"/><circle cx="74" cy="158" r="14" fill="#F472B6"/>')),
        ("tech-spark-bolt", svg('<circle cx="128" cy="128" r="106" fill="#1E1B4B"/><polygon points="142,46 78,138 124,138 112,212 178,118 132,118" fill="#FBBF24"/>')),
        ("tech-quantum-ring", svg('<circle cx="128" cy="128" r="98" fill="none" stroke="#4F46E5" stroke-width="22"/><circle cx="128" cy="128" r="46" fill="#06B6D4"/><circle cx="196" cy="64" r="20" fill="#EC4899"/>')),
        ("tech-cloud-pulse", svg('<path d="M74 186 H188 C212 186 230 168 230 144 C230 122 214 104 192 102 C184 68 154 44 118 44 C76 44 42 78 42 120 C28 128 22 142 22 156 C22 174 36 186 74 186 Z" fill="#2563EB"/><polygon points="128,84 156,144 100,144" fill="#FFFFFF"/>')),
        ("tech-code-shield", svg('<path d="M128 20 L218 56 L218 134 C218 188 180 222 128 240 C76 222 38 188 38 134 L38 56 Z" fill="#0F172A"/><polygon points="104,96 72,128 104,160 116,148 96,128 116,108" fill="#38BDF8"/><polygon points="152,96 184,128 152,160 140,148 160,128 140,108" fill="#A78BFA"/>')),
    ],
    "food": [
        ("food-coffee-roast-crest", svg('<circle cx="128" cy="128" r="108" fill="#451A03"/><path d="M78 108 H172 V156 C172 184 150 202 125 202 C100 202 78 184 78 156 Z" fill="#FDE68A"/><path d="M172 118 H190 C202 118 210 128 210 140 C210 152 202 162 190 162 H172" fill="none" stroke="#F59E0B" stroke-width="14"/><path d="M108 62 Q122 76 108 92 M140 58 Q154 74 140 92" fill="none" stroke="#FBBF24" stroke-width="10" stroke-linecap="round"/>')),
        ("food-golden-wheat", svg('<circle cx="128" cy="128" r="106" fill="#78350F"/><path d="M128 48 C150 72 150 104 128 128 C106 104 106 72 128 48 Z" fill="#FBBF24"/><path d="M84 102 C112 108 126 126 126 154 C98 148 84 130 84 102 Z" fill="#F59E0B"/><path d="M172 102 C144 108 130 126 130 154 C158 148 172 130 172 102 Z" fill="#F59E0B"/><rect x="122" y="128" width="12" height="82" rx="6" fill="#FDE68A"/>')),
        ("food-chef-flame", svg('<circle cx="128" cy="128" r="106" fill="#991B1B"/><path d="M128 44 C162 84 176 116 176 146 C176 178 154 202 128 202 C102 202 80 178 80 146 C80 122 96 96 112 80 C110 102 120 112 128 112 C122 86 126 62 128 44 Z" fill="#F97316"/><path d="M128 118 C144 138 150 154 150 168 C150 184 140 194 128 194 C116 194 106 184 106 168 C106 152 116 134 128 118 Z" fill="#FDE047"/>')),
        ("food-organic-leaf-bowl", svg('<circle cx="128" cy="128" r="106" fill="#064E3B"/><path d="M62 136 H194 C192 176 164 202 128 202 C92 202 64 176 62 136 Z" fill="#34D399"/><path d="M128 54 C162 66 174 96 156 124 C128 124 114 94 128 54 Z" fill="#A7F3D0"/><path d="M124 124 C96 124 82 96 96 68 C122 76 134 98 124 124 Z" fill="#10B981"/>')),
        ("food-citrus-slice", svg('<circle cx="128" cy="128" r="106" fill="#EA580C"/><circle cx="128" cy="128" r="88" fill="#FEF08A"/><path d="M128 52 A76 76 0 0 1 194 90 L128 128 Z" fill="#F97316"/><path d="M194 166 A76 76 0 0 1 128 204 L128 128 Z" fill="#F97316"/><path d="M62 90 A76 76 0 0 1 128 52 L128 128 Z" fill="#FB923C"/><path d="M128 204 A76 76 0 0 1 62 166 L128 128 Z" fill="#FB923C"/>')),
        ("food-espresso-bean", svg('<circle cx="128" cy="128" r="106" fill="#1C1917"/><ellipse cx="128" cy="128" rx="58" ry="82" transform="rotate(-24 128 128)" fill="#D97706"/><path d="M98 64 Q138 124 158 192" fill="none" stroke="#1C1917" stroke-width="14" stroke-linecap="round"/>')),
    ],
    "real-estate": [
        ("realestate-gold-roof-crest", svg('<polygon points="128,22 224,78 224,178 128,234 32,178 32,78" fill="#0F172A"/><polygon points="128,64 200,122 182,122 182,184 74,184 74,122 56,122" fill="#F59E0B"/><rect x="112" y="136" width="32" height="48" fill="#0F172A"/>')),
        ("realestate-skyline-towers", svg('<circle cx="128" cy="128" r="106" fill="#1E3A8A"/><rect x="66" y="114" width="34" height="86" fill="#93C5FD"/><rect x="110" y="64" width="38" height="136" fill="#FFFFFF"/><rect x="158" y="96" width="32" height="104" fill="#38BDF8"/>')),
        ("realestate-key-arch", svg('<circle cx="128" cy="128" r="106" fill="#064E3B"/><circle cx="128" cy="96" r="38" fill="none" stroke="#FBBF24" stroke-width="18"/><rect x="119" y="130" width="18" height="72" rx="4" fill="#FBBF24"/><rect x="137" y="164" width="24" height="14" rx="3" fill="#FBBF24"/><rect x="137" y="186" width="18" height="14" rx="3" fill="#FBBF24"/>')),
        ("realestate-modern-villa", svg('<rect x="26" y="26" width="204" height="204" rx="36" fill="#111827"/><polygon points="56,142 128,74 200,142 200,192 56,192" fill="#38BDF8"/><polygon points="96,192 128,128 160,192" fill="#0F172A"/>')),
        ("realestate-emerald-pin", svg('<path d="M128 238 C128 238 208 168 208 106 C208 62 172 26 128 26 C84 26 48 62 48 106 C48 168 128 238 128 238 Z" fill="#0F766E"/><polygon points="128,64 176,108 162,108 162,146 94,146 94,108 80,108" fill="#FFFFFF"/>')),
        ("realestate-luxury-pillars", svg('<circle cx="128" cy="128" r="106" fill="#1C1917"/><polygon points="60,92 128,50 196,92" fill="#F59E0B"/><rect x="74" y="102" width="20" height="78" fill="#FDE68A"/><rect x="118" y="102" width="20" height="78" fill="#FDE68A"/><rect x="162" y="102" width="20" height="78" fill="#FDE68A"/><rect x="60" y="184" width="136" height="14" rx="4" fill="#F59E0B"/>')),
    ],
    "luxury": [
        ("luxury-royal-crown-crest", svg('<circle cx="128" cy="128" r="108" fill="#080C24"/><polygon points="56,172 68,94 106,130 128,74 150,130 188,94 200,172" fill="#F59E0B"/><rect x="58" y="180" width="140" height="16" rx="6" fill="#FDE68A"/>')),
        ("luxury-faceted-diamond", svg('<circle cx="128" cy="128" r="108" fill="#240606"/><polygon points="82,76 174,76 206,118 128,204 50,118" fill="#FBBF24"/><polygon points="82,76 174,76 128,118" fill="#FEF08A"/><polygon points="128,118 206,118 128,204" fill="#D97706"/>')),
        ("luxury-gold-laurel-ring", svg('<circle cx="128" cy="128" r="106" fill="#111827"/><circle cx="128" cy="128" r="82" fill="none" stroke="#F59E0B" stroke-width="10"/><polygon points="128,66 178,128 128,190 78,128" fill="#FDE68A"/><polygon points="128,92 156,128 128,164 100,128" fill="#111827"/>')),
        ("luxury-monolith-shield", svg('<path d="M128 22 L214 58 L214 140 C214 190 176 222 128 238 C80 222 42 190 42 140 L42 58 Z" fill="#0F172A" stroke="#F59E0B" stroke-width="10"/><polygon points="128,64 168,128 128,192 88,128" fill="#FBBF24"/>')),
        ("luxury-starburst-seal", svg('<circle cx="128" cy="128" r="106" fill="#1E1B4B"/><polygon points="128,38 148,108 218,128 148,148 128,218 108,148 38,128 108,108" fill="#FDE047"/><circle cx="128" cy="128" r="24" fill="#1E1B4B"/>')),
        ("luxury-velvet-hex", svg('<polygon points="128,20 222,74 222,182 128,236 34,182 34,74" fill="#3B0764"/><polygon points="128,54 192,91 192,165 128,202 64,165 64,91" fill="none" stroke="#FBBF24" stroke-width="12"/><circle cx="128" cy="128" r="28" fill="#FDE68A"/>')),
    ],
    "fitness": [
        ("fitness-iron-dumbbell", svg('<circle cx="128" cy="128" r="106" fill="#111827"/><rect x="52" y="96" width="22" height="64" rx="6" fill="#F97316"/><rect x="78" y="84" width="18" height="88" rx="6" fill="#FB923C"/><rect x="96" y="118" width="64" height="20" fill="#E5E7EB"/><rect x="160" y="84" width="18" height="88" rx="6" fill="#FB923C"/><rect x="182" y="96" width="22" height="64" rx="6" fill="#F97316"/>')),
        ("fitness-apex-bolt", svg('<polygon points="128,20 220,74 220,182 128,236 36,182 36,74" fill="#DC2626"/><polygon points="146,48 82,138 124,138 110,208 176,118 134,118" fill="#FFFFFF"/>')),
        ("fitness-pulse-heart", svg('<circle cx="128" cy="128" r="106" fill="#0F172A"/><path d="M128 200 C128 200 54 152 54 102 C54 74 76 56 100 56 C114 56 124 64 128 74 C132 64 142 56 156 56 C180 56 202 74 202 102 C202 152 128 200 128 200 Z" fill="#EF4444"/><polyline points="72,126 104,126 118,98 136,150 150,126 184,126" fill="none" stroke="#FFFFFF" stroke-width="10" stroke-linecap="round" stroke-linejoin="round"/>')),
        ("fitness-sprint-wing", svg('<circle cx="128" cy="128" r="106" fill="#064E3B"/><path d="M52 158 L194 74 L164 134 L204 134 L82 194 L106 158 Z" fill="#34D399"/>')),
        ("fitness-kettle-shield", svg('<path d="M128 22 L216 58 L216 140 C216 190 176 222 128 238 C80 222 40 190 40 140 L40 58 Z" fill="#18181B"/><circle cx="128" cy="146" r="46" fill="#F59E0B"/><path d="M100 110 C100 84 156 84 156 110" fill="none" stroke="#FDE047" stroke-width="14"/>')),
        ("fitness-zen-lotus", svg('<circle cx="128" cy="128" r="106" fill="#0F766E"/><path d="M128 54 C156 90 156 138 128 176 C100 138 100 90 128 54 Z" fill="#CCFBF1"/><path d="M72 104 C104 114 122 142 122 176 C88 168 70 138 72 104 Z" fill="#5EEAD4"/><path d="M184 104 C152 114 134 142 134 176 C168 168 186 138 184 104 Z" fill="#5EEAD4"/>')),
    ],
    "finance": [
        ("finance-growth-bars", svg('<rect x="26" y="26" width="204" height="204" rx="40" fill="#0F172A"/><rect x="64" y="142" width="28" height="52" rx="6" fill="#38BDF8"/><rect x="114" y="108" width="28" height="86" rx="6" fill="#2563EB"/><rect x="164" y="66" width="28" height="128" rx="6" fill="#10B981"/>')),
        ("finance-vault-hex", svg('<polygon points="128,20 220,74 220,182 128,236 36,182 36,74" fill="#064E3B"/><circle cx="128" cy="128" r="56" fill="none" stroke="#34D399" stroke-width="16"/><circle cx="128" cy="128" r="20" fill="#FBBF24"/>')),
        ("finance-apex-arrow", svg('<circle cx="128" cy="128" r="106" fill="#1E3A8A"/><polygon points="128,54 198,136 158,136 158,196 98,196 98,136 58,136" fill="#34D399"/>')),
        ("finance-shield-trust", svg('<path d="M128 22 L216 58 L216 140 C216 190 176 222 128 238 C80 222 40 190 40 140 L40 58 Z" fill="#0F172A"/><polyline points="84,132 114,162 174,98" fill="none" stroke="#10B981" stroke-width="20" stroke-linecap="round" stroke-linejoin="round"/>')),
        ("finance-gold-coin-arc", svg('<circle cx="128" cy="128" r="106" fill="#1C1917"/><circle cx="128" cy="128" r="72" fill="none" stroke="#F59E0B" stroke-width="18"/><polygon points="128,74 166,128 128,182 90,128" fill="#FDE047"/>')),
        ("finance-ledger-prism", svg('<rect x="28" y="28" width="200" height="200" rx="34" fill="#1E1B4B"/><rect x="64" y="76" width="128" height="24" rx="12" fill="#818CF8"/><rect x="64" y="116" width="96" height="24" rx="12" fill="#38BDF8"/><rect x="64" y="156" width="64" height="24" rx="12" fill="#34D399"/>')),
    ],
}


def main() -> None:
    total = 0
    for category, items in PACKS.items():
        cat_dir = BASE / category
        cat_dir.mkdir(parents=True, exist_ok=True)
        for slug, content in items:
            (cat_dir / f"{slug}.svg").write_text(content, encoding="utf-8")
            total += 1
    print(f"Generated {total} audited geometric SVG emblems across {len(PACKS)} industries")


if __name__ == "__main__":
    main()
