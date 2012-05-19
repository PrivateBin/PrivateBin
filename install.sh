#!/bin/sh -eu

myname="$(readlink -ev "$0")"
compiler='uglifyjs/bin/uglifyjs'

SOURCES='cfg css img lib tpl index.php'
JSDIR='js'

if [ "$#" -eq 0 ]; then
	printf 'Usage: %s <destdir>\n' "${0##*/}"
	exit
fi

destdir="$1"
shift

if [ ! -d "$destdir" ]; then
	printf 'Error: %s: Not directory\n' "$destdir"
	exit 1
fi
destdir="$(readlink -ev "$destdir")"

cd "${myname%/*}"
cp -aurt "$destdir" -- $SOURCES

mkdir -p -- "$destdir/js"
for src in "$JSDIR"/*.js; do
	[ -f "$src" ] ||
		continue
	printf 'Processing %s ... ' "$src"

	rc='done'
	$compiler -nc -c -o "$destdir/js/${src##*/}" "$src" || rc='fail'

	printf '%s\n' "$rc"

	[ "$rc" = 'done' ] ||
		exit 1
done
