#!/usr/bin/env bash

set -euo pipefail

BASE_URL="${1:-${APP_URL:-https://polkesban.ac.id}}"
BASE_URL="${BASE_URL%/}"

pass() {
    printf 'PASS %s\n' "$1"
}

fail() {
    printf 'FAIL %s\n' "$1" >&2
    exit 1
}

check_page_contains() {
    local label="$1"
    local path="$2"
    local marker="$3"
    local body_file
    local status

    body_file="$(mktemp)"

    status="$(curl -ksS -o "$body_file" -w '%{http_code}' "$BASE_URL$path")" || {
        rm -f "$body_file"
        fail "$label (request failed)"
    }

    if [[ "$status" != "200" ]]; then
        rm -f "$body_file"
        fail "$label (expected HTTP 200, got $status)"
    fi

    if ! grep -Fq "$marker" "$body_file"; then
        rm -f "$body_file"
        fail "$label (missing marker: $marker)"
    fi

    rm -f "$body_file"
    pass "$label"
}

check_redirect_target() {
    local label="$1"
    local path="$2"
    local expected_path="$3"
    local result
    local status
    local final_url

    result="$(curl -ksS -L -o /dev/null -w '%{http_code} %{url_effective}' "$BASE_URL$path")" || fail "$label (request failed)"
    status="${result%% *}"
    final_url="${result#* }"

    if [[ "$status" != "200" ]]; then
        fail "$label (expected final HTTP 200, got $status)"
    fi

    if [[ "$final_url" != "$BASE_URL$expected_path" ]]; then
        fail "$label (expected redirect target $BASE_URL$expected_path, got $final_url)"
    fi

    pass "$label"
}

printf 'Smoke check base URL: %s\n' "$BASE_URL"

check_page_contains "Homepage renders" "/" "Berita Terbaru"
check_page_contains "LAKIP page renders" "/unit/bidang-administrasi-umum/lakip" "LAKIP 2025"
check_page_contains "Business order page renders" "/layanan-bisnis/pesan" "Form Pemesanan Layanan & Produk"
check_page_contains "Admin login renders" "/admin/login" "Captcha"
check_redirect_target "Protected evidence route redirects to admin login" "/admin/eviden-akreditasi" "/admin/login"