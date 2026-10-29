"""
Steven Picknwear: October 2026 catalog update (one-off, safe to re-run).

1. Writes web-sized copies of the new photos to assets/img/products/ (men's shoot
   + kids range) and assets/img/office/ (workshop + shop front), and copies the
   workshop videos to assets/video/.
2. Writes database/migrations/2026-10-29-catalog-update.sql, which adds the new
   products (with gallery photos and sizes), sets the new prices and the FNB
   banking details. The SQL only adds what is missing, so it can run twice.

Run from the project root:
    python scripts/add-catalog-2026-10.py "C:/Users/Zama/Downloads/StevenPickAndWear"
"""
import os
import re
import shutil
import sys

from PIL import Image, ImageOps

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SRC = sys.argv[1] if len(sys.argv) > 1 else "C:/Users/Zama/Downloads/StevenPickAndWear"
os.chdir(ROOT)

WA = "WhatsApp Image 2026-10-29 at "
KIDS = "Kids-Clothes/"

PRICE_DEFAULT = 2999   # every men's and women's item
PRICE_KIDS = 1199
PRICE_OVERRIDES = {"predict-the-future-oversized-tee": 900}

DESCRIPTIONS = {
    "suits": "A sharply tailored suit that works as hard as you do. Wear the full set for the boardroom, or split the pieces for a smart-casual look.",
    "blazers": "A tailored blazer that instantly elevates any outfit. Wear it with matching trousers or over jeans.",
    "casual": "Relaxed, comfortable everyday wear with a clean, modern look.",
    "boys": "Traditional-inspired occasion wear for boys. Made for weddings, family celebrations and special days.",
    "girls": "A bright, twirl-ready dress for parties, weddings and special occasions.",
}
TYPE_LABELS = {"suits": "Suits", "blazers": "Blazers", "casual": "Casual", "boys": "Boys", "girls": "Girls"}
MEN_SIZES = ["S", "M", "L", "XL", "XXL"]
KIDS_SIZES = ["2-3Y", "4-5Y", "6-7Y", "8-9Y", "10-11Y", "12-13Y"]

# (category, type, name, [main photo, gallery photos...], featured)
PRODUCTS = [
    ("men", "suits", "Black Safari Suit",
     [WA + "15.17.11 (1).jpeg", WA + "15.17.11.jpeg", WA + "15.17.13 (1).jpeg", WA + "15.17.26.jpeg"], True),
    ("men", "suits", "Charcoal Pinstripe Mandarin Suit",
     [WA + "15.17.12.jpeg", WA + "15.17.10 (2).jpeg", WA + "15.17.24 (2).jpeg", WA + "15.17.25 (2).jpeg"], False),
    ("men", "suits", "Black Tailored Shirt-Jacket Suit",
     [WA + "15.17.16 (1).jpeg"], False),
    ("men", "blazers", "Rust Jacquard Blazer",
     [WA + "15.17.24 (1).jpeg", WA + "15.17.22 (2).jpeg", WA + "15.17.22 (3).jpeg", WA + "15.17.14 (1).jpeg",
      WA + "15.17.23 (1).jpeg"], True),
    ("men", "casual", "Steel Blue Satin Shirt & Trouser Set",
     [WA + "15.17.18 (1).jpeg", WA + "15.17.15 (1).jpeg", WA + "15.17.15 (3).jpeg", WA + "15.17.20 (3).jpeg",
      WA + "15.17.19 (2).jpeg", WA + "15.17.17 (3).jpeg"], True),
    ("men", "casual", "Chocolate Satin Shirt & Trouser Set",
     [WA + "15.17.16 (2).jpeg", WA + "15.17.26 (2).jpeg"], False),

    ("kids", "boys", "Boys Ankara Print Kaftan Suit", [KIDS + "0.avif"], True),
    ("kids", "boys", "Boys Black & Gold Embroidered Suit", [KIDS + "01.jpg", KIDS + "01b.jpg"], False),
    ("kids", "boys", "Boys White & Gold Embroidered Suit", [KIDS + "02.jpg"], False),
    ("kids", "boys", "Boys Royal Blue Agbada Set with Cap", [KIDS + "03.avif"], False),
    ("kids", "boys", "Boys Navy Embroidered Agbada", [KIDS + "04.jpg"], False),
    ("kids", "boys", "Toddler Navy Agbada Set with Cap", [KIDS + "05.jpg"], False),
    ("kids", "boys", "Toddler Purple Agbada Set", [KIDS + "06.jpg"], False),
    ("kids", "boys", "Boys Zulu-Inspired Heritage Set", [KIDS + "07.webp"], False),
    ("kids", "boys", "Boys Leopard Print Heritage Set", [KIDS + "08.jpg"], False),
    ("kids", "boys", "Boys White & Gold Kaftan Suit", [KIDS + "18.webp"], False),
    ("kids", "girls", "Girls Blue Ankara Skater Dress", [KIDS + "09.jpg", KIDS + "10b.jpg"], True),
    ("kids", "girls", "Girls Blue Ankara Maxi Dress", [KIDS + "10.jpg"], False),
    ("kids", "girls", "Girls Yellow Ankara Flutter-Sleeve Dress", [KIDS + "11.webp"], False),
    ("kids", "girls", "Girls Orange Ankara Party Dress", [KIDS + "12.jpg"], False),
    ("kids", "girls", "Girls Orange Tulle Party Dress with Bag", [KIDS + "13.jpg"], False),
    ("kids", "girls", "Girls Orange Xhosa-Inspired Dress", [KIDS + "14.jpg"], False),
    ("kids", "girls", "Girls Crown Print Dress", [KIDS + "15.jpg"], False),
    ("kids", "girls", "Girls Purple Ankara Dress", [KIDS + "16.jpg"], False),
    ("kids", "girls", "Girls Blue Ankara Dress with Gold Belt", [KIDS + "17.avif"], False),
]

# Workshop + shop front photos for the About page (near-duplicates left out).
OFFICE = [
    "office/" + WA + "15.43.29.jpeg", "office/" + WA + "15.43.33.jpeg", "office/" + WA + "15.43.35.jpeg",
    "office/" + WA + "15.43.47.jpeg", "office/" + WA + "15.44.03.jpeg", "office/" + WA + "15.44.06.jpeg",
    "office/" + WA + "15.44.10 (1).jpeg", "office/" + WA + "15.44.11 (1).jpeg",
    WA + "15.17.09.jpeg",  # shop front
]
VIDEOS = ["office/WhatsApp Video 2026-10-29 at 15.43.18.mp4", "office/WhatsApp Video 2026-10-29 at 15.43.22.mp4"]

BANK = {
    "bank_name": "FNB",
    "bank_account_number": "63231801374",
    "bank_branch_code": "250655",
}


def slugify(text):
    return re.sub(r"[^a-z0-9]+", "-", text.lower()).strip("-")


def sql(v):
    if isinstance(v, (int, float)):
        return str(v)
    return "'" + str(v).replace("\\", "\\\\").replace("'", "''") + "'"


def save_web(src, dest, box=(1000, 1400)):
    im = ImageOps.exif_transpose(Image.open(os.path.join(SRC, src))).convert("RGB")
    im.thumbnail(box, Image.LANCZOS)
    im.save(dest, "JPEG", quality=84, optimize=True, progressive=True)


def main():
    os.makedirs("assets/img/products", exist_ok=True)
    os.makedirs("assets/img/office", exist_ok=True)
    os.makedirs("assets/video", exist_ok=True)
    os.makedirs("database/migrations", exist_ok=True)

    out = [
        "-- Steven Picknwear catalog update, 29 Oct 2026. Generated by scripts/add-catalog-2026-10.py.",
        "-- Adds the men's photoshoot + kids range, sets prices (men/women R2,999, kids R1,199,",
        "-- Predict The Future Tee R900) and the FNB banking details. Safe to run more than once.",
        "SET NAMES utf8mb4;",
        "",
    ]
    for n, (cat, typ, name, photos, featured) in enumerate(PRODUCTS, start=1):
        slug = slugify(name)
        images = []
        for k, photo in enumerate(photos):
            web = f"assets/img/products/{slug}.jpg" if k == 0 else f"assets/img/products/{slug}-{k + 1}.jpg"
            save_web(photo, web)
            images.append(web)

        sku = f"SPW-{cat[0].upper()}{typ[:2].upper()}-N{n:02d}"
        price = PRICE_KIDS if cat == "kids" else PRICE_DEFAULT
        tags = f"{typ},{TYPE_LABELS[typ].lower()},{cat}"
        short = {"men": f"{TYPE_LABELS[typ]} for him.", "kids": f"Kidswear for {typ}."}[cat]
        out.append(
            "INSERT IGNORE INTO products (category_id, name, slug, short_desc, description, type, base_price, sku, "
            "stock_status, stock_qty, featured_image, is_featured, is_one_of_a_kind, status, tags) VALUES ("
            f"(SELECT id FROM categories WHERE slug={sql(cat)}), {sql(name)}, {sql(slug)}, {sql(short)}, "
            f"{sql(DESCRIPTIONS[typ])}, 'variable', {price}.00, {sql(sku)}, 'in_stock', 0, {sql(images[0])}, "
            f"{1 if featured else 0}, 0, 'published', {sql(tags)});"
        )
        out.append(f"SET @pid = (SELECT id FROM products WHERE slug={sql(slug)});")
        sizes = KIDS_SIZES if cat == "kids" else MEN_SIZES
        rows = " UNION ALL ".join(f"SELECT {sql(s)} AS size_sa, {sql(sku + '-' + s)} AS sku" for s in sizes)
        out.append(
            f"INSERT INTO product_variants (product_id, size_sa, stock_qty, sku) SELECT @pid, v.size_sa, 5, v.sku "
            f"FROM ({rows}) v WHERE NOT EXISTS (SELECT 1 FROM product_variants WHERE product_id = @pid);"
        )
        if len(images) > 1:
            rows = " UNION ALL ".join(f"SELECT {sql(img)} AS image, {k} AS sort_order" for k, img in enumerate(images[1:], 1))
            out.append(
                f"INSERT INTO product_gallery (product_id, image, sort_order) SELECT @pid, g.image, g.sort_order "
                f"FROM ({rows}) g WHERE NOT EXISTS (SELECT 1 FROM product_gallery WHERE product_id = @pid);"
            )

    out += [
        "",
        "-- Prices",
        "UPDATE products p JOIN categories c ON c.id = p.category_id SET p.base_price = 2999.00 WHERE c.slug IN ('men', 'women');",
        "UPDATE products p JOIN categories c ON c.id = p.category_id SET p.base_price = 1199.00 WHERE c.slug = 'kids';",
    ]
    for slug, price in PRICE_OVERRIDES.items():
        out.append(f"UPDATE products SET base_price = {price}.00 WHERE slug = {sql(slug)};")
    out.append("-- Drop any sale price that is no longer below the new price.")
    out.append("UPDATE products SET sale_price = NULL WHERE sale_price IS NOT NULL AND sale_price >= base_price;")
    out += ["", "-- Banking details (shown at checkout)"]
    for key, value in BANK.items():
        out.append(f"INSERT INTO settings (key_name, value) VALUES ({sql(key)}, {sql(value)}) "
                   "ON DUPLICATE KEY UPDATE value = VALUES(value);")
    open("database/migrations/2026-10-29-catalog-update.sql", "w", encoding="utf-8").write("\n".join(out) + "\n")

    for i, photo in enumerate(OFFICE, start=1):
        name = "shop-front.jpg" if i == len(OFFICE) else f"workshop-{i:02d}.jpg"
        save_web(photo, f"assets/img/office/{name}", box=(1400, 1400))
    for i, video in enumerate(VIDEOS, start=1):
        shutil.copyfile(os.path.join(SRC, video), f"assets/video/workshop-{i}.mp4")

    print(len(PRODUCTS), "products,", sum(len(p[3]) for p in PRODUCTS), "product photos,",
          len(OFFICE), "office photos,", len(VIDEOS), "videos")


if __name__ == "__main__":
    main()
