#!/usr/bin/env python3
"""
Targeted matching for the remaining ~72 unmatched students.
Directly targets the 6 prodi folders:
- STr Kebidanan
- DIII Keperawatan (Bandung)
- DIII Keperawatan Bogor
- DIII Kebidanan Bogor
- DIII Kebidanan Karawang
- DIII Kebidanan Bandung
"""

import os, sys, re, io, json, shutil, subprocess, tempfile
import urllib.request, urllib.parse, urllib.error

TARGET_DIR = '/www/wwwroot/polkesban.ac.id/storage/app/public/wisudawan-photos'
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

def api_get(url):
    token = get_access_token()
    req = urllib.request.Request(url, headers={'Authorization': f'Bearer {token}'})
    with urllib.request.urlopen(req, timeout=45) as resp:
        return json.loads(resp.read().decode('utf-8'))

def download_file(file_id):
    token = get_access_token()
    url = f"https://www.googleapis.com/drive/v3/files/{file_id}?alt=media"
    req = urllib.request.Request(url, headers={'Authorization': f'Bearer {token}'})
    with urllib.request.urlopen(req, timeout=60) as resp:
        return resp.read()

# Load DB unmatched participants
print("Loading unmatched participants for Event 70...")
load_cmd = r"""php -r '
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$pts = App\Models\UnitEventParticipant::where("unit_event_id", 70)
    ->where(function($q) { $q->whereNull("foto_path")->orWhere("foto_path", ""); })
    ->get(["id", "identity_number", "nama", "instansi"]);
$res = [];
foreach ($pts as $p) {
    $nim = strtoupper(trim($p->identity_number ?? ""));
    $nama = $p->meta["nama_wisudawan"] ?? str_replace(["Orang Tua / Wali dari ", "Orang tua/ Wali ", "Orang Tua/Wali dari "], "", $p->nama);
    $res[] = [
        "id" => $p->id,
        "nim" => $nim,
        "nama" => trim($nama),
        "instansi" => trim($p->instansi ?? "")
    ];
}
echo json_encode($res);
'"""

unmatched = json.loads(subprocess.check_output(load_cmd, shell=True, cwd='/www/wwwroot/polkesban.ac.id').decode('utf-8'))
print(f"Loaded {len(unmatched)} unmatched participants.")

def clean_str(s):
    return re.sub(r'[^a-zA-Z0-9]', '', (s or '').lower())

def tokenize_str(s):
    return [w for w in re.sub(r'[^a-zA-Z0-9]', ' ', (s or '').lower()).split() if len(w) > 0]

def find_candidate(nim_str, name_str, prodi_str):
    if nim_str:
        nim_clean = re.sub(r'[^A-Z0-9]', '', nim_str.upper())
        for p in unmatched:
            p_nim = re.sub(r'[^A-Z0-9]', '', p['nim'])
            if p_nim and (nim_clean == p_nim or nim_clean in p_nim or p_nim in nim_clean):
                return p

    if not name_str:
        return None

    cname = clean_str(name_str)
    # Check exact clean name
    for p in unmatched:
        if clean_str(p['nama']) == cname:
            return p

    # Check token overlap
    c_tokens = tokenize_str(name_str)
    if len(c_tokens) >= 1:
        # First check in same prodi
        for p in unmatched:
            p_tokens = tokenize_str(p['nama'])
            # If at least 2 tokens match or single distinctive name matches
            matches = sum(1 for tok in c_tokens if len(tok) >= 3 and tok in p_tokens)
            if (matches >= 2) or (len(c_tokens) == 1 and len(c_tokens[0]) >= 4 and c_tokens[0] in p_tokens):
                return p

    return None

newly_matched = {}

def save_matched_image(p, img_bytes, ext):
    nim = p['nim'] or f"id_{p['id']}"
    filename = f"{nim}{ext}"
    target_path = os.path.join(TARGET_DIR, filename)
    with open(target_path, 'wb') as f:
        f.write(img_bytes)
    os.chmod(target_path, 0o775)
    rel_path = f"wisudawan-photos/{filename}"
    newly_matched[p['id']] = {
        'id': p['id'],
        'nim': p['nim'],
        'nama': p['nama'],
        'instansi': p['instansi'],
        'filename': filename,
        'foto_path': rel_path
    }
    print(f"  [MATCHED!] {p['nim']} - {p['nama']} ({p['instansi']}) -> {filename}")

# Target root folder
ROOT_FOLDER_ID = '1Eh9jAtAlQyVMMCCAg0qCo-edNVFFeQVw'
q = urllib.parse.quote(f"'{ROOT_FOLDER_ID}' in parents and trashed=false")
root_items = api_get(f"https://www.googleapis.com/drive/v3/files?q={q}&fields=files(id,name,mimeType)")['files']

for prodi_f in root_items:
    fname = prodi_f['name']
    if 'JUNI' in fname.upper():
        continue

    # Look for matching prodi folder
    matched_prodi_unmatched = [p for p in unmatched if any(k in p['instansi'].lower() for k in fname.lower().split() if len(k) > 3)]
    if not matched_prodi_unmatched:
        continue

    print(f"\nChecking Drive folder: {fname} (Unmatched in prodi: {len(matched_prodi_unmatched)})...")
    
    # Recursive search in this prodi folder
    def inspect_subfolder(fid, subname=""):
        fq = urllib.parse.quote(f"'{fid}' in parents and trashed=false")
        items = api_get(f"https://www.googleapis.com/drive/v3/files?q={fq}&fields=files(id,name,mimeType,size)")['files']
        
        for it in items:
            if it['mimeType'] == 'application/vnd.google-apps.folder':
                if 'JUNI' not in it['name'].upper():
                    inspect_subfolder(it['id'], subname + "/" + it['name'])
                continue

            name = it['name']
            mime = it['mimeType']
            file_id = it['id']

            # Extract NIM / Name candidate from filename
            nim_match = re.search(r'P17\d{8,11}', name, re.IGNORECASE)
            nim_cand = nim_match.group(0).upper() if nim_match else None
            clean_name = os.path.splitext(name)[0]
            clean_name = re.sub(r'^\d+[\s\.\-_]+', '', clean_name)
            clean_name = re.sub(r'PPT.*Wisuda.*', '', clean_name, flags=re.IGNORECASE).strip()

            p = find_candidate(nim_cand, clean_name, fname)

            # If image file and candidate found
            if mime.startswith('image/') or name.lower().endswith(('.jpg', '.jpeg', '.png', '.webp')):
                if p and p['id'] not in newly_matched:
                    try:
                        b = download_file(file_id)
                        ext = os.path.splitext(name)[1].lower() or '.png'
                        save_matched_image(p, b, ext)
                    except Exception as e:
                        print(f"    [!] Error downloading {name}: {e}")

            # If PDF file
            elif name.lower().endswith('.pdf') or mime == 'application/pdf':
                # If not matched from filename, try pdftotext
                try:
                    pdf_bytes = download_file(file_id)
                    with tempfile.NamedTemporaryFile(suffix='.pdf', delete=False) as tf:
                        tf.write(pdf_bytes)
                        tf_path = tf.name

                    # Page count
                    try:
                        info_out = subprocess.check_output(['pdfinfo', tf_path], stderr=subprocess.DEVNULL).decode('utf-8', errors='ignore')
                        m = re.search(r'Pages:\s+(\d+)', info_out)
                        pages = int(m.group(1)) if m else 1
                    except Exception:
                        pages = 1

                    if pages <= 3:
                        if not p:
                            try:
                                text = subprocess.check_output(['pdftotext', tf_path, '-'], stderr=subprocess.DEVNULL).decode('utf-8', errors='ignore')
                                nim_m = re.search(r'P17\d{8,11}', text, re.IGNORECASE)
                                if nim_m:
                                    p = find_candidate(nim_m.group(0).upper(), None, fname)
                                if not p:
                                    for l in text.split('\n'):
                                        l = l.strip()
                                        if len(l) > 3:
                                            p = find_candidate(None, l, fname)
                                            if p: break
                            except Exception:
                                pass

                        if p and p['id'] not in newly_matched:
                            with tempfile.TemporaryDirectory() as img_tmp:
                                prefix = os.path.join(img_tmp, 'out')
                                subprocess.run(['pdfimages', '-png', tf_path, prefix], stderr=subprocess.DEVNULL)
                                imgs = [os.path.join(img_tmp, f) for f in os.listdir(img_tmp) if f.startswith('out-') and f.endswith('.png')]
                                if imgs:
                                    imgs.sort(key=lambda x: os.path.getsize(x), reverse=True)
                                    if os.path.getsize(imgs[0]) > 10000:
                                        with open(imgs[0], 'rb') as f_img:
                                            save_matched_image(p, f_img.read(), '.png')
                    os.remove(tf_path)
                except Exception as e:
                    print(f"    [!] Error processing PDF {name}: {e}")

    inspect_subfolder(prodi_f['id'], fname)

print(f"\nTargeted scan completed! Newly matched: {len(newly_matched)}")

# Update database
if newly_matched:
    print(f"Updating database for {len(newly_matched)} participants...")
    update_cmd = r"""php -r '
    require "vendor/autoload.php";
    $app = require_once "bootstrap/app.php";
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $matched = json_decode(file_get_contents("php://stdin"), true);
    $updated = 0;
    foreach ($matched as $pid => $item) {
        $p = App\Models\UnitEventParticipant::find($pid);
        if ($p) {
            $p->foto_path = $item["foto_path"];
            $meta = $p->meta ?? [];
            $meta["foto_path"] = $item["foto_path"];
            $p->meta = $meta;
            $p->saveQuietly();
            $updated++;
        }
    }
    echo "Updated $updated participants in database.\n";
    '"""
    p = subprocess.Popen(update_cmd, shell=True, stdin=subprocess.PIPE, stdout=subprocess.PIPE, cwd='/www/wwwroot/polkesban.ac.id')
    out, _ = p.communicate(input=json.dumps(newly_matched).encode('utf-8'))
    print(out.decode('utf-8'))

print("All done!")
