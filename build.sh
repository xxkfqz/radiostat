#!/bin/bash

set -e

# To be filled from env:
#
# USER=''
# PASS=''

DIR=$(dirname "$0")
OUTDIR="$DIR/output"
DB="$DIR/stats.db"

php -f "$DIR/src/stat.php" "$DB"

if [ -d "$OUTDIR" ]; then
    rm -rf "$OUTDIR"
fi

mkdir -p "$OUTDIR"

# From https://github.com/wilsonzlin/minify-html
minhtml --minify-js --minify-css < "$DIR/html/index.html" > "$OUTDIR/index.html"
php -f "$DIR/html/radiostat.phtml" "$DB" | minhtml --minify-js --minify-css > "$OUTDIR/radiostat.html"

cd "$OUTDIR"

# From https://github.com/ge3224/neocities
NEOCITIES_USER="$USER" NEOCITIES_PASS="$PASS" neocities_cli upload "index.html" "radiostat.html"
