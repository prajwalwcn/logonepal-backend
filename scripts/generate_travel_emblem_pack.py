"""Generate 60+ bespoke multi-layer gradient SVG travel emblems:
Nepal, Himalayas, Everest peaks, mountain climbers/trekkers, rolling hills,
Pokhara lakes, stupa & peaks, world globes, orbital jets, compasses, and crests.

All output SVGs are 100% original, commercial-safe, self-contained 512x512 vectors
with linear gradients and clean path geometry compatible with Logoforge's SvgAssetImporter.
"""

from pathlib import Path
import math

OUT_DIR = Path(r"c:\Projects\logo-forge\api\storage\app\svg-library\travel")
OUT_DIR.mkdir(parents=True, exist_ok=True)


def svg_wrap(defs: str, body: str) -> str:
    return (
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="512" height="512">'
        f"<defs>{defs}</defs>{body}</svg>"
    )


def frame_path(shape: str, fill: str, stroke: str = "#0F172A", sw: int = 12) -> str:
    if shape == "circle":
        return f'<circle cx="256" cy="256" r="218" fill="{fill}" stroke="{stroke}" stroke-width="{sw}"/>'
    if shape == "hex":
        return (
            f'<polygon points="256,34 448,145 448,367 256,478 64,367 64,145" '
            f'fill="{fill}" stroke="{stroke}" stroke-width="{sw}"/>'
        )
    if shape == "shield":
        return (
            f'<path d="M256 36 L438 104 L438 258 C438 366 362 436 256 476 '
            f'C150 436 74 366 74 258 L74 104 Z" fill="{fill}" stroke="{stroke}" stroke-width="{sw}"/>'
        )
    if shape == "diamond":
        return (
            f'<polygon points="256,30 476,256 256,482 36,256" '
            f'fill="{fill}" stroke="{stroke}" stroke-width="{sw}"/>'
        )
    if shape == "pin":
        return (
            f'<path d="M256 478 C256 478 424 334 424 206 C424 113 349 38 256 38 '
            f'C163 38 88 113 88 206 C88 334 256 478 256 478 Z" fill="{fill}" stroke="{stroke}" stroke-width="{sw}"/>'
        )
    return f'<circle cx="256" cy="256" r="218" fill="{fill}" stroke="{stroke}" stroke-width="{sw}"/>'


def sky_grad(gid: str, c1: str, c2: str, c3: str = "") -> str:
    if c3:
        return (
            f'<linearGradient id="{gid}" x1="0%" y1="0%" x2="0%" y2="100%">'
            f'<stop offset="0%" stop-color="{c1}"/>'
            f'<stop offset="55%" stop-color="{c2}"/>'
            f'<stop offset="100%" stop-color="{c3}"/></linearGradient>'
        )
    return (
        f'<linearGradient id="{gid}" x1="0%" y1="0%" x2="100%" y2="100%">'
        f'<stop offset="0%" stop-color="{c1}"/>'
        f'<stop offset="100%" stop-color="{c2}"/></linearGradient>'
    )


# Reusable vector scene components (pure paths/circles/polygons — zero clipPath dependency)
SUN_HIGH = '<circle cx="256" cy="176" r="56" fill="#FDE047"/>'
SUN_CORAL = '<circle cx="256" cy="186" r="62" fill="#FB923C"/>'
SUN_GOLD = '<circle cx="312" cy="164" r="46" fill="#FBBF24"/>'
MOON_STARS = (
    '<path d="M322 132 A38 38 0 1 0 356 184 A30 30 0 1 1 322 132 Z" fill="#FEF08A"/>'
    '<polygon points="172,140 177,152 189,154 179,162 182,174 172,167 162,174 165,162 155,154 167,152" fill="#FDE68A"/>'
    '<polygon points="222,108 225,116 233,117 227,122 229,130 222,126 215,130 217,122 211,117 219,116" fill="#FEF3C7"/>'
)

EVEREST_PEAKS = (
    '<polygon points="78,342 182,196 276,342" fill="#1E293B"/>'
    '<polygon points="182,196 154,236 174,230 186,244 206,232" fill="#F8FAFC"/>'
    '<polygon points="154,358 266,144 378,358" fill="#0F172A"/>'
    '<polygon points="266,144 224,224 248,214 266,234 286,214 308,224" fill="#FFFFFF"/>'
    '<polygon points="262,358 354,202 436,358" fill="#334155"/>'
    '<polygon points="354,202 328,246 346,240 358,252 378,242" fill="#E2E8F0"/>'
)

HIMALAYA_RIDGE = (
    '<polygon points="68,354 156,214 236,354" fill="#312E81"/>'
    '<polygon points="156,214 134,250 152,244 164,256 178,248" fill="#EEF2FF"/>'
    '<polygon points="178,358 278,158 378,358" fill="#1E1B4B"/>'
    '<polygon points="278,158 242,230 264,220 278,238 296,220 314,230" fill="#FFFFFF"/>'
    '<polygon points="296,354 376,226 446,354" fill="#3730A3"/>'
    '<polygon points="376,226 356,258 372,252 384,262 396,256" fill="#E0E7FF"/>'
)

ROLLING_HILLS = (
    '<path d="M68 320 Q168 232 288 312 T446 300 L446 382 L68 382 Z" fill="#15803D"/>'
    '<path d="M68 344 Q188 266 320 338 T446 330 L446 396 L68 396 Z" fill="#166534"/>'
    '<path d="M196 396 Q246 342 262 316 Q278 342 324 396 Z" fill="#FDE68A"/>'
    '<path d="M254 396 L262 322 L270 396 Z" fill="#F59E0B"/>'
)

TERRACED_HILLS_NEPAL = (
    '<path d="M72 310 Q180 228 296 294 T440 286 L440 374 L72 374 Z" fill="#22C55E"/>'
    '<path d="M72 332 Q196 262 324 326 T440 318 L440 386 L72 386 Z" fill="#16A34A"/>'
    '<path d="M72 356 Q210 296 348 352 T440 348 L440 402 L72 402 Z" fill="#15803D"/>'
)

CLIMBER_SILHOUETTE = (
    # Stylized mountain climber with backpack & trekking pole on ridge
    '<circle cx="254" cy="196" r="18" fill="#F97316"/>'
    '<path d="M236 220 C236 212 266 210 272 224 L286 266 L264 274 L256 248 L244 282 L222 276 Z" fill="#0F172A"/>'
    '<path d="M222 222 L238 220 L236 254 L218 250 Z" fill="#DC2626"/>'
    '<path d="M244 278 L226 324 L242 328 L258 286 Z" fill="#1E293B"/>'
    '<path d="M262 270 L288 318 L272 326 L252 282 Z" fill="#1E293B"/>'
    '<path d="M276 232 L314 214 L318 222 L280 242 Z" fill="#0F172A"/>'
    '<path d="M312 204 L322 334 L316 334 L306 204 Z" fill="#FBBF24"/>'
)

NEPAL_STUPA = (
    # Boudhanath / Swayambhunath inspired golden stupa spire + white dome
    '<path d="M168 358 A88 80 0 0 1 344 358 Z" fill="#F8FAFC"/>'
    '<rect x="228" y="254" width="56" height="32" rx="4" fill="#F59E0B"/>'
    '<circle cx="244" cy="270" r="5" fill="#0F172A"/>'
    '<circle cx="268" cy="270" r="5" fill="#0F172A"/>'
    '<polygon points="256,164 282,254 230,254" fill="#FBBF24"/>'
    '<circle cx="256" cy="156" r="9" fill="#FEF08A"/>'
    # Prayer flag strands
    '<path d="M256 178 Q170 230 96 270" fill="none" stroke="#EF4444" stroke-width="4"/>'
    '<path d="M256 178 Q342 230 416 270" fill="none" stroke="#3B82F6" stroke-width="4"/>'
)

PINE_TREES = (
    '<polygon points="130,318 152,374 108,374" fill="#064E3B"/>'
    '<polygon points="130,294 148,342 112,342" fill="#065F46"/>'
    '<polygon points="382,314 406,374 358,374" fill="#064E3B"/>'
    '<polygon points="382,290 400,340 364,340" fill="#065F46"/>'
    '<polygon points="168,334 186,380 150,380" fill="#047857"/>'
)

JET_PLANE_DIAG = (
    '<path d="M356 138 C364 134 372 140 366 148 L314 202 L344 276 L326 286 L284 226 '
    'L234 268 L242 296 L228 304 L210 278 L184 260 L192 246 L220 254 L262 204 '
    'L202 162 L212 144 L286 174 Z" fill="#FFFFFF"/>'
)

JET_PLANE_GOLD = (
    '<path d="M362 134 C370 130 378 136 372 144 L318 200 L348 274 L330 284 L288 224 '
    'L238 266 L246 294 L232 302 L214 276 L188 258 L196 244 L224 252 L266 202 '
    'L206 160 L216 142 L290 172 Z" fill="#FBBF24"/>'
)

LAKE_BOAT = (
    '<path d="M84 356 Q170 336 256 356 T428 356 L428 402 L84 402 Z" fill="#0284C7"/>'
    '<path d="M110 376 Q186 364 256 376 T402 376" fill="none" stroke="#BAE6FD" stroke-width="6"/>'
    '<path d="M196 352 L316 352 L296 374 L216 374 Z" fill="#F97316"/>'
    '<polygon points="256,288 256,348 214,348" fill="#FFFFFF"/>'
    '<polygon points="262,300 262,348 296,348" fill="#FDE047"/>'
)

HOT_AIR_BALLOON = (
    '<path d="M256 106 C322 106 366 154 358 218 C352 268 310 308 284 334 L228 334 '
    'C202 308 160 268 154 218 C146 154 190 106 256 106 Z" fill="#EF4444"/>'
    '<path d="M256 106 C288 106 308 154 302 218 C298 268 278 308 268 334 L244 334 '
    'C234 308 214 268 210 218 C204 154 224 106 256 106 Z" fill="#FBBF24"/>'
    '<rect x="234" y="352" width="44" height="32" rx="6" fill="#78350F"/>'
    '<line x1="238" y1="334" x2="240" y2="352" stroke="#FDE68A" stroke-width="4"/>'
    '<line x1="274" y1="334" x2="272" y2="352" stroke="#FDE68A" stroke-width="4"/>'
)

WORLD_GLOBE_RING = (
    '<circle cx="256" cy="256" r="144" fill="none" stroke="#38BDF8" stroke-width="14"/>'
    '<ellipse cx="256" cy="256" rx="76" ry="144" fill="none" stroke="#38BDF8" stroke-width="10"/>'
    '<line x1="112" y1="256" x2="400" y2="256" stroke="#38BDF8" stroke-width="10"/>'
    '<path d="M132 194 Q256 226 380 194" fill="none" stroke="#38BDF8" stroke-width="9"/>'
    '<path d="M132 318 Q256 286 380 318" fill="none" stroke="#38BDF8" stroke-width="9"/>'
    '<path d="M84 328 Q256 420 432 184" fill="none" stroke="#FBBF24" stroke-width="16"/>'
)

COMPASS_STAR = (
    '<circle cx="256" cy="256" r="168" fill="none" stroke="#FBBF24" stroke-width="12"/>'
    '<circle cx="256" cy="256" r="138" fill="none" stroke="#94A3B8" stroke-width="5"/>'
    '<polygon points="256,82 288,224 256,256 224,224" fill="#F59E0B"/>'
    '<polygon points="256,430 288,288 256,256 224,288" fill="#E2E8F0"/>'
    '<polygon points="430,256 288,224 256,256 288,288" fill="#FBBF24"/>'
    '<polygon points="82,256 224,224 256,256 224,288" fill="#CBD5E1"/>'
    '<circle cx="256" cy="256" r="22" fill="#0F172A" stroke="#FDE047" stroke-width="6"/>'
)

PARAGLIDER = (
    '<path d="M126 176 Q256 98 386 176 Q256 144 126 176 Z" fill="#EC4899"/>'
    '<path d="M180 162 Q256 116 332 162 Q256 142 180 162 Z" fill="#FBBF24"/>'
    '<line x1="156" y1="170" x2="250" y2="262" stroke="#1E293B" stroke-width="3"/>'
    '<line x1="356" y1="170" x2="262" y2="262" stroke="#1E293B" stroke-width="3"/>'
    '<circle cx="256" cy="258" r="10" fill="#0F172A"/>'
    '<path d="M244 270 L268 270 L264 300 L248 300 Z" fill="#0F172A"/>'
)

TENT_CAMP = (
    '<polygon points="256,246 364,376 148,376" fill="#F97316"/>'
    '<polygon points="256,246 312,376 256,376" fill="#EA580C"/>'
    '<polygon points="256,304 288,376 224,376" fill="#1E293B"/>'
)


RECIPES = [
    # 1-12: Nepal & Himalaya Mountain Peaks
    ("nepal-everest-sunrise-circle", "circle", ("#0EA5E9", "#F97316", "#FDE047"), "#0F172A", SUN_HIGH + EVEREST_PEAKS + PINE_TREES),
    ("nepal-everest-sunrise-shield", "shield", ("#1E3A8A", "#EA580C", "#FBBF24"), "#1E1B4B", SUN_CORAL + EVEREST_PEAKS),
    ("nepal-everest-sunrise-hex", "hex", ("#0284C7", "#38BDF8", "#FEF08A"), "#0F172A", SUN_GOLD + EVEREST_PEAKS + PINE_TREES),
    ("nepal-himalaya-night-circle", "circle", ("#0F172A", "#1E1B4B", "#312E81"), "#F59E0B", MOON_STARS + HIMALAYA_RIDGE),
    ("nepal-himalaya-night-shield", "shield", ("#0B1120", "#1E293B", "#334155"), "#FBBF24", MOON_STARS + EVEREST_PEAKS),
    ("nepal-stupa-himalaya-circle", "circle", ("#0284C7", "#F59E0B", "#FEF3C7"), "#0F172A", EVEREST_PEAKS + NEPAL_STUPA),
    ("nepal-stupa-everest-shield", "shield", ("#7C2D12", "#EA580C", "#FDE047"), "#431407", HIMALAYA_RIDGE + NEPAL_STUPA),
    ("nepal-stupa-sunset-hex", "hex", ("#BE123C", "#F97316", "#FDE047"), "#1E1B4B", SUN_HIGH + NEPAL_STUPA),
    ("nepal-pokhara-lake-boat", "circle", ("#0EA5E9", "#38BDF8", "#FDE68A"), "#0F172A", SUN_GOLD + EVEREST_PEAKS + LAKE_BOAT),
    ("nepal-pokhara-hills-boat", "hex", ("#0369A1", "#0EA5E9", "#BAE6FD"), "#0C4A6E", SUN_HIGH + ROLLING_HILLS + LAKE_BOAT),
    ("nepal-annapurna-gold-diamond", "diamond", ("#1E1B4B", "#4338CA", "#F59E0B"), "#0F172A", SUN_GOLD + HIMALAYA_RIDGE),
    ("nepal-himalaya-pin-peaks", "pin", ("#0F766E", "#14B8A6", "#FDE047"), "#0F172A", SUN_HIGH + EVEREST_PEAKS),

    # 13-24: Man Climbing / Trekker / Mountaineer
    ("climber-everest-ridge-circle", "circle", ("#0284C7", "#38BDF8", "#FDE68A"), "#0F172A", SUN_GOLD + EVEREST_PEAKS + CLIMBER_SILHOUETTE),
    ("climber-himalaya-sunset-shield", "shield", ("#9A3412", "#F97316", "#FDE047"), "#1C1917", SUN_CORAL + HIMALAYA_RIDGE + CLIMBER_SILHOUETTE),
    ("climber-mountain-peak-hex", "hex", ("#1E3A8A", "#2563EB", "#93C5FD"), "#0F172A", SUN_HIGH + EVEREST_PEAKS + CLIMBER_SILHOUETTE),
    ("climber-trek-hills-circle", "circle", ("#047857", "#10B981", "#A7F3D0"), "#064E3B", SUN_GOLD + ROLLING_HILLS + CLIMBER_SILHOUETTE),
    ("climber-summit-night-shield", "shield", ("#0F172A", "#1E293B", "#475569"), "#F59E0B", MOON_STARS + EVEREST_PEAKS + CLIMBER_SILHOUETTE),
    ("climber-nepal-adventure-pin", "pin", ("#EA580C", "#F59E0B", "#FEF08A"), "#1C1917", EVEREST_PEAKS + CLIMBER_SILHOUETTE),
    ("climber-alpine-diamond", "diamond", ("#0F172A", "#0284C7", "#7DD3FC"), "#0F172A", SUN_HIGH + HIMALAYA_RIDGE + CLIMBER_SILHOUETTE),
    ("trekker-camp-mountain-circle", "circle", ("#1E293B", "#0F766E", "#FDE68A"), "#0F172A", MOON_STARS + EVEREST_PEAKS + TENT_CAMP),
    ("trekker-camp-sunset-hex", "hex", ("#B45309", "#F97316", "#FDE047"), "#451A03", SUN_CORAL + EVEREST_PEAKS + TENT_CAMP + PINE_TREES),
    ("trekker-camp-hills-shield", "shield", ("#065F46", "#10B981", "#FEF08A"), "#064E3B", SUN_HIGH + ROLLING_HILLS + TENT_CAMP),
    ("paragliding-pokhara-hills", "circle", ("#0284C7", "#38BDF8", "#E0F2FE"), "#0C4A6E", SUN_GOLD + ROLLING_HILLS + PARAGLIDER),
    ("paragliding-himalaya-peaks", "hex", ("#1D4ED8", "#60A5FA", "#FEF3C7"), "#1E3A8A", EVEREST_PEAKS + PARAGLIDER),

    # 25-36: Green Hills, Terraces, Roads & Valleys
    ("hills-green-road-circle", "circle", ("#0EA5E9", "#38BDF8", "#FEF08A"), "#064E3B", SUN_HIGH + ROLLING_HILLS + PINE_TREES),
    ("hills-terrace-nepal-circle", "circle", ("#0284C7", "#7DD3FC", "#FEF9C3"), "#14532D", SUN_GOLD + TERRACED_HILLS_NEPAL + PINE_TREES),
    ("hills-sunset-valley-shield", "shield", ("#C2410C", "#FB923C", "#FEF08A"), "#431407", SUN_CORAL + ROLLING_HILLS),
    ("hills-alpine-pine-hex", "hex", ("#0F766E", "#2DD4BF", "#CCFBF1"), "#134E4A", SUN_GOLD + TERRACED_HILLS_NEPAL + PINE_TREES),
    ("hills-roadtrip-adventure-pin", "pin", ("#0369A1", "#38BDF8", "#FDE047"), "#0F172A", SUN_HIGH + ROLLING_HILLS),
    ("hills-everest-valley-circle", "circle", ("#1D4ED8", "#38BDF8", "#FDE68A"), "#0F172A", EVEREST_PEAKS + TERRACED_HILLS_NEPAL),
    ("hills-balloon-valley-circle", "circle", ("#0EA5E9", "#7DD3FC", "#FEF08A"), "#0C4A6E", ROLLING_HILLS + HOT_AIR_BALLOON),
    ("hills-balloon-sunset-hex", "hex", ("#BE123C", "#FB7185", "#FDE047"), "#4C0519", TERRACED_HILLS_NEPAL + HOT_AIR_BALLOON),
    ("hills-misty-mountain-diamond", "diamond", ("#064E3B", "#059669", "#A7F3D0"), "#022C22", SUN_HIGH + EVEREST_PEAKS + PINE_TREES),
    ("hills-emerald-crest-shield", "shield", ("#14532D", "#22C55E", "#DCFCE7"), "#052E16", SUN_GOLD + TERRACED_HILLS_NEPAL + PINE_TREES),
    ("hills-sunrise-camp-circle", "circle", ("#EA580C", "#FBBF24", "#FEF9C3"), "#431407", SUN_HIGH + ROLLING_HILLS + TENT_CAMP),
    ("hills-golden-horizon-hex", "hex", ("#7C2D12", "#F59E0B", "#FEF08A"), "#451A03", SUN_CORAL + TERRACED_HILLS_NEPAL),

    # 37-48: World Globes, Flights, Aviation & Luxury Travel
    ("world-globe-gold-jet-circle", "circle", ("#080C24", "#0F172A", "#1E293B"), "#FBBF24", WORLD_GLOBE_RING + JET_PLANE_GOLD),
    ("world-globe-ocean-jet-circle", "circle", ("#0C4A6E", "#0369A1", "#0284C7"), "#38BDF8", WORLD_GLOBE_RING + JET_PLANE_DIAG),
    ("world-globe-burgundy-gold", "circle", ("#240606", "#450A0A", "#7F1D1D"), "#FBBF24", WORLD_GLOBE_RING + JET_PLANE_GOLD),
    ("world-globe-royal-shield", "shield", ("#1E1B4B", "#312E81", "#4338CA"), "#FDE047", WORLD_GLOBE_RING + JET_PLANE_GOLD),
    ("world-globe-emerald-hex", "hex", ("#022C22", "#064E3B", "#047857"), "#34D399", WORLD_GLOBE_RING + JET_PLANE_DIAG),
    ("world-flight-mountain-circle", "circle", ("#0F172A", "#1E3A8A", "#38BDF8"), "#F59E0B", EVEREST_PEAKS + JET_PLANE_GOLD),
    ("world-flight-sunset-hex", "hex", ("#7C2D12", "#EA580C", "#FDE047"), "#1C1917", SUN_HIGH + EVEREST_PEAKS + JET_PLANE_DIAG),
    ("world-flight-nepal-shield", "shield", ("#1E1B4B", "#DC2626", "#FBBF24"), "#0F172A", HIMALAYA_RIDGE + JET_PLANE_DIAG),
    ("world-compass-luxury-circle", "circle", ("#080C24", "#111827", "#1E293B"), "#F59E0B", COMPASS_STAR),
    ("world-compass-burgundy-shield", "shield", ("#240606", "#450A0A", "#1C1917"), "#FBBF24", COMPASS_STAR),
    ("world-compass-ocean-hex", "hex", ("#06202B", "#0C4A6E", "#0369A1"), "#38BDF8", COMPASS_STAR),
    ("world-compass-mountain-circle", "circle", ("#0F172A", "#1E293B", "#0F766E"), "#FBBF24", EVEREST_PEAKS + COMPASS_STAR),

    # 49-60: Additional Adventure, Balloon, Cruise & Scenic Badges
    ("adventure-balloon-everest-circle", "circle", ("#0284C7", "#38BDF8", "#FEF08A"), "#0F172A", EVEREST_PEAKS + HOT_AIR_BALLOON),
    ("adventure-balloon-night-shield", "shield", ("#0F172A", "#312E81", "#6366F1"), "#FBBF24", MOON_STARS + HOT_AIR_BALLOON),
    ("adventure-sail-mountain-circle", "circle", ("#0C4A6E", "#0EA5E9", "#BAE6FD"), "#0F172A", EVEREST_PEAKS + LAKE_BOAT),
    ("adventure-sail-sunset-hex", "hex", ("#9A3412", "#F97316", "#FDE047"), "#1C1917", SUN_CORAL + LAKE_BOAT),
    ("adventure-pin-himalaya-jet", "pin", ("#1E3A8A", "#2563EB", "#93C5FD"), "#0F172A", EVEREST_PEAKS + JET_PLANE_GOLD),
    ("adventure-pin-compass-world", "pin", ("#080C24", "#1E293B", "#334155"), "#FBBF24", COMPASS_STAR),
    ("adventure-diamond-everest-gold", "diamond", ("#080C24", "#1E1B4B", "#312E81"), "#FBBF24", SUN_GOLD + EVEREST_PEAKS),
    ("adventure-diamond-climber-orange", "diamond", ("#7C2D12", "#EA580C", "#FDE047"), "#1C1917", EVEREST_PEAKS + CLIMBER_SILHOUETTE),
    ("adventure-crest-nepal-treks", "shield", ("#064E3B", "#047857", "#34D399"), "#022C22", EVEREST_PEAKS + PINE_TREES + TENT_CAMP),
    ("adventure-crest-world-tours", "hex", ("#1E1B4B", "#4F46E5", "#A5B4FC"), "#0F172A", WORLD_GLOBE_RING + JET_PLANE_DIAG),
    ("adventure-sunset-paraglider-pin", "pin", ("#BE123C", "#F97316", "#FDE047"), "#4C0519", EVEREST_PEAKS + PARAGLIDER),
    ("adventure-himalaya-stupa-diamond", "diamond", ("#0F172A", "#0284C7", "#FDE047"), "#F59E0B", EVEREST_PEAKS + NEPAL_STUPA),
]


def main() -> None:
    count = 0
    for idx, (slug, shape, colors, stroke, scene) in enumerate(RECIPES, start=1):
        gid = f"sky{idx}"
        defs = sky_grad(gid, colors[0], colors[1], colors[2] if len(colors) > 2 else "")
        body = frame_path(shape, f"url(#{gid})", stroke=stroke, sw=12) + scene
        svg = svg_wrap(defs, body)
        (OUT_DIR / f"{slug}.svg").write_text(svg, encoding="utf-8")
        count += 1
    print(f"Generated {count} bespoke multi-layer travel/Nepal/mountain/climber SVG emblems in {OUT_DIR}")


if __name__ == "__main__":
    main()
