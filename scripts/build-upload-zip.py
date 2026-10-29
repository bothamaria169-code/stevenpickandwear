"""
Steven Picknwear: build dist/stevenpickandwear-site.zip for a File Manager upload.

Packs the committed site (git HEAD) without the source-only folders, with web-readable
permissions (files 644, folders 755) so the server can serve CSS and images.

    python scripts/build-upload-zip.py            # update zip: never touches config.local.php
    python scripts/build-upload-zip.py --config   # first install: also adds config/config.local.php

Run from the project root, after committing.
"""
import io
import os
import secrets
import subprocess
import sys
import tarfile
import zipfile

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
os.chdir(ROOT)
OUT = "dist/stevenpickandwear-site.zip"
SKIP_DIRS = ("database/", "scripts/", "coming-soon/")
SKIP_FILES = (".cpanel.yml", ".gitignore")

CONFIG = """<?php
// Server-only settings. Fill in DB_USER and DB_PASS from cPanel > MySQL Databases.
define('DB_HOST', 'localhost');
define('DB_NAME', 'nlnthdft_database');
define('DB_USER', 'CHANGE_ME_DB_USER');
define('DB_PASS', 'CHANGE_ME_DB_PASS');

// Signs login tokens. Random, keep private.
define('APP_SECRET', '{secret}');

// Order emails via Brevo (Brevo > SMTP & API > SMTP tab). Leave SMTP_USER empty to disable.
define('SMTP_HOST', 'smtp-relay.brevo.com');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_FROM_EMAIL', 'info@stevenpickandwear.co.za');
define('ADMIN_EMAIL', 'info@stevenpickandwear.co.za');
"""


def entry(name, is_dir=False):
    info = zipfile.ZipInfo(name, date_time=(2026, 10, 29, 0, 0, 0))
    info.create_system = 3  # Unix, so extractors honour the mode bits below
    info.external_attr = ((0o40755 if is_dir else 0o100644) << 16) | (0x10 if is_dir else 0)
    info.compress_type = zipfile.ZIP_DEFLATED
    return info


def main():
    tar = subprocess.run(["git", "archive", "--format=tar", "HEAD"], check=True, capture_output=True).stdout
    os.makedirs("dist", exist_ok=True)
    files = dirs = 0
    with tarfile.open(fileobj=io.BytesIO(tar)) as t, zipfile.ZipFile(OUT, "w") as z:
        for m in t.getmembers():
            name = m.name + ("/" if m.isdir() else "")
            if name.startswith(SKIP_DIRS) or name in SKIP_FILES or name.endswith(".md"):
                continue
            if m.isdir():
                z.writestr(entry(name, True), b""); dirs += 1
            elif m.isfile():
                z.writestr(entry(name), t.extractfile(m).read()); files += 1
        for d in ("uploads/products/", "uploads/proof-of-payments/"):
            z.writestr(entry(d, True), b"")
        if "--config" in sys.argv:
            z.writestr(entry("config/config.local.php"), CONFIG.format(secret=secrets.token_hex(32))); files += 1
    print(f"{OUT}: {files} files, {dirs} folders" + (" (with config.local.php)" if "--config" in sys.argv else ""))


if __name__ == "__main__":
    main()
