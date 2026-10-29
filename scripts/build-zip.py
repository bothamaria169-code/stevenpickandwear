"""
Steven Picknwear: build the File Manager upload zip from the committed code.

Writes dist/stevenpickandwear-site.zip (extract into public_html). Files get
0644 and folders 0755, so the web server can read CSS and images.
config/config.local.php is only added when you pass --config <file>; it never
comes from Git.

Run from the project root:
    python scripts/build-zip.py [--config path/to/config.local.php]
"""
import io
import os
import subprocess
import sys
import tarfile
import zipfile

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
os.chdir(ROOT)
OUT = "dist/stevenpickandwear-site.zip"
SKIP_DIRS = ("database/", "scripts/", "coming-soon/")
SKIP_FILES = (".cpanel.yml", ".gitignore")


def skip(name):
    return name.startswith(SKIP_DIRS) or name in SKIP_FILES or name.endswith(".md")


def add(z, name, data, mode):
    info = zipfile.ZipInfo(name, date_time=(2026, 1, 1, 0, 0, 0))
    info.create_system = 3  # Unix, so extractors honour the mode bits
    info.external_attr = mode << 16
    info.compress_type = zipfile.ZIP_DEFLATED
    z.writestr(info, data)


def main():
    config = None
    if "--config" in sys.argv:
        config = open(sys.argv[sys.argv.index("--config") + 1], "rb").read()

    tar = subprocess.run(["git", "archive", "--format=tar", "HEAD"], check=True, capture_output=True).stdout
    os.makedirs("dist", exist_ok=True)
    dirs, count = set(), 0
    with tarfile.open(fileobj=io.BytesIO(tar)) as t, zipfile.ZipFile(OUT, "w") as z:
        files = [m for m in t.getmembers() if m.isfile() and not skip(m.name)]
        extra = ["uploads/products/x", "uploads/proof-of-payments/x"] + (["config/config.local.php"] if config else [])
        for name in [m.name for m in files] + extra:
            parts = name.split("/")[:-1]
            for i in range(1, len(parts) + 1):
                dirs.add("/".join(parts[:i]) + "/")
        for d in sorted(dirs):
            add(z, d, b"", 0o40755)
        for m in files:
            add(z, m.name, t.extractfile(m).read(), 0o100644)
            count += 1
        if config:
            add(z, "config/config.local.php", config, 0o100640)
    print(f"{OUT}: {count} files, {len(dirs)} folders" + (" + config.local.php" if config else ""))


if __name__ == "__main__":
    main()
