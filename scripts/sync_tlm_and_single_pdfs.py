#!/usr/bin/env python3
"""
Sync DIII TLM photos from /tmp/tlm.pdf and individual student PDFs from Google Drive.
"""

import os, sys, re, json, shutil, subprocess, tempfile
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

def get_access_token():
    token_url = 'https://oauth2.googleapis.com/token'
    data = urllib.parse.urlencode({
        'client_id': CLIENT_ID,
        'client_secret': CLIENT_SECRET,
        'refresh_token': REFRESH_TOKEN,
        'grant_type': 'refresh_token'
    }).encode('utf-8')
    req = urllib.request.Request(token_url, data=data)
    with urllib.request.urlopen(req, timeout=30) as resp:
        return json.loads(resp.read().decode('utf-8'))['access_token']

def download_file(file_id):
    token = get_access_token()
    url = f"https://www.googleapis.com/drive/v3/files/{file_id}?alt=media"
    req = urllib.request.Request(url, headers={'Authorization': f'Bearer {token}'})
    with urllib.request.urlopen(req, timeout=60) as resp:
        return resp.read()

# 1. Load DB participants
print("Loading participants...")
load_cmd = r"""php -r '
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$pts = App\Models\UnitEventParticipant::where("unit_event_id", 70)->get(["id", "identity_number", "nama", "instansi", "foto_path"]);
$res = [];
foreach ($pts as $p) {
    $nim = strtoupper(trim($p->identity_number ?? ""));
    $nama = $p->meta["nama_wisudawan"] ?? str_replace(["Orang Tua / Wali dari ", "Orang tua/ Wali ", "Orang Tua/Wali dari "], "", $p->nama);
    $res[] = [
        "id" => $p->id,
        "nim" => $nim,
        "nama" => trim($nama),
        "instansi" => trim($p->instansi ?? ""),
        "foto_path" => $p->foto_path
    ];
}
echo json_encode($res);
'"""

pts_raw = subprocess.check_output(load_cmd, shell=True, cwd='/www/wwwroot/polkesban.ac.id')
participants = json.loads(pts_raw.decode('utf-8'))
by_nim = {p['nim']: p for p in participants if p['nim']}
by_id = {p['id']: p for p in participants}
already_have = {p['id'] for p in participants if p['foto_path']}

print(f"Total participants: {len(participants)}, Already have photos: {len(already_have)}")

newly_matched = {}

# 2. Process TLM PDF
if os.path.exists('/tmp/tlm.pdf'):
    print("\n--- Processing DIII TLM PDF ---")
    # Read all text by page
    all_text = subprocess.check_output(['pdftotext', '/tmp/tlm.pdf', '-'], stderr=subprocess.DEVNULL).decode('utf-8', errors='ignore')
    pages_text = all_text.split('\x0c')

    # Read image listing
    list_out = subprocess.check_output(['pdfimages', '-list', '/tmp/tlm.pdf'], stderr=subprocess.DEVNULL).decode('utf-8', errors='ignore')
    page_to_photo_num = {}
    
    # Template image sizes to ignore
    template_sizes = {(2397, 1353), (1418, 1082), (2531, 372), (1199, 740), (662, 136)}

    for line in list_out.split('\n'):
        parts = line.split()
        if len(parts) >= 6 and parts[0].isdigit() and parts[1].isdigit():
            page_num = int(parts[0])
            img_num = int(parts[1])
            img_type = parts[2]
            if img_type == 'image' and len(parts) >= 5:
                try:
                    w = int(parts[3])
                    h = int(parts[4])
                    if (w, h) not in template_sizes and 300 <= w <= 1200 and 500 <= h <= 1500:
                        page_to_photo_num[page_num] = img_num
                except ValueError:
                    pass

    print(f"Detected photo candidate for {len(page_to_photo_num)} / {len(pages_text)} pages in TLM PDF.")

    # Match each page with participant
    for page_num, img_num in page_to_photo_num.items():
        if page_num - 1 < len(pages_text):
            ptext = pages_text[page_num - 1]
            nim_match = re.search(r'P17\d{8,11}', ptext, re.IGNORECASE)
            if nim_match:
                nim = nim_match.group(0).upper()
                if nim in by_nim:
                    p = by_nim[nim]
                    # Format img filename: img-000.png, img-012.png, etc.
                    candidates = [
                        f"/tmp/tlm_all/img-{img_num:03d}.png",
                        f"/tmp/tlm_all/img-{img_num:04d}.png",
                        f"/tmp/tlm_all/img-{img_num}.png"
                    ]
                    img_file = None
                    for cand in candidates:
                        if os.path.exists(cand):
                            img_file = cand
                            break
                    
                    if not img_file:
                        continue

                    if img_file and os.path.exists(img_file):
                        target_file = f"{nim}.png"
                        target_path = os.path.join(TARGET_DIR, target_file)
                        shutil.copyfile(img_file, target_path)
                        os.chmod(target_path, 0o775)
                        newly_matched[p['id']] = {
                            'id': p['id'],
                            'nim': nim,
                            'nama': p['nama'],
                            'instansi': p['instansi'],
                            'filename': target_file,
                            'foto_path': f"wisudawan-photos/{target_file}"
                        }
                        already_have.add(p['id'])
                        print(f"  [TLM MATCH] Page {page_num}: {nim} - {p['nama']} -> {target_file}")

print(f"\nTLM Processed! Total newly matched so far: {len(newly_matched)}")

# 3. Save Summary & Update Database
if newly_matched:
    summary_path = '/www/wwwroot/polkesban.ac.id/storage/app/wisudawan_photos_summary.json'
    existing = {}
    if os.path.exists(summary_path):
        try: existing = json.load(open(summary_path))
        except Exception: pass
    matched_all = existing.get('matched', {})
    for pid, it in newly_matched.items():
        matched_all[str(pid)] = it
    
    with open(summary_path, 'w') as f:
        json.dump({
            'total_participants': len(participants),
            'matched_count': len(matched_all),
            'matched': matched_all
        }, f, indent=2)

    print(f"Updating database for {len(newly_matched)} participants...")
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

print("Sync completed successfully!")
