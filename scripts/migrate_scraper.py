#!/usr/bin/env python3
"""
Scraper migrasi data dari poltekkesbandung.ac.id → polkesban.ac.id
Mengambil: Berita, Pengumuman, Deskripsi Jurusan (faculty-detail)

Penggunaan:
    python3 migrate_scraper.py                   # scrape semua
    python3 migrate_scraper.py --only news       # berita saja
    python3 migrate_scraper.py --only faculty    # jurusan saja
    python3 migrate_scraper.py --only pages      # halaman statis saja
    python3 migrate_scraper.py --limit 10        # batasi 10 artikel (test)
"""

import re
import sys
import json
import time
import hashlib
import os
import argparse
import unicodedata
from pathlib import Path
from urllib.parse import urlparse, urljoin
from datetime import datetime

import requests
from bs4 import BeautifulSoup
import trafilatura

# ─── Konfigurasi ──────────────────────────────────────────────────────────────

BASE_URL       = "https://poltekkesbandung.ac.id"
LARAVEL_ROOT   = Path(__file__).parent.parent
OUTPUT_DIR     = LARAVEL_ROOT / "storage" / "app" / "public" / "migrated"
IMAGES_DIR     = OUTPUT_DIR / "images"
JSON_DIR       = OUTPUT_DIR / "json"

DELAY_SECONDS  = 0.8   # jeda antar request (hormati server lama)
TIMEOUT        = 20
MAX_RETRIES    = 3

HEADERS = {
    "User-Agent": "Mozilla/5.0 (compatible; MigrationBot/1.0; +https://polkesban.ac.id/)",
    "Accept": "text/html,application/xhtml+xml",
    "Accept-Language": "id,en;q=0.9",
}

# Slug halaman statis yang relevan untuk migrasi
STATIC_PAGES = [
    "sejarah-poltekkes",
    "visi-misi",
    "akreditasi",
]

# ─── Setup ────────────────────────────────────────────────────────────────────

OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
IMAGES_DIR.mkdir(parents=True, exist_ok=True)
JSON_DIR.mkdir(parents=True, exist_ok=True)

session = requests.Session()
session.headers.update(HEADERS)

stats = {"news": 0, "faculty": 0, "pages": 0, "images": 0, "errors": 0}


# ─── Helper functions ─────────────────────────────────────────────────────────

def fetch(url: str, retries: int = MAX_RETRIES) -> str | None:
    """Ambil HTML dari URL dengan retry."""
    for attempt in range(retries):
        try:
            resp = session.get(url, timeout=TIMEOUT)
            resp.raise_for_status()
            return resp.text
        except requests.RequestException as e:
            print(f"  [!] Attempt {attempt+1}/{retries} failed for {url}: {e}", file=sys.stderr)
            if attempt < retries - 1:
                time.sleep(2 ** attempt)
    stats["errors"] += 1
    return None


def slugify(text: str) -> str:
    """Buat slug dari teks."""
    text = unicodedata.normalize("NFKD", text).encode("ascii", "ignore").decode()
    text = re.sub(r"[^\w\s-]", "", text.lower())
    return re.sub(r"[-\s]+", "-", text).strip("-")[:80]


def download_image(url: str) -> str | None:
    """
    Unduh gambar, simpan di images/ dengan nama hash.
    Kembalikan path relatif untuk disimpan di DB: migrated/images/xxx.jpg
    """
    if not url or not url.startswith("http"):
        return None

    ext = Path(urlparse(url).path).suffix.lower() or ".jpg"
    if ext not in (".jpg", ".jpeg", ".png", ".gif", ".webp"):
        ext = ".jpg"

    filename = hashlib.md5(url.encode()).hexdigest() + ext
    local_path = IMAGES_DIR / filename

    if local_path.exists():
        return f"migrated/images/{filename}"  # sudah diunduh sebelumnya

    try:
        resp = session.get(url, timeout=TIMEOUT, stream=True)
        resp.raise_for_status()
        with open(local_path, "wb") as f:
            for chunk in resp.iter_content(8192):
                f.write(chunk)
        stats["images"] += 1
        return f"migrated/images/{filename}"
    except Exception as e:
        print(f"  [!] Gagal unduh gambar {url}: {e}", file=sys.stderr)
        return None


def html_to_markdown(html: str, url: str) -> str:
    """
    Konversi HTML ke Markdown.
    Untuk halaman berita poltekkesbandung.ac.id, ekstrak hanya konten artikel
    (hapus noise nav, author info, view count) sebelum diproses trafilatura.
    """
    soup = BeautifulSoup(html, "lxml")

    # Hapus elemen noise sebelum ekstraksi
    for sel in [".tg-themepostinfo", ".tg-header", ".tg-footer",
                ".tg-sidebar", "nav", "footer", ".breadcrumb",
                ".tg-btntop", "script", "style"]:
        for el in soup.select(sel):
            el.decompose()

    clean_html = str(soup)
    result = trafilatura.extract(
        clean_html,
        url=url,
        output_format="markdown",
        include_images=True,
        include_links=False,   # skip JS href links
        no_fallback=False,
        favor_recall=True,
    )
    return result or ""


def clean_text(text: str) -> str:
    return re.sub(r"\s+", " ", text or "").strip()


def parse_date(raw: str) -> str | None:
    """Parse berbagai format tanggal ke YYYY-MM-DD."""
    if not raw:
        return None
    raw = raw.strip()
    # Coba format ISO
    m = re.search(r"(\d{4}-\d{2}-\d{2})", raw)
    if m:
        return m.group(1)
    # Format Indonesia: "10 Oktober 2025"
    id_months = {
        "januari": "01", "februari": "02", "maret": "03", "april": "04",
        "mei": "05", "juni": "06", "juli": "07", "agustus": "08",
        "september": "09", "oktober": "10", "november": "11", "desember": "12",
    }
    m = re.search(r"(\d{1,2})\s+(\w+)\s+(\d{4})", raw, re.I)
    if m:
        day, month, year = m.groups()
        mon = id_months.get(month.lower())
        if mon:
            return f"{year}-{mon}-{day.zfill(2)}"
    # Format DD/MM/YYYY
    m = re.search(r"(\d{1,2})[/\-](\d{1,2})[/\-](\d{4})", raw)
    if m:
        d, mo, y = m.groups()
        return f"{y}-{mo.zfill(2)}-{d.zfill(2)}"
    return None


def extract_first_image(soup: BeautifulSoup, base_url: str) -> str | None:
    """Ambil gambar pertama dalam konten artikel."""
    # Prioritas: Open Graph image
    og = soup.find("meta", property="og:image") or soup.find("meta", attrs={"name": "og:image"})
    if og and og.get("content"):
        return og["content"]
    # Gambar pertama dalam artikel
    article = soup.find(id="tg-content") or soup.find("article") or soup.find("main")
    if article:
        img = article.find("img", src=True)
        if img:
            src = img["src"]
            if src.startswith("//"):
                src = "https:" + src
            elif src.startswith("/"):
                src = BASE_URL + src
            # Abaikan logo/icon kecil
            if "logo" not in src.lower() and "icon" not in src.lower():
                return src
    return None


# ─── Scraper berita ───────────────────────────────────────────────────────────

def get_all_news_urls() -> list[str]:
    """Kumpulkan semua URL artikel dari semua halaman paginasi."""
    urls = []
    page = 1
    print("Memuat daftar URL berita...")

    while True:
        html = fetch(f"{BASE_URL}/news?page={page}")
        if not html:
            break

        soup = BeautifulSoup(html, "lxml")
        links = soup.find_all("a", href=re.compile(r"/news-detail/"))
        new_urls = list(dict.fromkeys(
            a["href"] if a["href"].startswith("http") else BASE_URL + a["href"]
            for a in links
        ))

        if not new_urls:
            break

        urls.extend(new_urls)
        print(f"  Halaman {page}: {len(new_urls)} artikel ({len(urls)} total)")

        # Cek apakah ada halaman berikutnya
        next_link = soup.find("a", href=re.compile(rf"/news\?page={page+1}"))
        if not next_link:
            break

        page += 1
        time.sleep(DELAY_SECONDS)

    return list(dict.fromkeys(urls))  # deduplicate


def scrape_news_article(url: str) -> dict | None:
    """Scrape satu artikel berita."""
    html = fetch(url)
    if not html:
        return None

    soup = BeautifulSoup(html, "lxml")

    # ── Metadata via trafilatura (lebih akurat untuk title/date) ──
    meta = trafilatura.extract_metadata(html)

    # Judul
    title = ""
    if meta and meta.title:
        title = clean_text(meta.title)
    if not title:
        title_el = soup.select_one(".tg-themeposttitle h1, .tg-themeposttitle h2, article h1")
        title = clean_text(title_el.get_text()) if title_el else ""
    if not title:
        title = urlparse(url).path.split("/")[-1].replace("-", " ").title()

    # Author — dari .tg-themepostinfo strong (lebih akurat dari trafilatura)
    author = "Admin"
    author_el = soup.select_one(".tg-themepostinfo strong, .tg-themepostinfo .tg-infodata strong")
    if author_el:
        author = clean_text(author_el.get_text())
    elif meta and meta.author:
        author = meta.author

    # Tanggal — trafilatura + htmldate lebih andal dari regex HTML
    published_at = None
    if meta and meta.date:
        published_at = parse_date(meta.date)
    if not published_at:
        published_at = datetime.now().strftime("%Y-%m-%d")

    # Gambar utama
    featured_image_url = extract_first_image(soup, url)
    featured_image_local = download_image(featured_image_url) if featured_image_url else None

    # Konten → Markdown (dengan pre-cleaning noise)
    content_md = html_to_markdown(html, url)

    # Download semua gambar dalam konten dan rewrite URL
    if content_md:
        img_urls = re.findall(r"!\[.*?\]\((https?://[^\)]+)\)", content_md)
        for img_url in img_urls:
            local = download_image(img_url)
            if local:
                content_md = content_md.replace(img_url, f"/storage/{local}")

    # Kategori: deteksi "pengumuman" dari teks atau URL
    slug = urlparse(url).path.split("/")[-1]
    text_lower = (title + " " + content_md[:200]).lower()
    if any(k in text_lower for k in ["pengumuman", "informasi", "pemberitahuan", "undangan"]) or \
       any(k in slug for k in ["pengumuman", "announce", "info-"]):
        category = "pengumuman"
    else:
        category = "berita"

    time.sleep(DELAY_SECONDS)

    return {
        "source_url": url,
        "slug": slug,
        "category": category,
        "judul": title,
        "penulis": author,
        "published_at": published_at,
        "featured_image_url": featured_image_url,
        "featured_image_local": featured_image_local,
        "konten_markdown": content_md,
    }


# ─── Scraper faculty/jurusan ──────────────────────────────────────────────────

def get_faculty_urls() -> list[str]:
    """Kumpulkan semua URL faculty-detail dari homepage."""
    html = fetch(BASE_URL)
    if not html:
        return []
    soup = BeautifulSoup(html, "lxml")
    links = soup.find_all("a", href=re.compile(r"/faculty-detail/"))
    urls = list(dict.fromkeys(
        a["href"] if a["href"].startswith("http") else BASE_URL + a["href"]
        for a in links
    ))
    print(f"Ditemukan {len(urls)} URL jurusan")
    return urls


def scrape_faculty(url: str) -> dict | None:
    """Scrape satu halaman jurusan."""
    html = fetch(url)
    if not html:
        return None

    soup = BeautifulSoup(html, "lxml")

    # Nama jurusan: cari di heading area content
    name = ""
    for sel in [".tg-themeposttitle h1", ".tg-innerbanner h1", "h1.tg-title",
                "#tg-content h1", "#tg-content h2"]:
        el = soup.select_one(sel)
        if el:
            name = clean_text(el.get_text())
            break
    if not name:
        # Fallback dari <title>
        title_el = soup.find("title")
        name = clean_text(title_el.get_text().split("|")[0]) if title_el else ""

    # Gambar
    image_url = extract_first_image(soup, url)
    image_local = download_image(image_url) if image_url else None

    # Deskripsi → Markdown
    content_md = html_to_markdown(html, url)

    time.sleep(DELAY_SECONDS)

    return {
        "source_url": url,
        "nama": name,
        "slug": slugify(name) if name else urlparse(url).path.split("/")[-1],
        "image_url": image_url,
        "image_local": image_local,
        "deskripsi_markdown": content_md,
    }


# ─── Scraper halaman statis ───────────────────────────────────────────────────

def scrape_static_page(slug: str) -> dict | None:
    """Scrape halaman statis (sejarah, visi-misi, dst)."""
    url = f"{BASE_URL}/page/{slug}"
    html = fetch(url)
    if not html:
        return None

    soup = BeautifulSoup(html, "lxml")
    title_el = soup.select_one(".tg-themeposttitle h1, #tg-content h1, h1")
    title = clean_text(title_el.get_text()) if title_el else slug.replace("-", " ").title()

    content_md = html_to_markdown(html, url)
    time.sleep(DELAY_SECONDS)

    return {
        "source_url": url,
        "slug": slug,
        "judul": title,
        "konten_markdown": content_md,
    }


# ─── Main ─────────────────────────────────────────────────────────────────────

def run(only: str | None = None, limit: int | None = None):
    print("=" * 60)
    print("MIGRASI DATA poltekkesbandung.ac.id → polkesban.ac.id")
    print(f"Output → {OUTPUT_DIR}")
    print("=" * 60)

    # ── 1. Berita ──
    if only in (None, "news"):
        print("\n[1/3] Memulai scraping berita & pengumuman...")
        all_news_urls = get_all_news_urls()
        if limit:
            all_news_urls = all_news_urls[:limit]
        print(f"Total URL berita: {len(all_news_urls)}")

        news_items, pengumuman_items = [], []
        for i, url in enumerate(all_news_urls, 1):
            print(f"  [{i}/{len(all_news_urls)}] {url}")
            item = scrape_news_article(url)
            if item:
                if item["category"] == "pengumuman":
                    pengumuman_items.append(item)
                else:
                    news_items.append(item)
                stats["news"] += 1
            else:
                print(f"    -> GAGAL")

        # Simpan JSON
        berita_path = JSON_DIR / "berita.json"
        pengumuman_path = JSON_DIR / "pengumuman.json"
        berita_path.write_text(json.dumps(news_items, ensure_ascii=False, indent=2))
        pengumuman_path.write_text(json.dumps(pengumuman_items, ensure_ascii=False, indent=2))
        print(f"\n  ✓ {len(news_items)} berita → {berita_path}")
        print(f"  ✓ {len(pengumuman_items)} pengumuman → {pengumuman_path}")

    # ── 2. Jurusan ──
    if only in (None, "faculty"):
        print("\n[2/3] Memulai scraping deskripsi jurusan...")
        faculty_urls = get_faculty_urls()
        faculty_items = []
        for i, url in enumerate(faculty_urls, 1):
            print(f"  [{i}/{len(faculty_urls)}] {url}")
            item = scrape_faculty(url)
            if item:
                faculty_items.append(item)
                stats["faculty"] += 1

        faculty_path = JSON_DIR / "jurusan.json"
        faculty_path.write_text(json.dumps(faculty_items, ensure_ascii=False, indent=2))
        print(f"\n  ✓ {len(faculty_items)} jurusan → {faculty_path}")

    # ── 3. Halaman statis ──
    if only in (None, "pages"):
        print("\n[3/3] Memulai scraping halaman statis...")
        page_items = []
        for slug in STATIC_PAGES:
            print(f"  {slug}")
            item = scrape_static_page(slug)
            if item:
                page_items.append(item)
                stats["pages"] += 1

        pages_path = JSON_DIR / "halaman_statis.json"
        pages_path.write_text(json.dumps(page_items, ensure_ascii=False, indent=2))
        print(f"\n  ✓ {len(page_items)} halaman → {pages_path}")

    # ── Ringkasan ──
    print("\n" + "=" * 60)
    print("SELESAI")
    print(f"  Berita/Pengumuman : {stats['news']}")
    print(f"  Jurusan           : {stats['faculty']}")
    print(f"  Halaman statis    : {stats['pages']}")
    print(f"  Gambar diunduh    : {stats['images']}")
    print(f"  Error             : {stats['errors']}")
    print(f"  Output JSON       : {JSON_DIR}")
    print(f"  Output gambar     : {IMAGES_DIR}")
    print("=" * 60)
    print("\nSelanjutnya jalankan:")
    print("  php8.3 artisan migrate:import")


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Scraper migrasi poltekkesbandung.ac.id")
    parser.add_argument("--only", choices=["news", "faculty", "pages"],
                        help="Hanya scrape bagian tertentu")
    parser.add_argument("--limit", type=int, default=None,
                        help="Batasi jumlah artikel berita (untuk testing)")
    args = parser.parse_args()
    run(only=args.only, limit=args.limit)
