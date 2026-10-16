#!/usr/bin/env python3
"""
Extract photos from PDF files and remaining images in Google Drive.
- Connects to Google Drive using refresh token.
- Finds all PDF files in Wisuda September 2026 folders.
- Uses pdfinfo, pdftotext, and pdfimages to parse slides and extract portrait photos.
- Also inspects direct image files that weren't matched in the first pass.
- Saves photos to /www/wwwroot/polkesban.ac.id/storage/app/public/wisudawan-photos/{nim}.png.
- Updates database unit_event_participants.
"""

import os, sys, re, io, json, time, shutil, subprocess, tempfile
import urllib.request, urllib.parse, urllib.error

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

def download_file(file_id, mime_type=''):
    token = get_access_token()
    url = f"https://www.googleapis.com/drive/v3/files/{file_id}?alt=media"
    req = urllib.request.Request(url, headers={'Authorization': f'Bearer {token}'})
    with urllib.request.urlopen(req, timeout=120) as resp:
        return resp.read()

# Load DB participants
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

by_id = {p['id']: p for p in participants}
by_nim = {p['nim']: p for p in participants if p['nim']}

def clean_str(s):
    return re.sub(r'[^a-zA-Z0-9]', '', (s or '').lower())

def tokenize_str(s):
    return [w for w in re.sub(r'[^a-zA-Z0-9]', ' ', (s or '').lower()).split() if len(w) > 0]

def normalize_prodi(p):
    p = (p or '').lower()
    p = p.replace('kampus', '').replace('dan dietetika', '').replace('lingkungan', '')
    return re.sub(r'[^a-z0-9]', '', p)

by_clean_name = {}
prodi_participants = {}
for p in participants:
    cn = clean_str(p['nama'])
    if cn:
        by_clean_name[cn] = p
    np = normalize_prodi(p['instansi'])
    if np not in prodi_participants:
        prodi_participants[np] = []
    prodi_participants[np].append(p)

already_has_photo = {p['id'] for p in participants if p['foto_path']}
print(f"Already has photo: {len(already_has_photo)} / {len(participants)}")

def find_participant(nim=None, name_candidate=None, prodi_hint=None):
    if nim:
        nim_clean = re.sub(r'[^A-Z0-9]', '', nim.upper())
        if nim_clean in by_nim:
            return by_nim[nim_clean]
        for k, p in by_nim.items():
            if nim_clean in k or k in nim_clean:
                return p

    if not name_candidate:
        return None

    cname = clean_str(name_candidate)
    if not cname:
        return None

    if cname in by_clean_name:
        return by_clean_name[cname]

    c_tokens = tokenize_str(name_candidate)
    if not c_tokens:
        return None

    cand_list = participants
    if prodi_hint:
        np = normalize_prodi(prodi_hint)
        for k in prodi_participants:
            if np in k or k in np:
                cand_list = prodi_participants[k]
                break

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

    # Word overlap
    best_p = None
    best_score = 0
    for p in cand_list:
        p_tokens = tokenize_str(p['nama'])
        if len(c_tokens) >= 2 and len(p_tokens) >= 2:
            matched = sum(1 for ct in c_tokens if len(ct) > 2 and ct in p_tokens)
            if matched >= 2 and matched >= len(c_tokens) - 1:
                if matched > best_score:
                    best_score = matched
                    best_p = p
    return best_p

newly_matched = {}

def save_photo(participant, img_path):
    nim = participant['nim'] or f"id_{participant['id']}"
    ext = os.path.splitext(img_path)[1].lower() or '.png'
    target_filename = f"{nim}{ext}"
    target_path = os.path.join(TARGET_DIR, target_filename)

    shutil.copyfile(img_path, target_path)
    os.chmod(target_path, 0o775)
    rel_path = f"wisudawan-photos/{target_filename}"

    newly_matched[participant['id']] = {
        'id': participant['id'],
        'nim': participant['nim'],
        'nama': participant['nama'],
        'instansi': participant['instansi'],
        'filename': target_filename,
        'foto_path': rel_path
    }
    already_has_photo.add(participant['id'])
    print(f"    [MATCHED] {participant['nim']} - {participant['nama']} -> {target_filename}")
    return True

def process_pdf_file(pdf_bytes, pdf_name, prodi_hint):
    with tempfile.TemporaryDirectory() as tmpdir:
        pdf_path = os.path.join(tmpdir, 'input.pdf')
        with open(pdf_path, 'wb') as f:
            f.write(pdf_bytes)

        # Get page count
        try:
            info_out = subprocess.check_output(['pdfinfo', pdf_path], stderr=subprocess.DEVNULL).decode('utf-8', errors='ignore')
            m = re.search(r'Pages:\s+(\d+)', info_out)
            total_pages = int(m.group(1)) if m else 1
        except Exception:
            total_pages = 1

        print(f"    Processing PDF '{pdf_name}' ({total_pages} pages, prodi: {prodi_hint})...")

        # Skip multi-page PDFs as all full batch presentations are already extracted
        if total_pages > 3:
            return

        # Case 2: 1-3 page PDF for an individual student
        # Try to match from filename first
        nim_match = re.search(r'P17\d{8,11}', pdf_name, re.IGNORECASE)
        nim = nim_match.group(0).upper() if nim_match else None
        clean_name = os.path.splitext(pdf_name)[0]
        clean_name = re.sub(r'^\d+[\s\.\-_]+', '', clean_name)
        clean_name = re.sub(r'PPT.*Wisuda.*', '', clean_name, flags=re.IGNORECASE).strip()

        p = find_participant(nim=nim, name_candidate=clean_name, prodi_hint=prodi_hint)
        if p and p['id'] in already_has_photo:
            return

        if not p:
            # Try extracting text
            try:
                text = subprocess.check_output(['pdftotext', pdf_path, '-'], stderr=subprocess.DEVNULL).decode('utf-8', errors='ignore')
                nim_match = re.search(r'P17\d{8,11}', text, re.IGNORECASE)
                if nim_match:
                    p = find_participant(nim=nim_match.group(0).upper(), prodi_hint=prodi_hint)
                if not p:
                    lines = [l.strip() for l in text.split('\n') if len(l.strip()) > 3]
                    for l in lines:
                        p = find_participant(name_candidate=l, prodi_hint=prodi_hint)
                        if p: break
            except Exception:
                pass

        if p and p['id'] not in already_has_photo:
            # Extract images
            img_prefix = os.path.join(tmpdir, 'single')
            subprocess.run(['pdfimages', '-png', pdf_path, img_prefix], stderr=subprocess.DEVNULL)
            imgs = [os.path.join(tmpdir, f) for f in os.listdir(tmpdir) if f.startswith('single-') and f.endswith('.png')]
            if imgs:
                imgs.sort(key=lambda x: os.path.getsize(x), reverse=True)
                # Pick largest image > 10KB
                if os.path.getsize(imgs[0]) > 10000:
                    save_photo(p, imgs[0])

def scan_folder(folder_id, folder_name='', prodi_context=None, depth=0):
    if 'JUNI' in folder_name.upper():
        return

    q = urllib.parse.quote(f"'{folder_id}' in parents and trashed=false")
    url = f"https://www.googleapis.com/drive/v3/files?q={q}&fields=files(id,name,mimeType,size)&pageSize=300"
    data = api_get(url)
    items = data.get('files', [])

    current_prodi = prodi_context
    if depth <= 1 and any(prefix in folder_name.upper() for prefix in ['DIII', 'STR', 'PROFESI']):
        current_prodi = folder_name

    folders = [it for it in items if it['mimeType'] == 'application/vnd.google-apps.folder']
    files = [it for it in items if it['mimeType'] != 'application/vnd.google-apps.folder']

    for f in folders:
        if 'JUNI' in f['name'].upper():
            continue
        scan_folder(f['id'], f['name'], current_prodi, depth + 1)

    for item in files:
        name = item['name']
        mime = item['mimeType']
        fid = item['id']

        if name.lower().endswith('.pdf') or mime == 'application/pdf':
            # Skip if student already has photo from filename
            nim_match = re.search(r'P17\d{8,11}', name, re.IGNORECASE)
            nim = nim_match.group(0).upper() if nim_match else None
            clean_name = os.path.splitext(name)[0]
            clean_name = re.sub(r'^\d+[\s\.\-_]+', '', clean_name)
            clean_name = re.sub(r'PPT.*Wisuda.*', '', clean_name, flags=re.IGNORECASE).strip()
            p = find_participant(nim=nim, name_candidate=clean_name, prodi_hint=current_prodi)
            if p and p['id'] in already_has_photo:
                continue

            try:
                content = download_file(fid, mime)
                process_pdf_file(content, name, current_prodi)
            except Exception as e:
                print(f"    [!] Error downloading PDF {name}: {e}")

        elif mime.startswith('image/') or name.lower().endswith(('.jpg', '.jpeg', '.png', '.webp')):
            # Direct image file - check if can be matched
            nim_match = re.search(r'P17\d{8,11}', name, re.IGNORECASE)
            nim = nim_match.group(0).upper() if nim_match else None
            clean_name = os.path.splitext(name)[0]
            clean_name = re.sub(r'^\d+[\s\.\-_]+', '', clean_name)
            matched_p = find_participant(nim=nim, name_candidate=clean_name, prodi_hint=current_prodi)

            if matched_p and matched_p['id'] not in already_has_photo:
                try:
                    img_bytes = download_file(fid, mime)
                    with tempfile.NamedTemporaryFile(suffix=os.path.splitext(name)[1] or '.png', delete=False) as tf:
                        tf.write(img_bytes)
                        tf_path = tf.name
                    save_photo(matched_p, tf_path)
                    os.remove(tf_path)
                except Exception as e:
                    print(f"    [!] Error downloading image {name}: {e}")

print("\n=== STARTING SCAN FOR PDF & REMAINING PHOTOS ===")
start_t = time.time()
scan_folder(ROOT_FOLDER_ID, "Root Kumpulan PPT Wisuda")
print(f"Scan completed in {round(time.time() - start_t, 1)}s")
print(f"Newly matched participants: {len(newly_matched)}")

if newly_matched:
    print("Updating database with newly matched photos...")
    # Read existing summary
    summary_path = '/www/wwwroot/polkesban.ac.id/storage/app/wisudawan_photos_summary.json'
    existing = {}
    if os.path.exists(summary_path):
        try:
            existing = json.load(open(summary_path))
        except Exception:
            pass
    matched_all = existing.get('matched', {})
    for pid, it in newly_matched.items():
        matched_all[str(pid)] = it
    
    with open(summary_path, 'w') as f:
        json.dump({
            'total_participants': len(participants),
            'matched_count': len(matched_all),
            'matched': matched_all
        }, f, indent=2)

    update_cmd = r"""php -r '
    require "vendor/autoload.php";
    $app = require_once "bootstrap/app.php";
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $summary = json_decode(file_get_contents("/www/wwwroot/polkesban.ac.id/storage/app/wisudawan_photos_summary.json"), true);
    $matched = $summary["matched"] ?? [];
    $updated = 0;
    foreach ($matched as $pid => $item) {
        $p = App\Models\UnitEventParticipant::find($pid);
        $relPath = $item["foto_path"] ?? (isset($item["filename"]) ? ("wisudawan-photos/" . $item["filename"]) : null);
        if ($p && $relPath && (!$p->foto_path || $p->foto_path != $relPath)) {
            $p->foto_path = $relPath;
            $meta = $p->meta ?? [];
            $meta["foto_path"] = $relPath;
            $p->meta = $meta;
            $p->saveQuietly();
            $updated++;
        }
    }
    echo "Updated $updated participants in database.\n";
    '"""
    subprocess.run(update_cmd, shell=True, cwd='/www/wwwroot/polkesban.ac.id')

print("Done!")
