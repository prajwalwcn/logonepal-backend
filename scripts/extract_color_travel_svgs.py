"""Extract curated full-color travel, mountain, climbing, Nepal/temple, globe,
aviation, cruise, and adventure SVGs from MIT (@iconify-json/fluent-emoji-flat)
and Apache 2.0 (@iconify-json/noto) packs into Logoforge's svg-library/travel.
"""

import json
from pathlib import Path

PACK_ROOT = Path(
    r"C:\Users\prajw\.gemini\antigravity\brain\7aa6d5e6-fe78-489c-8973-3f7924108616\scratch\color-packs\node_modules"
)
OUT_DIR = Path(r"c:\Projects\logo-forge\api\storage\app\svg-library\travel")
OUT_DIR.mkdir(parents=True, exist_ok=True)

# Map (icon_name -> clean travel/nepal/mountain/climber/world slug)
CURATED = {
    "snow-capped-mountain": "mountain-snow-peak-himalaya",
    "mountain": "mountain-hill-peak-trek",
    "mount-fuji": "mountain-everest-snow-crest",
    "sunrise-over-mountains": "mountain-sunrise-hills-nepal",
    "national-park": "hills-valley-national-park-trek",
    "camping": "camp-tent-mountain-trek",
    "person-climbing": "climber-man-climbing-mountain",
    "man-climbing": "climber-hiker-man-climbing",
    "woman-climbing": "climber-trekker-climbing-peak",
    "backpack": "travel-backpack-trekker-himalaya",
    "hiking-boot": "travel-hiking-boot-trek-hills",
    "compass": "travel-world-compass-navigator",
    "world-map": "world-map-global-travel",
    "globe-showing-asia-australia": "world-globe-asia-nepal-travel",
    "globe-showing-europe-africa": "world-globe-europe-africa-travel",
    "globe-showing-americas": "world-globe-americas-travel",
    "globe-with-meridians": "world-globe-meridians-tours",
    "airplane": "travel-airplane-flight-jet",
    "airplane-departure": "travel-airplane-departure-flight",
    "airplane-arrival": "travel-airplane-arrival-flight",
    "small-airplane": "travel-small-airplane-hills",
    "parachute": "travel-paragliding-sky-adventure",
    "passenger-ship": "travel-cruise-ship-ocean",
    "sailboat": "travel-sailboat-lake-pokhara",
    "speedboat": "travel-speedboat-island-tour",
    "ferry": "travel-ferry-boat-tours",
    "desert-island": "travel-tropical-island-palm",
    "beach-with-umbrella": "travel-beach-resort-umbrella",
    "palm-tree": "travel-palm-tree-beach",
    "sunrise": "travel-sunrise-horizon-ocean",
    "sunset": "travel-sunset-cityscape-tours",
    "cityscape": "travel-cityscape-world-tours",
    "hindu-temple": "nepal-temple-heritage-travel",
    "shinto-shrine": "nepal-pagoda-gate-heritage",
    "classical-building": "world-monument-heritage-tours",
    "statue-of-liberty": "world-landmark-liberty-tours",
    "tokyo-tower": "world-tower-landmark-tours",
    "luggage": "travel-luggage-suitcase-tours",
    "mountain-cableway": "nepal-mountain-cableway-hills",
    "aerial-tramway": "mountain-aerial-tramway-hills",
    "mountain-railway": "mountain-railway-hills-train",
    "high-speed-train": "world-express-train-travel",
    "locomotive": "travel-scenic-train-tours",
    "bus": "travel-tour-bus-roadtrip",
    "minibus": "travel-adventure-van-hills",
    "sport-utility-vehicle": "travel-4x4-mountain-safari",
    "tent": "camp-tent-hills-adventure",
    "evergreen-tree": "hills-pine-forest-mountain",
    "deciduous-tree": "hills-green-nature-travel",
    "volcano": "mountain-volcano-adventure-peak",
    "bridge-at-night": "world-bridge-night-tours",
    "camera": "travel-photography-tours",
    "map-of-japan": "world-island-map-travel",
    "hotel": "travel-hotel-resort-stay",
    "castle": "world-castle-heritage-tours",
    "canoe": "travel-rafting-river-nepal",
    "anchor": "travel-maritime-cruise-anchor",
    "sun-behind-small-cloud": "travel-sunny-sky-holiday",
}


def extract_pack(pkg: str, prefix: str) -> int:
    json_path = PACK_ROOT / "@iconify-json" / pkg / "icons.json"
    if not json_path.is_file():
        print(f"Missing {json_path}")
        return 0
    data = json.loads(json_path.read_text(encoding="utf-8"))
    icons = data.get("icons", {})
    default_w = data.get("width", 32)
    default_h = data.get("height", 32)
    saved = 0
    for icon_name, slug_base in CURATED.items():
        entry = icons.get(icon_name)
        if not entry or "body" not in entry:
            continue
        w = entry.get("width", default_w)
        h = entry.get("height", default_h)
        svg = (
            f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w} {h}" '
            f'width="512" height="512">{entry["body"]}</svg>'
        )
        out_file = OUT_DIR / f"{prefix}-{slug_base}.svg"
        out_file.write_text(svg, encoding="utf-8")
        saved += 1
    return saved


def main() -> None:
    n1 = extract_pack("fluent-emoji-flat", "fluent")
    n2 = extract_pack("noto", "noto")
    print(f"Extracted {n1} Fluent Emoji Flat + {n2} Noto Color = {n1 + n2} full-color travel SVGs")


if __name__ == "__main__":
    main()
