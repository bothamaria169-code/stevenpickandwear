"""
Steven Picknwear: one-off catalog builder.

1. Sorts the WhatsApp product photos into product-images/<category>/<type>/.
   Exact duplicate photos go to product-images/_duplicates/.
2. Writes web-sized copies to assets/img/products/ (deployed with the code).
3. Writes database/seed-products.sql (categories + products + sizes).

Run from the project root:  python scripts/build-catalog.py
Re-running is safe: it reads photos from the project root the first time,
and after that from the sorted folders (via product-images/_catalog-map.json).
"""
import glob
import hashlib
import json
import os
import re
import shutil

from PIL import Image, ImageOps

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
os.chdir(ROOT)
MAP_FILE = "product-images/_catalog-map.json"

# (category, type, name) in the order of the de-duplicated photo list.
# Women = jeans/denim, suits, dresses. Men = suits, casual. Kids = none yet.
W, M = "women", "men"
CATALOG = [
    (W, "jeans", "Light Wash Wide-Leg Jeans"),
    (W, "jeans", "Dark Wash Flare Jeans"),
    (W, "jeans", "Indigo Wide-Leg Jeans"),
    (W, "jeans", "Mid Wash Barrel-Leg Jeans"),
    (W, "skirts", "Light Denim Midi Skirt with Front Slit"),
    (W, "dresses", "Denim Button-Up Mini Dress"),
    (W, "jeans", "Faded Wide-Leg Jeans"),
    (W, "jeans", "Dark Indigo Flare Jeans"),
    (W, "jeans", "Classic Barrel-Leg Jeans"),
    (W, "jeans", "Mid Wash Straight-Leg Jeans"),
    (W, "jeans", "Ripped Knee Straight Jeans"),
    (W, "jeans", "Mid Wash Flare Jeans"),
    (W, "jeans", "Dark Rinse Straight Jeans"),
    (W, "jeans", "Light Wash Straight-Leg Jeans"),
    (W, "jeans", "Vintage Wash Palazzo Jeans"),
    (W, "jeans", "Classic Blue Wide-Leg Jeans"),
    (W, "jeans", "Double Denim Shirt & Wide-Leg Jeans"),
    (W, "jeans", "Ice Wash Straight Jeans"),
    (W, "jeans", "Deep Blue Straight Jeans"),
    (W, "jeans", "Light Wash Relaxed Jeans"),
    (W, "jeans", "Pale Blue Straight Jeans"),
    (W, "jeans", "Relaxed Mid Wash Jeans"),
    (W, "jeans", "Denim Waistcoat & Wide-Leg Jeans Set"),
    (W, "jeans", "Baggy Light Wash Jeans"),
    (W, "skirts", "Denim Maxi Skirt with Front Slit"),
    (W, "jeans", "Cropped Denim Jacket & Jeans Set"),
    (W, "jeans", "High-Rise Light Wide-Leg Jeans"),
    (W, "jeans", "Everyday Straight-Leg Jeans"),
    (W, "jeans", "Blue Skinny Jeans"),
    (W, "jeans", "Dark Wash Bootcut Jeans"),
    (W, "jeans", "Denim Waistcoat & Flare Jeans"),
    (W, "jeans", "Denim Jacket & Wide-Leg Jeans"),
    (W, "jeans", "Grey Wash Straight Jeans"),
    (W, "jeans", "Ice Blue High-Waist Jeans"),
    (W, "jeans", "Distressed Wide-Leg Jeans"),
    (W, "jeans", "Frayed Hem Straight Jeans"),
    (W, "jeans", "High-Rise Flare Jeans"),
    (W, "jeans", "Cuffed Straight Jeans"),
    (W, "jeans", "Light Wash High-Waist Jeans"),
    (W, "jeans", "Dark Indigo Wide-Leg Jeans"),
    (W, "jeans", "Mid Wash Wide-Leg Jeans"),
    (W, "jeans", "Light Wash Baggy Jeans"),
    (W, "jeans", "Cuffed Mid Wash Jeans"),
    (W, "jeans", "Pale Wash Wide-Leg Jeans"),
    (W, "jeans", "Dark Wash Wide-Leg Jeans"),
    (W, "skirts", "Light Blue Denim Maxi Skirt"),
    (W, "jeans", "Denim Shirt & Wide-Leg Jeans Co-ord"),
    (W, "jeans", "Deep Cuff Wide-Leg Jeans"),
    (W, "jeans", "Denim Shirt & High-Waist Jeans"),
    (W, "jeans", "Dark Wash Palazzo Jeans"),
    (W, "suits", "Ivory Wide-Leg Trouser Suit"),
    (W, "suits", "Emerald Three-Piece Suit"),
    (W, "jeans", "Distressed Boyfriend Jeans"),
    (W, "suits", "Lilac Tweed Double-Breasted Suit"),
    (W, "suits", "Camel Three-Piece Suit"),
    (W, "suits", "Beige Waistcoat & Trouser Set"),
    (W, "suits", "Mocha Three-Piece Suit"),
    (W, "suits", "Powder Blue Pantsuit"),
    (W, "suits", "Navy Lace-Trim Pantsuit"),
    (W, "suits", "Taupe Blazer Suit"),
    (W, "suits", "Latte Pantsuit"),
    (W, "suits", "Mocha Double-Breasted Pantsuit"),
    (W, "suits", "Red Longline Coat & Trouser Set"),
    (W, "suits", "Black Classic Pantsuit"),
    (W, "suits", "Butter Yellow Pantsuit"),
    (W, "suits", "Stone Belted Blazer Suit"),
    (W, "suits", "Blush Pink Pantsuit"),
    (W, "suits", "White Belted Blazer Suit"),
    (W, "suits", "Taupe Relaxed Pantsuit"),
    (W, "suits", "Lime Oversized Pantsuit"),
    (W, "suits", "Burgundy Three-Piece Suit"),
    (W, "suits", "Grey Waistcoat Suit"),
    (W, "suits", "Dove Grey Three-Piece Suit"),
    (W, "suits", "Dusty Pink Pantsuit"),
    (W, "suits", "Taupe Wide-Leg Suit"),
    (W, "suits", "Sage Pantsuit"),
    (W, "suits", "Rust Three-Piece Suit"),
    (W, "suits", "Tan Fitted Pantsuit"),
    (W, "suits", "Olive Pantsuit"),
    (W, "suits", "Vanilla Pantsuit"),
    (W, "suits", "Sand Pleated Pantsuit"),
    (W, "suits", "Sky Blue Three-Piece Suit"),
    (W, "suits", "Olive Tailored Pantsuit"),
    (W, "suits", "Caramel Short-Sleeve Suit"),
    (W, "suits", "Grey Longline Three-Piece Suit"),
    (W, "suits", "Ivory Pantsuit"),
    (W, "suits", "Oat Wrap Blazer Suit"),
    (W, "suits", "Cornflower Blue Pantsuit"),
    (W, "suits", "Cocoa Pantsuit"),
    (W, "suits", "Baby Blue Pantsuit"),
    (W, "suits", "Cream & Blush Three-Piece Suit"),
    (W, "suits", "Beige Three-Piece Suit with Tie"),
    (W, "suits", "Moss Green Pantsuit"),
    (W, "suits", "Ice Blue Double-Breasted Suit"),
    (W, "suits", "Burgundy Double-Breasted Pantsuit"),
    (W, "suits", "Teal Pleated Blazer Suit"),
    (W, "suits", "Cream Double-Breasted Suit"),
    (W, "suits", "Ivory Wide-Leg Pantsuit"),
    (W, "suits", "Slate Grey Wrap Suit"),
    (W, "suits", "Cream Relaxed Pantsuit"),
    (W, "suits", "Mocha Waistcoat Three-Piece Suit"),
    (W, "suits", "Black Sculpted Pantsuit"),
    (W, "suits", "Olive Belted Suit"),
    (W, "suits", "Rose Pink Pantsuit"),
    (W, "suits", "Taupe Three-Piece Suit"),
    (W, "suits", "Pink Pantsuit"),
    (W, "suits", "Green Pantsuit"),
    (M, "suits", "Sky Blue Two-Piece Suit"),
    (W, "suits", "Stone Pantsuit"),
    (M, "suits", "Sage Green Suit"),
    (M, "suits", "Sand Two-Piece Suit"),
    (M, "blazers", "Camel Blazer"),
    (M, "suits", "Periwinkle Suit"),
    (M, "blazers", "Cream Blazer"),
    (M, "suits", "Beige Summer Suit"),
    (M, "suits", "Stone Suit"),
    (M, "suits", "Mocha Suit"),
    (M, "blazers", "Tan Blazer"),
    (M, "casual", "Grey Half-Zip Lounge Set"),
    (M, "blazers", "Olive & Black Mandarin Jacket"),
    (M, "suits", "Light Grey Suit"),
    (M, "suits", "Silver Grey Suit"),
    (M, "suits", "Charcoal Grey Suit"),
    (M, "blazers", "Burgundy Double-Breasted Blazer"),
    (M, "suits", "Oatmeal Suit"),
    (M, "suits", "Royal Blue Double-Breasted Suit"),
    (M, "suits", "Grey Double-Breasted Suit"),
    (M, "suits", "Ivory Shawl Collar Tuxedo"),
    (M, "blazers", "Grey Double-Breasted Blazer"),
    (M, "suits", "Mint Double-Breasted Suit"),
    (M, "blazers", "Wine Double-Breasted Blazer"),
    (M, "suits", "Burgundy Double-Breasted Suit"),
    (M, "suits", "Grey Three-Piece Wedding Suit"),
    (M, "blazers", "White Double-Breasted Tuxedo Jacket"),
    (M, "suits", "Navy Three-Piece Suit"),
    (M, "suits", "Chocolate Double-Breasted Suit"),
    (M, "blazers", "Sky Blue Double-Breasted Blazer"),
    (M, "suits", "Caramel Wide-Leg Suit"),
    (M, "suits", "White Mandarin Collar Suit"),
    (M, "suits", "Brown Double-Breasted Suit"),
    (M, "suits", "Burnt Orange Three-Piece Suit"),
    (M, "suits", "Emerald Double-Breasted Suit"),
    (M, "suits", "Navy Pinstripe Suit"),
    (M, "suits", "Midnight Navy Shawl Tuxedo"),
    (M, "suits", "Charcoal Three-Piece Suit"),
    (M, "suits", "Beige Mandarin Collar Suit"),
    (M, "blazers", "Silver Double-Breasted Blazer"),
    (M, "blazers", "Forest Green Double-Breasted Blazer"),
    (M, "blazers", "Baby Blue Double-Breasted Blazer"),
    (W, "dresses", "Teal & Gold Mermaid Gown"),
    (M, "suits", "Charcoal Three-Piece Business Suit"),
    (M, "casual", "Predict The Future Oversized Tee"),
    (W, "dresses", "Gold Lattice Gown with Train"),
]

# Prices in rand (set 29 Oct 2026): every item R2,999, the Predict The Future Tee R900.
# Change individual prices in Admin > Products.
PRICES = {
    (W, "jeans"): 2999, (W, "skirts"): 2999, (W, "suits"): 2999, (W, "dresses"): 2999,
    (M, "suits"): 2999, (M, "blazers"): 2999, (M, "casual"): 2999,
}
PRICE_OVERRIDES = {"Predict The Future Oversized Tee": 900}
DESCRIPTIONS = {
    "jeans": "Premium denim with a flattering fit, easy to dress up or down. Pair it with a crisp shirt for the office or a tee for the weekend.",
    "skirts": "A statement denim skirt with a modern cut. Style it with sneakers for daytime or heels for a night out.",
    "suits": "A sharply tailored suit that works as hard as you do. Wear the full set for the boardroom, or split the pieces for a smart-casual look.",
    "dresses": "A show-stopping piece for weddings, galas and special occasions. Made to turn heads.",
    "blazers": "A tailored blazer that instantly elevates any outfit. Wear it with matching trousers or over jeans.",
    "casual": "Relaxed, comfortable everyday wear with a clean, modern look.",
}
TYPE_LABELS = {"jeans": "Jeans & Denim", "skirts": "Skirts", "suits": "Suits", "dresses": "Dresses",
               "blazers": "Blazers", "casual": "Casual"}
SIZES = ["XS", "S", "M", "L", "XL", "XXL"]
MEN_SIZES = ["S", "M", "L", "XL", "XXL"]
FEATURED = {"Emerald Three-Piece Suit", "Royal Blue Double-Breasted Suit", "Teal & Gold Mermaid Gown",
            "Denim Waistcoat & Wide-Leg Jeans Set", "Blush Pink Pantsuit", "Ivory Shawl Collar Tuxedo",
            "Burgundy Double-Breasted Suit", "Mid Wash Flare Jeans"}


def slugify(text):
    return re.sub(r"[^a-z0-9]+", "-", text.lower()).strip("-")


def sql(v):
    if v is None:
        return "NULL"
    if isinstance(v, (int, float)):
        return str(v)
    return "'" + str(v).replace("\\", "\\\\").replace("'", "''") + "'"


def main():
    if os.path.exists(MAP_FILE):
        sources = json.load(open(MAP_FILE))
    else:
        sources = []
        seen = {}
        os.makedirs("product-images/_duplicates", exist_ok=True)
        for f in sorted(glob.glob("WhatsApp Image *.jpeg")):
            h = hashlib.md5(open(f, "rb").read()).hexdigest()
            if h in seen:
                shutil.move(f, os.path.join("product-images/_duplicates", f))
                continue
            seen[h] = f
            sources.append(f)
        assert len(sources) == len(CATALOG), f"{len(sources)} photos vs {len(CATALOG)} catalog rows"

    os.makedirs("assets/img/products", exist_ok=True)
    for d in ("women", "men", "kids"):
        os.makedirs(f"product-images/{d}", exist_ok=True)
    open("product-images/kids/.gitkeep", "a").close()

    used = set()
    rows = []
    new_map = []
    for i, ((cat, typ, name), src) in enumerate(zip(CATALOG, sources), start=1):
        slug = slugify(name)
        if slug in used:
            slug = f"{slug}-{i}"
        used.add(slug)
        sku = f"SPW-{cat[0].upper()}{typ[:2].upper()}-{i:03d}"

        dest_dir = f"product-images/{cat}/{typ}"
        os.makedirs(dest_dir, exist_ok=True)
        dest = f"{dest_dir}/{i:03d}-{slug}.jpeg"
        if os.path.abspath(src) != os.path.abspath(dest):
            shutil.move(src, dest)
        new_map.append(dest)

        web = f"assets/img/products/{slug}.jpg"
        im = ImageOps.exif_transpose(Image.open(dest)).convert("RGB")
        im.thumbnail((1000, 1400), Image.LANCZOS)
        im.save(web, "JPEG", quality=84, optimize=True, progressive=True)

        price = PRICE_OVERRIDES.get(name, PRICES[(cat, typ)])
        rows.append(dict(i=i, cat=cat, typ=typ, name=name, slug=slug, sku=sku, image=web, price=price))

    json.dump(new_map, open(MAP_FILE, "w"), indent=1)

    out = ["-- Steven Picknwear catalog seed. Generated by scripts/build-catalog.py; don't edit by hand.",
           "-- Prices: R2,999 each, Predict The Future Tee R900 (change in Admin > Products).",
           "SET NAMES utf8mb4;", "SET FOREIGN_KEY_CHECKS = 0;",
           "DELETE FROM product_variants;", "DELETE FROM product_gallery;", "DELETE FROM products;",
           "SET FOREIGN_KEY_CHECKS = 1;", ""]
    for r in rows:
        tags = f"{r['typ']},{TYPE_LABELS[r['typ']].lower()},{r['cat']}"
        short = f"{TYPE_LABELS[r['typ']]} for {'her' if r['cat'] == W else 'him'}."
        out.append(
            "INSERT INTO products (category_id, name, slug, short_desc, description, type, base_price, sku, "
            "stock_status, stock_qty, featured_image, is_featured, is_one_of_a_kind, status, tags) VALUES ("
            f"(SELECT id FROM categories WHERE slug={sql(r['cat'])}), {sql(r['name'])}, {sql(r['slug'])}, "
            f"{sql(short)}, {sql(DESCRIPTIONS[r['typ']])}, 'variable', {r['price']}.00, "
            f"{sql(r['sku'])}, 'in_stock', 0, {sql(r['image'])}, {1 if r['name'] in FEATURED else 0}, 0, "
            f"'published', {sql(tags)});"
        )
        sizes = MEN_SIZES if r["cat"] == M else SIZES
        if r["typ"] == "casual" or r["typ"] == "dresses":
            sizes = SIZES
        vals = ", ".join(
            f"(@pid, {sql(s)}, 5, {sql(r['sku'] + '-' + s)})" for s in sizes
        )
        out.append("SET @pid = LAST_INSERT_ID();")
        out.append(f"INSERT INTO product_variants (product_id, size_sa, stock_qty, sku) VALUES {vals};")
    open("database/seed-products.sql", "w", encoding="utf-8").write("\n".join(out) + "\n")

    counts = {}
    for r in rows:
        counts[(r["cat"], r["typ"])] = counts.get((r["cat"], r["typ"]), 0) + 1
    for k, v in sorted(counts.items()):
        print(f"{k[0]:6} {k[1]:8} {v}")
    print("total", len(rows))


if __name__ == "__main__":
    main()
