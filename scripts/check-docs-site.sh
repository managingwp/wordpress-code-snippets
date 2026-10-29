#!/usr/bin/env bash
# =================================================================================================
# check-docs-site.sh -- the publishable-or-not gate for a Docusaurus 3 docs site
# =================================================================================================
# Asserts the contract a docs site has to keep after the migration: guarded
# broken links, pinned and locked dependencies, front matter, one sidebar that
# resolves both ways, relative links, a slim README, balanced generated markers,
# and a Pages workflow that can authenticate.
#
# Read-only and offline. Exit 0 when every check passes, 1 when any fails, 2 on
# bad usage. Copy it into the repository it checks.
# =================================================================================================

set -uo pipefail

SITE=""
REPO_ROOT=""
DOCS=""
README=""
MAX_README=90
SKIP_SEARCH=0
QUIET=0
PASS=0
FAIL=0

usage () {
	cat <<'EOF'
Usage: check-docs-site.sh --site <dir> [options]

Options:
  --site <dir>            Docusaurus site directory (required)
  --repo-root <dir>       Repository root; default: git root of --site, else its parent
  --docs <dir>            Content directory; default: <site>/docs
  --readme <file>         README to check; default: <repo-root>/README.md
  --max-readme-lines <n>  README line budget; default: 90
  --skip-search           Do not require client-side search (Algolia, or none)
  --quiet                 Print failures only
  -h, --help              Show this help

Exit: 0 every check passed, 1 any check failed, 2 bad usage.
EOF
}

while (( $# )); do
	case "$1" in
		--site)             shift; SITE="${1:-}" ;;
		--repo-root)        shift; REPO_ROOT="${1:-}" ;;
		--docs)             shift; DOCS="${1:-}" ;;
		--readme)           shift; README="${1:-}" ;;
		--max-readme-lines) shift; MAX_README="${1:-90}" ;;
		--skip-search)      SKIP_SEARCH=1 ;;
		--quiet)            QUIET=1 ;;
		-h|--help)          usage; exit 0 ;;
		*) echo "check-docs-site: unknown option: $1" >&2; usage >&2; exit 2 ;;
	esac
	shift
done

[[ -n $SITE ]] || { echo "check-docs-site: --site is required" >&2; exit 2; }
[[ -d $SITE ]] || { echo "check-docs-site: no such site directory: $SITE" >&2; exit 2; }
SITE=$(cd "$SITE" && pwd)

if [[ -z $REPO_ROOT ]]; then
	REPO_ROOT=$(git -C "$SITE" rev-parse --show-toplevel 2>/dev/null || dirname "$SITE")
fi
[[ -d $REPO_ROOT ]] || { echo "check-docs-site: no such repo root: $REPO_ROOT" >&2; exit 2; }
REPO_ROOT=$(cd "$REPO_ROOT" && pwd)

DOCS=${DOCS:-$SITE/docs}
README=${README:-$REPO_ROOT/README.md}

ok () { PASS=$(( PASS + 1 )); (( QUIET )) || printf 'ok   %s\n' "$1"; }
bad () { FAIL=$(( FAIL + 1 )); printf 'FAIL %s: %s\n' "$1" "$2"; }

TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT

# -- first file that matches, in a fixed order
pick () { # <dir> <basename...>
	local dir="$1"; shift
	local base
	for base in "$@"; do
		for ext in js ts mjs cjs; do
			[[ -f "$dir/$base.$ext" ]] && { printf '%s\n' "$dir/$base.$ext"; return 0; }
		done
	done
	return 1
}

CONFIG=$(pick "$SITE" docusaurus.config) || true

# =================================================================================================
# -- config
# =================================================================================================
if [[ -z ${CONFIG:-} ]]; then
	bad config "no docusaurus.config.{js,ts,mjs,cjs} in $SITE"
else
	grep -qE "^[[:space:]]*url:[[:space:]]*['\"][^'\"]+['\"]" "$CONFIG" \
		&& ok config-url || bad config-url "url: is missing or empty"
	grep -qE "^[[:space:]]*baseUrl:[[:space:]]*['\"][^'\"]+['\"]" "$CONFIG" \
		&& ok config-baseurl || bad config-baseurl "baseUrl: is missing or empty"
	grep -qE "onBrokenLinks:[[:space:]]*['\"]throw['\"]" "$CONFIG" \
		&& ok config-broken-links || bad config-broken-links "onBrokenLinks is not 'throw'"
	grep -qE "onBrokenAnchors:[[:space:]]*['\"]throw['\"]" "$CONFIG" \
		&& ok config-broken-anchors || bad config-broken-anchors "onBrokenAnchors is not 'throw' (a warning ships dead #anchors)"
	grep -qE "onBrokenMarkdownLinks:[[:space:]]*['\"]throw['\"]" "$CONFIG" \
		&& ok config-broken-md-links || bad config-broken-md-links "markdown.hooks.onBrokenMarkdownLinks is not 'throw'"
fi

# =================================================================================================
# -- dependencies: pinned, locked, ignored
# =================================================================================================
PKG="$SITE/package.json"

spec () { # <package name>
	tr -d '\n' < "$PKG" \
		| grep -oE "\"$1\"[[:space:]]*:[[:space:]]*\"[^\"]*\"" \
		| head -n 1 | sed -E 's/.*:[[:space:]]*"([^"]*)"/\1/'
}

if [[ ! -f $PKG ]]; then
	bad package-json "no package.json in $SITE"
else
	for dep in '@docusaurus/core' '@docusaurus/preset-classic'; do
		got=$(spec "$dep")
		if [[ -z $got ]]; then
			bad "pinned:${dep##*/}" "$dep is not a dependency"
		elif [[ $got =~ ^[0-9]+\.[0-9]+\.[0-9]+ ]]; then
			ok "pinned:${dep##*/}"
		else
			bad "pinned:${dep##*/}" "$dep is '$got', not an exact version"
		fi
	done
fi

[[ -f "$SITE/package-lock.json" ]] && ok lockfile \
	|| bad lockfile "no package-lock.json in $SITE (npm ci in CI needs one)"

IGNORES="$TMP/ignores"
: > "$IGNORES"
for f in "$SITE/.gitignore" "$REPO_ROOT/.gitignore"; do
	[[ -f $f ]] && cat "$f" >> "$IGNORES"
done
for entry in node_modules build .docusaurus; do
	if grep -qE "^/?${entry}/?[[:space:]]*$" "$IGNORES"; then
		ok "ignore:$entry"
	else
		bad "ignore:$entry" "'$entry' is not in a .gitignore"
	fi
done

# =================================================================================================
# -- pages: front matter, sidebar both ways, links
# =================================================================================================
[[ -d $DOCS ]] || bad docs-dir "no content directory at $DOCS"

PAGES="$TMP/pages"
if [[ -d $DOCS ]]; then
	find "$DOCS" -type f \( -name '*.md' -o -name '*.mdx' \) \
		-not -path '*/_*' | sort > "$PAGES"
else
	: > "$PAGES"
fi

while IFS= read -r page; do
	rel=${page#"$DOCS"/}
	if [[ "$(head -n 1 "$page")" != "---" ]]; then
		bad "frontmatter:$rel" "no front matter block"
		continue
	fi
	awk 'NR == 1 { next } /^---[[:space:]]*$/ { exit } { print }' "$page" > "$TMP/fm"
	if grep -qE '^title:[[:space:]]*[^[:space:]]' "$TMP/fm"; then
		ok "title:$rel"
	else
		bad "title:$rel" "front matter has no title:"
	fi
	if grep -qE '^description:[[:space:]]*[^[:space:]]' "$TMP/fm"; then
		ok "description:$rel"
	else
		bad "description:$rel" "front matter has no description:"
	fi
done < "$PAGES"

SIDEBAR=$(pick "$SITE" sidebars) || true
DOC_IDS="$TMP/doc-ids"
: > "$DOC_IDS"
while IFS= read -r page; do
	rel=${page#"$DOCS"/}
	rel=${rel%.*}
	printf '%s\n' "$rel" >> "$DOC_IDS"
done < "$PAGES"

if [[ -z ${SIDEBAR:-} ]]; then
	bad sidebar "no sidebars.{js,ts,mjs,cjs} in $SITE"
else
	# -- quoted strings that look like doc ids, minus the Docusaurus vocabulary
	grep -oE "['\"][A-Za-z0-9._/-]+['\"]" "$SIDEBAR" | tr -d "'\"" \
		| grep -E '^[a-z0-9._/-]+$' \
		| grep -vE '^(type|category|doc|link|generated-index|autogenerated|dirName|slug|label|id|href|items|className|collapsible|collapsed|description|keywords|sidebar|position|displayName)$' \
		| grep -vE '\.(js|ts|json|css)$|^/|^#|://' | sort -u > "$TMP/sidebar-ids"

	while IFS= read -r id; do
		if grep -qxF "$id" "$TMP/sidebar-ids" \
			|| { [[ $id == */index ]] && grep -qxF "${id%/index}" "$TMP/sidebar-ids"; } \
			|| { [[ $id != */index ]] && grep -qxF "$id/index" "$TMP/sidebar-ids"; }; then
			ok "sidebar:$id"
		else
			bad "orphan:$id" "page is not registered in $SIDEBAR"
		fi
	done < "$DOC_IDS"

	while IFS= read -r id; do
		if [[ -f "$DOCS/$id.md" || -f "$DOCS/$id.mdx" \
			|| -f "$DOCS/$id/index.md" || -f "$DOCS/$id/index.mdx" ]]; then
			ok "sidebar-id:$id"
			continue
		fi
		if [[ -e "$DOCS/$id" ]]; then
			ok "sidebar-id:$id"
		else
			bad "sidebar-dangling:$id" "sidebar entry resolves to no file in $DOCS"
		fi
	done < "$TMP/sidebar-ids"
fi

while IFS= read -r page; do
	rel=${page#"$DOCS"/}
	dir=$(dirname "$page")
	awk '/^[[:space:]]*(```|~~~)/ { fence = !fence; next }
	     !fence {
	       line = $0
	       while (match(line, /\]\([^)]*\)/)) {
	         print FNR "\t" substr(line, RSTART + 2, RLENGTH - 3)
	         line = substr(line, RSTART + RLENGTH)
	       }
	     }' "$page" > "$TMP/links"
	while IFS=$'\t' read -r line target; do
		[[ -n ${target:-} ]] || continue
		case "$target" in
			http://*|https://*|mailto:*|\#*) continue ;;
			/*)
				bad "absolute-link:$rel:$line" "link '$target' ignores baseUrl"
				continue ;;
		esac
		path=${target%%#*}
		path=${path%%\?*}
		[[ -z $path ]] && continue
		if [[ $path == @site/* ]]; then
			resolved="$SITE/${path#@site/}"
			[[ -e $resolved ]] && ok "link:$rel:$line" \
				|| bad "link:$rel:$line" "'$target' does not resolve"
			continue
		fi
		if [[ -e "$dir/$path" || -e "$dir/$path.md" || -e "$dir/$path.mdx" \
			|| -e "$dir/$path/index.md" || -e "$dir/$path/index.mdx" ]]; then
			ok "link:$rel:$line"
		else
			bad "link:$rel:$line" "'$target' does not resolve"
		fi
	done < "$TMP/links"
done < "$PAGES"

# =================================================================================================
# -- generated markers
# =================================================================================================
markers () { # <file> <label>
	local begin end
	begin=$(grep -oE 'BEGIN GENERATED:[[:space:]]*[A-Za-z0-9._-]+' "$1" | sed 's/.*:[[:space:]]*//' | sort)
	end=$(grep -oE 'END GENERATED:[[:space:]]*[A-Za-z0-9._-]+' "$1" | sed 's/.*:[[:space:]]*//' | sort)
	if [[ $begin == "$end" ]]; then
		ok "markers:$2"
	else
		bad "markers:$2" "BEGIN/END GENERATED pairs do not match"
	fi
}

if [[ -f $README ]]; then
	lines=$(wc -l < "$README")
	if (( lines <= MAX_README )); then
		ok readme-length
	else
		bad readme-length "$README is $lines lines, budget is $MAX_README"
	fi
	markers "$README" README
	b=$(grep -c '<!-- BEGIN docs-index -->' "$README" || true)
	e=$(grep -c '<!-- END docs-index -->' "$README" || true)
	[[ $b -eq 1 && $e -eq 1 ]] && ok readme-pointer-block \
		|| bad readme-pointer-block "expected one docs-index marker pair, found $b/$e"
else
	bad readme "no README at $README"
fi

while IFS= read -r page; do
	if grep -q 'BEGIN GENERATED:' "$page"; then
		markers "$page" "${page#"$DOCS"/}"
	fi
done < "$PAGES"

# =================================================================================================
# -- client-side search
# =================================================================================================
SEARCH_PKG='@easyops-cn/docusaurus-search-local'

if (( SKIP_SEARCH )); then
	ok search-skipped
elif [[ -f $PKG ]]; then
	got=$(spec "$SEARCH_PKG")
	if [[ -z $got ]]; then
		bad search-dependency "$SEARCH_PKG is not a dependency"
	else
		ok search-dependency
		if [[ $got =~ ^[0-9]+\.[0-9]+\.[0-9]+ ]]; then
			ok search-pinned
		else
			bad search-pinned "$SEARCH_PKG is '$got', not an exact version"
		fi
	fi

	if [[ -n ${CONFIG:-} ]] && grep -q "$SEARCH_PKG" "$CONFIG"; then
		ok search-themes
	else
		bad search-themes "the plugin is not registered in a top-level themes entry"
	fi

	if [[ -n ${CONFIG:-} ]]; then
		route=$(grep -oE "(^|[^A-Za-z])routeBasePath:[[:space:]]*['\"][^'\"]*['\"]" "$CONFIG" \
			| head -n 1 | sed -E "s/.*['\"]([^'\"]*)['\"]$/\1/")
		droute=$(grep -oE "docsRouteBasePath:[[:space:]]*['\"][^'\"]*['\"]" "$CONFIG" \
			| head -n 1 | sed -E "s/.*['\"]([^'\"]*)['\"]$/\1/")
		route=${route:-docs}
		droute=${droute:-docs}
		nr=${route#/}; nr=${nr%/}
		nd=${droute#/}; nd=${nd%/}
		if [[ $nr == "$nd" ]]; then
			ok search-base-path
		else
			bad search-base-path "docsRouteBasePath '$droute' != docs.routeBasePath '$route' (results will 404)"
		fi
	fi

	if [[ -f "$SITE/package-lock.json" ]] && grep -q "$SEARCH_PKG" "$SITE/package-lock.json"; then
		ok search-locked
	else
		bad search-locked "package-lock.json has no record of $SEARCH_PKG"
	fi

	if [[ -d "$SITE/build" ]]; then
		# The local search plugin writes the index to the docs version outDir, which
		# is the build root on a docs-only site with routeBasePath '/'.
		if find "$SITE/build" -maxdepth 2 -name 'search-index*' -print -quit | grep -q .; then
			ok search-index
		else
			bad search-index "no search-index file under $SITE/build"
		fi
	fi
fi

# =================================================================================================
# -- Pages workflow
# =================================================================================================
WORKFLOWS="$TMP/workflows"
: > "$WORKFLOWS"
for f in "$REPO_ROOT"/.github/workflows/*.yml "$REPO_ROOT"/.github/workflows/*.yaml; do
	[[ -f $f ]] && grep -q 'actions/deploy-pages' "$f" && printf '%s\n' "$f" >> "$WORKFLOWS"
done

if [[ ! -s $WORKFLOWS ]]; then
	bad workflow "no workflow under .github/workflows uses actions/deploy-pages"
else
	xargs cat < "$WORKFLOWS" > "$TMP/workflow"
	grep -qE 'pages:[[:space:]]*write' "$TMP/workflow" && ok workflow-pages-write \
		|| bad workflow-pages-write "no 'pages: write' permission"
	grep -qE 'id-token:[[:space:]]*write' "$TMP/workflow" && ok workflow-id-token \
		|| bad workflow-id-token "no 'id-token: write' permission"
	grep -qE 'run:[[:space:]]*npm ci' "$TMP/workflow" && ok workflow-npm-ci \
		|| bad workflow-npm-ci "the workflow does not run 'npm ci'"
	grep -q 'actions/upload-pages-artifact' "$TMP/workflow" && ok workflow-artifact \
		|| bad workflow-artifact "no actions/upload-pages-artifact step"
fi

# =================================================================================================
# -- summary
# =================================================================================================
if (( FAIL > 0 )); then
	printf '\n%d passed, %d failed\n' "$PASS" "$FAIL" >&2
	exit 1
fi
(( QUIET )) || printf '\n%d checks passed\n' "$PASS"
exit 0
