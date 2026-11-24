#!/usr/bin/env sh
set -eu

ROOT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
STAGE_DIR="$(mktemp -d)"
PKG_DIR="$STAGE_DIR/pa-sinova-starter"
OUT_ZIP="$ROOT_DIR/dist/pa-sinova-starter.zip"

trap 'rm -rf "$STAGE_DIR"' EXIT

mkdir -p "$PKG_DIR/wordpress-mu-plugins/assets" "$PKG_DIR/wordpress-templates" "$ROOT_DIR/dist"

cp "$ROOT_DIR/wordpress-plugin-wrapper/pa-sinova-starter.php" "$PKG_DIR/pa-sinova-starter.php"
cp "$ROOT_DIR/wordpress-mu-plugins/"*.php "$PKG_DIR/wordpress-mu-plugins/"
cp "$ROOT_DIR/wordpress-mu-plugins/assets/cctv-aco.gif" "$PKG_DIR/wordpress-mu-plugins/assets/"
cp "$ROOT_DIR/wordpress-templates/beranda-blocks-starter.html" "$PKG_DIR/wordpress-templates/"
cp "$ROOT_DIR/version.json" "$ROOT_DIR/LICENSE" "$ROOT_DIR/README.md" "$PKG_DIR/"

rm -f "$OUT_ZIP"
(cd "$STAGE_DIR" && zip -qr "$OUT_ZIP" pa-sinova-starter)

echo "Paket plugin berhasil dibuat: $OUT_ZIP"
ls -lh "$OUT_ZIP"
