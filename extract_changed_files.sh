#!/bin/bash
# ==============================================================================
# Script untuk mengumpulkan semua file yang sudah direvisi menjadi SATU file zip.
#
# CARA PAKAI:
#   1. Upload script ini (extract_changed_files.sh) dan file CHANGED_FILES.txt
#      ke ROOT folder project ucm-venue di server (folder yang berisi folder
#      "application" dan "db").
#   2. Jalankan lewat SSH:
#        bash extract_changed_files.sh
#   3. Hasilnya: file "ucm-venue-changed-files.zip" akan muncul di folder yang
#      sama, isinya semua file yang sudah direvisi (struktur folder sama persis
#      dengan project asli, tinggal di-extract/upload ulang ke tempatnya).
#
# CATATAN:
#   - Script ini HANYA MENGUMPULKAN (mengcopy) file yang sudah ada di server -
#     bukan mengunduh dari internet, jadi aman dijalankan langsung di server
#     production untuk keperluan backup sebelum deploy revisi baru.
#   - Kalau ada file di CHANGED_FILES.txt yang tidak ditemukan di server, script
#     akan memberi peringatan (bukan berhenti total) supaya sisanya tetap
#     diproses.
# ==============================================================================

set -e

LIST_FILE="CHANGED_FILES.txt"
OUTPUT_ZIP="ucm-venue-changed-files.zip"
STAGING_DIR="$(mktemp -d)"

if [ ! -f "$LIST_FILE" ]; then
  echo "ERROR: File '$LIST_FILE' tidak ditemukan di folder ini."
  echo "Pastikan CHANGED_FILES.txt ada di folder yang sama dengan script ini."
  exit 1
fi

echo "Mengumpulkan file ke folder sementara: $STAGING_DIR"
echo ""

MISSING=0
FOUND=0

while IFS= read -r filepath; do
  # Lewati baris kosong
  [ -z "$filepath" ] && continue

  if [ -f "$filepath" ]; then
    mkdir -p "$STAGING_DIR/$(dirname "$filepath")"
    cp "$filepath" "$STAGING_DIR/$filepath"
    echo "  [OK]      $filepath"
    FOUND=$((FOUND+1))
  else
    echo "  [HILANG]  $filepath  <-- tidak ditemukan, dilewati"
    MISSING=$((MISSING+1))
  fi
done < "$LIST_FILE"

echo ""
echo "Total ditemukan : $FOUND file"
echo "Total hilang    : $MISSING file"
echo ""

if [ "$FOUND" -eq 0 ]; then
  echo "ERROR: Tidak ada satu pun file yang ditemukan. Pastikan script ini"
  echo "dijalankan dari ROOT folder project (folder yang berisi 'application/')."
  rm -rf "$STAGING_DIR"
  exit 1
fi

rm -f "$OUTPUT_ZIP"
(cd "$STAGING_DIR" && zip -r -q "$OLDPWD/$OUTPUT_ZIP" .)

rm -rf "$STAGING_DIR"

echo "Selesai! Zip tersimpan di: $(pwd)/$OUTPUT_ZIP"
