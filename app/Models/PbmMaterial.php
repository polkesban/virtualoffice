<?php

namespace App\Models;

use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Model;

class PbmMaterial extends Model
{
    protected $guarded = ['id'];

    public function clinicalSession()
    {
        return $this->belongsTo(ClinicalSession::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getEffectiveUrlAttribute(): ?string
    {
        if (!empty($this->file_path)) {
            return MediaStorage::url($this->file_path);
        }
        return $this->url;
    }

    public function getEmbedUrlAttribute(): ?string
    {
        $targetUrl = $this->effective_url ?? $this->url;

        // 1. Uploaded files on our system (uses native local viewer / stream without 3rd-party CSP blocking)
        if (!empty($this->file_path)) {
            $ext = strtolower(pathinfo(MediaStorage::fileName($this->file_path) ?: '', PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                return route('pbm.materials.viewer', $this->id, false);
            }
            if (in_array($ext, ['mp4', 'webm', 'mov', 'mkv'])) {
                return route('pbm.materials.stream', $this->id, false);
            }
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                return route('pbm.materials.stream', $this->id, false);
            }
            if (in_array($ext, ['ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx'])) {
                return 'https://docs.google.com/viewer?url=' . urlencode(route('pbm.materials.stream', $this->id)) . '&embedded=true';
            }
        }

        if (empty($targetUrl)) {
            return null;
        }

        // 2. Google Drive from URL (uc?export=view&id=ID, open?id=ID, uc?id=ID, file/d/ID)
        if (str_contains($targetUrl, 'drive.google.com')) {
            if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $targetUrl, $m)) {
                return 'https://drive.google.com/file/d/' . $m[1] . '/preview';
            }
            if (preg_match('~drive\.google\.com/file/d/([a-zA-Z0-9_-]+)~', $targetUrl, $m)) {
                return 'https://drive.google.com/file/d/' . $m[1] . '/preview';
            }
        }

        // 3. Google Docs / Slides / Sheets
        if (preg_match('~docs\.google\.com/presentation/d/([a-zA-Z0-9_-]+)~', $targetUrl, $m)) {
            return 'https://docs.google.com/presentation/d/' . $m[1] . '/embed';
        }
        if (preg_match('~docs\.google\.com/document/d/([a-zA-Z0-9_-]+)~', $targetUrl, $m)) {
            return 'https://docs.google.com/document/d/' . $m[1] . '/preview';
        }
        if (preg_match('~docs\.google\.com/spreadsheets/d/([a-zA-Z0-9_-]+)~', $targetUrl, $m)) {
            return 'https://docs.google.com/spreadsheets/d/' . $m[1] . '/preview';
        }

        // 4. YouTube
        if (str_contains($targetUrl, 'youtube.com/watch')) {
            parse_str(parse_url($targetUrl, PHP_URL_QUERY), $vars);
            if (isset($vars['v'])) {
                return 'https://www.youtube.com/embed/' . $vars['v'];
            }
        } elseif (str_contains($targetUrl, 'youtu.be/')) {
            return 'https://www.youtube.com/embed/' . basename(parse_url($targetUrl, PHP_URL_PATH));
        }

        // 5. Canva Presentation links
        if (str_contains($targetUrl, 'canva.com/design/')) {
            return rtrim(explode('?', $targetUrl)[0], '/') . '/view?embed';
        }

        // 6. Office Documents (PPT, PPTX, DOC, DOCX, XLS, XLSX) via Google Docs Viewer
        if (preg_match('/\.(ppt|pptx|doc|docx|xls|xlsx)(\?.*)?$/i', $targetUrl)) {
            $fullPublicUrl = str_starts_with($targetUrl, 'http') ? $targetUrl : url($targetUrl);
            return 'https://docs.google.com/viewer?url=' . urlencode($fullPublicUrl) . '&embedded=true';
        }

        return $targetUrl;
    }

    public function getEmbedTypeAttribute(): string
    {
        $url = $this->embed_url ?? $this->effective_url ?? $this->url ?? '';

        if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
            return 'youtube';
        }

        if (preg_match('/\.(mp4|webm|mov|mkv|ogg)(\?.*)?$/i', $url)) {
            return 'video';
        }

        if (preg_match('/\.(jpg|jpeg|png|webp|gif)(\?.*)?$/i', $url)) {
            return 'image';
        }

        if ($this->type === 'video') {
            return 'video';
        }

        return 'document';
    }
}
