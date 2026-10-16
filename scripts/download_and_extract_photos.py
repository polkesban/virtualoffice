#!/usr/bin/env python3
"""
Comprehensive Wisudawan Photo Downloader & Extractor from Google Drive.
- Connects to Google Drive API using Polkesban refresh token.
- Scans all subfolders in root folder 1Eh9jAtAlQyVMMCCAg0qCo-edNVFFeQVw.
- Extracts photos from all PPTX files (mapping slide text NIM -> slide photo).
- Downloads all individual photo files (mapping filename NIM/Name -> participant).
- Saves all extracted photos into storage/app/public/wisudawan-photos/{nim}.{ext}.
- Updates unit_event_participants table in database.
"""

import os, sys, re, io, json, time, zipfile
import urllib.request, urllib.parse, urllib.error
import xml.etree.ElementTree as ET

TARGET_DIR = '/www/wwwroot/polkesban.ac.id/storage/app/public/wisudawan-photos'
os.makedirs(TARGET_DIR, exist_ok=True)

def load_env(env_path='/www/wwwroot/polkesban.ac.id/.env'):
    if os.path.exists(env_path):
        with open(env_path, 'r', encoding='utf-8') as f:
            for line in f:
                line = line.strip()
                if line and not line.startswith('#') and '=' in line:
                    k, v = line.split('=', 1)
                    k = k.strip()
                    v = v.strip().strip('"').strip("'")
                    if k and k not in os.environ:
                        os.environ[k] = v

load_env()

CLIENT_ID = os.getenv('GOOGLE_DRIVE_CLIENT_ID', '')
CLIENT_SECRET = os.getenv('GOOGLE_DRIVE_CLIENT_SECRET', '')
REFRESH_TOKEN = os.getenv('GOOGLE_DRIVE_REFRESH_TOKEN', '')
ROOT_FOLDER_ID = os.getenv('GOOGLE_DRIVE_FOLDER_ID', '1Eh9jAtAlQyVMMCCAg0qCo-edNVFFeQVw')

current_token = None
token_expiry = 0

def get_access_token():
    global current_token, token_expiry
    now = time.time()
    if current_token and now < token_expiry - 60:
        return current_token

    token_url = 'https://oauth2.googleapis.com/token'
    data = urllib.parse.urlencode({
        'client_id': CLIENT_ID,
        'client_secret': CLIENT_SECRET,
        'refresh_token': REFRESH_TOKEN,
        'grant_type': 'refresh_token'
    }).encode('utf-8')

    req = urllib.request.Request(token_url, data=data)
    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            res = json.loads(resp.read().decode('utf-8'))
            current_token = res['access_token']
            token_expiry = now + res.get('expires_in', 3600)
            return current_token
    except Exception as e:
        print(f"[!] Error fetching access token: {e}")
        if current_token:
            return current_token
        raise

def api_get(url):
    token = get_access_token()
    req = urllib.request.Request(url, headers={'Authorization': f'Bearer {token}'})
    try:
        with urllib.request.urlopen(req, timeout=45) as resp:
            return json.loads(resp.read().decode('utf-8'))
    except urllib.error.HTTPError as e:
        if e.code == 401:
            token = get_access_token()
            req = urllib.request.Request(url, headers={'Authorization': f'Bearer {token}'})
            with urllib.request.urlopen(req, timeout=45) as resp:
                return json.loads(resp.read().decode('utf-8'))
        raise

def list_folder_children(folder_id):
    q = urllib.parse.quote(f"'{folder_id}' in parents and trashed=false")
    url = f"https://www.googleapis.com/drive/v3/files?q={q}&fields=files(id,name,mimeType,size)&pageSize=300"
    data = api_get(url)
    return data.get('files', [])

def download_file(file_id, mime_type=''):
    token = get_access_token()
    if 'presentation' in mime_type and not mime_type.endswith('presentationml.presentation'):
        url = f"https://www.googleapis.com/drive/v3/files/{file_id}/export?mimeType=application%2Fvnd.openxmlformats-officedocument.presentationml.presentation"
    else:
        url = f"https://www.googleapis.com/drive/v3/files/{file_id}?alt=media"
    
    req = urllib.request.Request(url, headers={'Authorization': f'Bearer {token}'})
    try:
        with urllib.request.urlopen(req, timeout=120) as resp:
            return resp.read()
    except Exception as e:
        # Fallback to direct export URL if it is Google Slides
        fallback_url = f"https://docs.google.com/presentation/d/{file_id}/export/pptx"
        req2 = urllib.request.Request(fallback_url, headers={'Authorization': f'Bearer {token}', 'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req2, timeout=120) as resp2:
            return resp2.read()

# Load DB participants
import subprocess
print("Loading participants from database for Event 70...")
load_cmd = r"""php -r '
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$pts = App\Models\UnitEventParticipant::where("unit_event_id", 70)->get(["id", "identity_number", "nama", "instansi", "meta", "foto_path"]);
$res = [];
foreach ($pts as $p) {
    $nim = strtoupper(trim($p->identity_number ?? ""));
    $nama = $p->meta["nama_wisudawan"] ?? str_replace(["Orang Tua / Wali dari ", "Orang tua/ Wali ", "Orang Tua/Wali dari "], "", $p->nama);
    $res[] = [
        "id" => $p->id,
        "nim" => $nim,
        "nama" => trim($nama),
        "instansi" => trim($p->instansi ?? ""),
        "foto_path" => $p->foto_path,
        "meta" => $p->meta
    ];
}
echo json_encode($res);
'"""

pts_raw = subprocess.check_output(load_cmd, shell=True, cwd='/www/wwwroot/polkesban.ac.id')
participants = json.loads(pts_raw.decode('utf-8'))
print(f"Loaded {len(participants)} participants.")

def clean_str(s):
    return re.sub(r'[^a-zA-Z0-9]', '', (s or '').lower())

def tokenize_str(s):
    return [w for w in re.sub(r'[^a-zA-Z0-9]', ' ', (s or '').lower()).split() if len(w) > 0]

def normalize_prodi(p):
    p = (p or '').lower()
    p = p.replace('kampus', '').replace('dan dietetika', '').replace('lingkungan', '')
    p = re.sub(r'[^a-z0-9]', '', p)
    return p

by_nim = {}
by_clean_name = {}
prodi_participants = {} # normalized_prodi -> list of p

for p in participants:
    if p['nim']:
        by_nim[p['nim']] = p
    
    cname = clean_str(p['nama'])
    if cname:
        by_clean_name[cname] = p

    norm_pr = normalize_prodi(p['instansi'])
    if norm_pr not in prodi_participants:
        prodi_participants[norm_pr] = []
    prodi_participants[norm_pr].append(p)

def find_participant(nim=None, name_candidate=None, prodi_hint=None):
    if nim and nim.upper() in by_nim:
        return by_nim[nim.upper()]
    
    if not name_candidate:
        return None
    
    cname = clean_str(name_candidate)
    if not cname:
        return None
    
    # 1. Exact clean name match
    if cname in by_clean_name:
        return by_clean_name[cname]
    
    c_tokens = tokenize_str(name_candidate)
    if not c_tokens:
        return None

    # Helper to check token match
    def check_candidate_list(cand_list):
        # Exact token match
        for p in cand_list:
            p_tokens = tokenize_str(p['nama'])
            if len(c_tokens) >= 2 and len(c_tokens) <= len(p_tokens):
                all_match = True
                for i, tok in enumerate(c_tokens):
                    if i < len(p_tokens):
                        if len(tok) == 1:
                            if not p_tokens[i].startswith(tok):
                                all_match = False
                                break
                        elif tok != p_tokens[i]:
                            all_match = False
                            break
                    else:
                        all_match = False
                        break
                if all_match:
                    return p
        
        # Word overlap match
        for p in cand_list:
            p_tokens = tokenize_str(p['nama'])
            if len(c_tokens) >= 2 and len(p_tokens) >= 2:
                matched_words = 0
                for ct in c_tokens:
                    if len(ct) > 2 and ct in p_tokens:
                        matched_words += 1
                if matched_words >= 2 and matched_words >= len(c_tokens) - 1:
                    return p
        return None

    # Check in prodi first if hint provided
    if prodi_hint:
        norm_hint = normalize_prodi(prodi_hint)
        for k, cand_list in prodi_participants.items():
            if norm_hint in k or k in norm_hint:
                res = check_candidate_list(cand_list)
                if res:
                    return res

    # Check global candidates
    return check_candidate_list(participants)

matched_photos = {} # participant_id -> {nim, filename, source}

def save_image_for_participant(p, img_bytes, ext):
    if not img_bytes or len(img_bytes) < 3000:
        return False
    nim = p['nim']
    ext = (ext or '.jpg').lower()
    if ext == '.jpeg': ext = '.jpg'
    if not ext.startswith('.'): ext = '.' + ext
    
    filename = f"{nim}{ext}"
    filepath = os.path.join(TARGET_DIR, filename)
    with open(filepath, 'wb') as f:
        f.write(img_bytes)
    
    matched_photos[p['id']] = {
        'id': p['id'],
        'nim': nim,
        'nama': p['nama'],
        'prodi': p['instansi'],
        'filename': filename
    }
    return True

def process_pptx_data(content, file_name, prodi_hint=""):
    try:
        z = zipfile.ZipFile(io.BytesIO(content))
    except Exception as e:
        print(f"  [!] Invalid zip/pptx for {file_name}: {e}")
        return 0

    namelist = z.namelist()
    slide_files = [n for n in namelist if re.match(r'ppt/slides/slide\d+\.xml', n)]
    slide_files.sort(key=lambda x: int(re.search(r'\d+', x).group()))

    if not slide_files:
        return 0

    # 1. Detect template images by counting occurrences across slides
    media_counts = {}
    slide_media_map = {}

    for s in slide_files:
        rels_path = s.replace('ppt/slides/', 'ppt/slides/_rels/') + '.rels'
        slide_media_map[s] = []
        if rels_path in namelist:
            try:
                rels_root = ET.fromstring(z.read(rels_path))
                for r in rels_root.iter():
                    target = r.attrib.get('Target', '')
                    if 'media/' in target:
                        clean_target = os.path.normpath(os.path.join('ppt/slides', target))
                        if clean_target in namelist:
                            slide_media_map[s].append(clean_target)
                            media_counts[clean_target] = media_counts.get(clean_target, 0) + 1
            except Exception:
                pass

    template_threshold = max(3, int(len(slide_files) * 0.15)) if len(slide_files) > 4 else 999
    template_images = {m for m, count in media_counts.items() if count >= template_threshold}

    saved_in_pptx = 0

    for s in slide_files:
        try:
            xml_data = z.read(s)
            root = ET.fromstring(xml_data)
            texts = [elem.text.strip() for elem in root.iter() if elem.text and elem.text.strip()]
            full_text = ' '.join(texts)

            # Find NIM
            nim_match = re.search(r'P17\d{8,11}', full_text, re.IGNORECASE)
            nim = nim_match.group(0).upper() if nim_match else None

            # Find Name in slide texts
            name_candidate = None
            if not nim:
                for i, t in enumerate(texts):
                    if t.lower() in ('nama', 'nama:', 'nama :') and i + 1 < len(texts):
                        name_candidate = texts[i + 1]
                        break

            matched_p = find_participant(nim=nim, name_candidate=name_candidate, prodi_hint=prodi_hint)
            if not matched_p:
                # Try all text elements as name candidate
                for t in texts:
                    if len(t) > 6 and not any(w in t.lower() for w in ['wisuda', 'gelombang', 'politeknik', 'kesehatan', 'kemenkes', 'program', 'studi', 'september']):
                        matched_p = find_participant(name_candidate=t, prodi_hint=prodi_hint)
                        if matched_p:
                            break

            if not matched_p:
                continue

            # Candidate images for this slide
            cand_media = [m for m in slide_media_map.get(s, []) if m not in template_images]
            if not cand_media:
                continue

            # Pick largest candidate image
            best_target = max(cand_media, key=lambda m: len(z.read(m)))
            img_bytes = z.read(best_target)
            ext = os.path.splitext(best_target)[1]
            if save_image_for_participant(matched_p, img_bytes, ext):
                saved_in_pptx += 1

        except Exception as e:
            pass

    print(f"  ✓ {file_name}: Extracted {saved_in_pptx} photos.")
    return saved_in_pptx

def scan_folder_recursive(folder_id, folder_name="", prodi_context="", depth=0):
    indent = "  " * depth
    print(f"{indent}Scanning: {folder_name or 'Root'}...")
    items = list_folder_children(folder_id)

    # Separate folders and files
    folders = [it for it in items if it['mimeType'] == 'application/vnd.google-apps.folder']
    files = [it for it in items if it['mimeType'] != 'application/vnd.google-apps.folder']

    # Update prodi context if this folder is a prodi folder
    current_prodi = prodi_context
    if depth <= 1 and any(prefix in folder_name.upper() for prefix in ['DIII', 'STR', 'PROFESI']):
        current_prodi = folder_name

    for f in folders:
        fname = f['name']
        # Strictly skip JUNI 2026 folders
        if 'JUNI' in fname.upper():
            continue
        scan_folder_recursive(f['id'], fname, current_prodi, depth + 1)

    for item in files:
        name = item['name']
        mime = item['mimeType']
        fid = item['id']

        # Skip non-relevant files (e.g. apk, docx, pdf)
        if name.lower().endswith(('.apk', '.pdf', '.docx', '.xlsx')):
            continue

        if 'presentation' in mime or name.lower().endswith(('.pptx', '.ppt')):
            print(f"{indent}  Downloading PPTX: {name} (Prodi: {current_prodi})...")
            try:
                content = download_file(fid, mime)
                process_pptx_data(content, name, current_prodi)
            except Exception as e:
                print(f"{indent}  [!] Failed {name}: {e}")

        elif mime.startswith('image/') or name.lower().endswith(('.jpg', '.jpeg', '.png', '.webp')):
            # Direct image file
            # 1. Try NIM from name
            nim_match = re.search(r'P17\d{8,11}', name, re.IGNORECASE)
            nim = nim_match.group(0).upper() if nim_match else None
            
            # 2. Try Name
            clean_fname = os.path.splitext(name)[0]
            # Strip prefixes like "1. ", "01-", etc.
            clean_fname = re.sub(r'^\d+[\s\.\-_]+', '', clean_fname)
            matched_p = find_participant(nim=nim, name_candidate=clean_fname, prodi_hint=current_prodi)

            if matched_p:
                try:
                    img_bytes = download_file(fid, mime)
                    ext = os.path.splitext(name)[1] or '.jpg'
                    if save_image_for_participant(matched_p, img_bytes, ext):
                        pass
                except Exception as e:
                    print(f"{indent}  [!] Error downloading image {name}: {e}")

print("\n=== STARTING RECURSIVE EXTRACTION FROM GOOGLE DRIVE ===")
start_time = time.time()
scan_folder_recursive(ROOT_FOLDER_ID, "Root Kumpulan PPT Wisuda")
duration = round(time.time() - start_time, 1)

print("\n" + "="*50)
print(f"Extraction finished in {duration}s!")
print(f"Total participants matched with photos: {len(matched_photos)} / {len(participants)}")
print("="*50)

# Save JSON mapping
summary_path = '/www/wwwroot/polkesban.ac.id/storage/app/wisudawan_photos_summary.json'
with open(summary_path, 'w') as f:
    json.dump({
        'total_participants': len(participants),
        'matched_count': len(matched_photos),
        'matched': matched_photos
    }, f, indent=2)
print(f"Saved summary to {summary_path}")

# Update database
print("Updating database participants with foto_path...")
update_cmd = r"""php -r '
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$summary = json_decode(file_get_contents("/www/wwwroot/polkesban.ac.id/storage/app/wisudawan_photos_summary.json"), true);
$matched = $summary["matched"] ?? [];
$updated = 0;

foreach ($matched as $pid => $item) {
    $p = App\Models\UnitEventParticipant::find($pid);
    if ($p) {
        $p->foto_path = "wisudawan-photos/" . $item["filename"];
        $meta = $p->meta ?? [];
        $meta["foto_path"] = "wisudawan-photos/" . $item["filename"];
        $p->meta = $meta;
        $p->saveQuietly();
        $updated++;
    }
}
echo "Successfully updated $updated participants in database!\n";
'"""

subprocess.run(update_cmd, shell=True, cwd='/www/wwwroot/polkesban.ac.id')
print("=== All Operations Completed Successfully ===")
