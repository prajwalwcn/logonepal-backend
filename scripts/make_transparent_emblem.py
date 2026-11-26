"""Convert solid-background illustrated emblem PNGs into crisp transparent RGBA PNGs.

Uses 4-corner + border BFS flood-fill so interior white highlights (clouds,
wave foam, airplane fuselages, eyes) stay completely opaque while the outer
backdrop is cleanly knocked out with smooth anti-aliased edges.
"""

from collections import deque
from pathlib import Path
import sys
from PIL import Image


def is_bg(r: int, g: int, b: int, bg_rgb: tuple[int, int, int], tol: int) -> bool:
    return (
        abs(r - bg_rgb[0]) <= tol
        and abs(g - bg_rgb[1]) <= tol
        and abs(b - bg_rgb[2]) <= tol
    )


def process_image(src_path: Path, dst_path: Path, size: int = 512, tol: int = 34, remove_holes: bool = False) -> None:
    img = Image.open(src_path).convert("RGBA")
    w, h = img.size
    px = img.load()

    # Sample the 4 corners to detect the backdrop color (usually ~255,255,255).
    corners = [px[2, 2][:3], px[w - 3, 2][:3], px[2, h - 3][:3], px[w - 3, h - 3][:3]]
    bg_rgb = (
        sum(c[0] for c in corners) // 4,
        sum(c[1] for c in corners) // 4,
        sum(c[2] for c in corners) // 4,
    )

    visited = bytearray(w * h)
    q: deque[tuple[int, int]] = deque()

    # Seed from entire outer border
    for x in range(w):
        for y in (0, h - 1):
            r, g, b, _ = px[x, y]
            if is_bg(r, g, b, bg_rgb, tol):
                idx = y * w + x
                if not visited[idx]:
                    visited[idx] = 1
                    q.append((x, y))
    for y in range(h):
        for x in (0, w - 1):
            r, g, b, _ = px[x, y]
            if is_bg(r, g, b, bg_rgb, tol):
                idx = y * w + x
                if not visited[idx]:
                    visited[idx] = 1
                    q.append((x, y))

    while q:
        x, y = q.popleft()
        px[x, y] = (0, 0, 0, 0)
        for nx, ny in ((x - 1, y), (x + 1, y), (x, y - 1), (x, y + 1)):
            if 0 <= nx < w and 0 <= ny < h:
                idx = ny * w + nx
                if not visited[idx]:
                    r, g, b, _ = px[nx, ny]
                    if is_bg(r, g, b, bg_rgb, tol):
                        visited[idx] = 1
                        q.append((nx, ny))

    if remove_holes:
        # Also remove enclosed background-colored holes (e.g. inside metallic rings on pure black)
        hole_tol = max(14, tol - 12)
        for y in range(h):
            for x in range(w):
                r, g, b, a = px[x, y]
                if a > 0 and is_bg(r, g, b, bg_rgb, hole_tol):
                    px[x, y] = (0, 0, 0, 0)

    # Anti-alias 1-pixel fringe adjacent to transparent background to remove white halo
    fringe_tol = tol + 46
    for y in range(1, h - 1):
        for x in range(1, w - 1):
            r, g, b, a = px[x, y]
            if a == 0:
                continue
            if (
                px[x - 1, y][3] == 0
                or px[x + 1, y][3] == 0
                or px[x, y - 1][3] == 0
                or px[x, y + 1][3] == 0
            ):
                dist = max(abs(r - bg_rgb[0]), abs(g - bg_rgb[1]), abs(b - bg_rgb[2]))
                if dist < fringe_tol:
                    new_a = int(max(0, min(255, (dist - tol * 0.5) / (fringe_tol - tol * 0.5) * 255)))
                    px[x, y] = (r, g, b, new_a)

    # Tightly crop to non-transparent bounding box with 4% square padding
    bbox = img.getbbox()
    if bbox:
        img = img.crop(bbox)

    cw, ch = img.size
    side = int(max(cw, ch) * 1.08)
    canvas = Image.new("RGBA", (side, side), (0, 0, 0, 0))
    canvas.paste(img, ((side - cw) // 2, (side - ch) // 2), img)
    canvas = canvas.resize((size, size), Image.Resampling.LANCZOS)
    dst_path.parent.mkdir(parents=True, exist_ok=True)
    canvas.save(dst_path, format="PNG", optimize=True)
    print(f"OK: {src_path.name} -> {dst_path.name} ({dst_path.stat().st_size // 1024} KB)")


if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Usage: python make_transparent_emblem.py <src.png> <dst.png> [--remove-holes]")
        sys.exit(1)
    process_image(Path(sys.argv[1]), Path(sys.argv[2]), remove_holes="--remove-holes" in sys.argv[3:])
