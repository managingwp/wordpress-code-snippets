#!/usr/bin/env bash
# Generate README.md, CHANGELOG.md and the Docusaurus docs from the snippet headers.
#
# Ownership:
#   README.md                           README-header.md + the generated docs index
#   CHANGELOG.md                        full commit log (not compared by --check)
#   website/docs/index.md               catalogue block
#   website/docs/history/changelog.md   released versions from git tags
#   website/docs/snippets/<cat>/*.md    one page per snippet (whole directory)
#   website/sidebars.ts                 snippet categories block
#
# Usage: ./generate-readme.sh [--check]
#   --check  generate into a temporary directory and diff; exit 1 when the
#            committed files are stale. A snippet without its docs fails it.

set -uo pipefail
export LC_ALL=C

REPO_SLUG="managingwp/wordpress-code-snippets"
REPO_URL="https://github.com/$REPO_SLUG"
BLOB_URL="$REPO_URL/blob/main"
SITE_URL="https://managingwp.github.io/wordpress-code-snippets"

SITE_DIR="website"
SNIPPETS_DIR="$SITE_DIR/docs/snippets"
README_TEMPLATE="README-header.md"
README_OUT="README.md"
CHANGELOG_OUT="CHANGELOG.md"
CATALOGUE_PAGE="$SITE_DIR/docs/index.md"
CHANGELOG_PAGE="$SITE_DIR/docs/history/changelog.md"
SIDEBARS_FILE="$SITE_DIR/sidebars.ts"

# Root directories that are not snippet categories.
EXCLUDED_DIRS=".git .github website scripts docs node_modules plans"

CHECK=0
for arg in "$@"; do
    case $arg in
        --check) CHECK=1 ;;
        -h|--help) sed -n '2,16p' "$0"; exit 0 ;;
        *) echo "Unknown option: $arg" >&2; exit 2 ;;
    esac
done

STAGE=$(mktemp -d)
trap 'rm -rf "$STAGE"' EXIT
if (( CHECK )); then
    OUT_ROOT="$STAGE/out"
    mkdir -p "$OUT_ROOT"
else
    OUT_ROOT="."
fi
out() { printf '%s/%s' "$OUT_ROOT" "$1"; }

# Initialize warning counters
MISSING_PLUGIN_NAME=0
MISSING_TYPE=0
MISSING_STATUS=0
MISSING_DESCRIPTION=0
MISSING_VERSION=0
TOTAL_FILES=0
TOTAL_CATEGORIES=0

# ---------------------------------------------------------------------------- helpers

# Read a header field from the leading docblock of a PHP file.
# Only the first match inside the docblock is used, so code further down the file
# (for example an echo containing "version:") can never be picked up.
# Falls back to the first match anywhere in the file when the docblock is missing.
header_value() { # <file> <key>
    local file=$1 key=$2 value
    value=$(awk -v key="$key" '
        NR == 1 { next }
        /\*\// { exit }
        {
            line = $0
            sub(/^[[:space:]]*\/?\*+[[:space:]]*/, "", line)
            if (index(line, key ":") == 1) {
                value = substr(line, length(key) + 2)
                sub(/^[[:space:]]+/, "", value)
                sub(/[[:space:]]+$/, "", value)
                print value
                exit
            }
        }' "$file")
    if [[ -z $value ]]; then
        value=$(grep -i -m 1 -E "^[[:space:]]*(/?\*+[[:space:]]*)?${key}:" "$file" \
            | sed -E "s/^[[:space:]]*(\/?\*+[[:space:]]*)?${key}:[[:space:]]*//")
    fi
    printf '%s' "$value"
}

# Escape a value so it cannot break a markdown table row.
table_cell() { printf '%s' "$1" | sed 's/|/\\|/g'; }

# Quote a value for YAML front matter.
yaml_quote() { printf '"%s"' "$(printf '%s' "$1" | sed 's/\\/\\\\/g; s/"/\\"/g')"; }

# Normalise a raw Status value for display.
status_label() { # <status>
    case "${1,,}" in
        complete) printf 'Complete' ;;
        broken)   printf 'Broken' ;;
        wip)      printf 'WIP' ;;
        *)        printf 'Unknown' ;;
    esac
}

# Map a normalised Status label to the emoji used in the tables.
status_emoji() { # <label>
    case "$1" in
        Complete) printf ':white_check_mark:' ;;
        Broken)   printf ':x:' ;;
        WIP)      printf ':construction:' ;;
        *)        printf ':question:' ;;
    esac
}

# Human label for a category directory.
category_label() { # <directory>
    case "$1" in
        acf)         printf 'ACF' ;;
        ajaxlog)     printf 'Ajax Log' ;;
        buddyboss)   printf 'BuddyBoss' ;;
        woocommerce) printf 'WooCommerce' ;;
        wp-failover) printf 'WP Failover' ;;
        *) printf '%s' "$1" | tr '-' ' ' \
            | awk '{ for (i = 1; i <= NF; i++) $i = toupper(substr($i, 1, 1)) substr($i, 2); print }' ;;
    esac
}

# Where a snippet goes, by Type.
install_note() { # <type>
    case "${1,,}" in
        mu-plugin) printf 'Copy the file into `wp-content/mu-plugins/`. It loads automatically and cannot be deactivated from the admin.' ;;
        plugin)    printf 'Copy the file into its own folder under `wp-content/plugins/`, then activate it under Plugins.' ;;
        theme)     printf 'Add the file to your theme or child theme and include it from `functions.php`.' ;;
        snippet)   printf 'Paste the contents into WP Codebox, Code Snippets, or another snippet manager. Drop the opening `<?php` tag when the manager adds it.' ;;
        script)    printf 'Include it from `functions.php` or from another file WordPress loads. It is not a plugin entry point on its own.' ;;
        *)         printf 'Copy the file to where its context expects it and load it from a file WordPress already loads.' ;;
    esac
}

# Replace the lines between two markers in <src> with the contents of <content>.
splice_to() { # <dest> <src> <begin marker> <end marker> <content file>
    local dest=$1 src=$2 begin=$3 end=$4 content=$5 tmp
    if ! grep -qF "$begin" "$src" || ! grep -qF "$end" "$src"; then
        echo "generate-readme: $src has no '$begin' / '$end' marker pair" >&2
        exit 1
    fi
    tmp="$STAGE/splice.out"
    awk -v begin="$begin" -v end="$end" -v content="$content" '
        index($0, begin) { print; inblock = 1
            while ((getline line < content) > 0) print line
            close(content); next }
        index($0, end) { inblock = 0 }
        !inblock { print }' "$src" > "$tmp"
    mkdir -p "$(dirname "$dest")"
    mv "$tmp" "$dest"
}

# ---------------------------------------------------------------------------- collect

META="$STAGE/meta.tsv"
: > "$META"

CATEGORIES=()
for DIRECTORY in */; do
    DIRECTORY_NAME=${DIRECTORY%/}
    if [[ " $EXCLUDED_DIRS " == *" $DIRECTORY_NAME "* ]]; then
        continue
    fi
    compgen -G "$DIRECTORY_NAME/*.php" > /dev/null 2>&1 || continue
    CATEGORIES+=("$DIRECTORY_NAME")
    TOTAL_CATEGORIES=$((TOTAL_CATEGORIES + 1))
    LABEL=$(category_label "$DIRECTORY_NAME")

    for FILE in "$DIRECTORY_NAME"/*.php; do
        FILE_NAME=$(basename "$FILE")
        TOTAL_FILES=$((TOTAL_FILES + 1))

        # -- Title
        TITLE=$(header_value "$FILE" "Plugin Name")
        if [[ -z "$TITLE" ]]; then
            TITLE="$FILE_NAME"
            echo "⚠️  Warning: No 'Plugin Name:' found in $FILE - using filename as title"
            MISSING_PLUGIN_NAME=$((MISSING_PLUGIN_NAME + 1))
        fi

        # -- Version
        VERSION=$(header_value "$FILE" "Version")
        if [[ ! $VERSION =~ ^[0-9][0-9A-Za-z.+_-]*$ ]]; then
            if [[ -n $VERSION ]]; then
                echo "⚠️  Warning: Unusable 'Version:' value '$VERSION' in $FILE"
            fi
            VERSION="Unknown"
            echo "⚠️  Warning: No 'Version:' found in $FILE"
            MISSING_VERSION=$((MISSING_VERSION + 1))
        fi

        # -- Type
        TYPE=$(header_value "$FILE" "Type")
        if [[ -z "$TYPE" ]]; then
            echo "⚠️  Warning: No 'Type:' found in $FILE"
            MISSING_TYPE=$((MISSING_TYPE + 1))
        fi

        # -- Status
        STATUS=$(header_value "$FILE" "Status")
        if [[ -z $STATUS ]]; then
            STATUS="Unknown"
            echo "⚠️  Warning: No 'Status:' found in $FILE"
            MISSING_STATUS=$((MISSING_STATUS + 1))
        fi
        STATUS=$(status_label "$STATUS")

        # -- Description
        DESCRIPTION=$(header_value "$FILE" "Description")
        if [[ -z "$DESCRIPTION" ]]; then
            DESCRIPTION="No description"
            echo "⚠️  Warning: No 'Description:' found in $FILE"
            MISSING_DESCRIPTION=$((MISSING_DESCRIPTION + 1))
        fi

        SLUG=$(basename "$FILE" .php)
        printf '%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\n' \
            "$DIRECTORY_NAME" "$LABEL" "$FILE" "$SLUG" "$TITLE" "$DESCRIPTION" \
            "$VERSION" "$TYPE" "$STATUS" >> "$META"
    done
done

# ---------------------------------------------------------------------------- snippet pages

GENERATED_PAGES=()
if (( ! CHECK )); then
    # The whole directory is generated, so pages for deleted snippets go too.
    rm -rf "$SNIPPETS_DIR"
fi

while IFS=$'\t' read -r CATEGORY LABEL FILE SLUG TITLE DESCRIPTION VERSION TYPE STATUS; do
    PAGE="$SNIPPETS_DIR/$CATEGORY/$SLUG.md"
    GENERATED_PAGES+=("$PAGE")
    mkdir -p "$(out "$(dirname "$PAGE")")"

    {
        echo '---'
        echo "title: $(yaml_quote "$TITLE")"
        echo "description: $(yaml_quote "$DESCRIPTION")"
        echo '---'
        echo ''
        echo '<!-- BEGIN GENERATED: snippet -->'
        echo 'Generated from the snippet header by `./generate-readme.sh`. Do not edit this file by hand.'
        echo ''
        echo '| Field | Value |'
        echo '| ----- | ----- |'
        echo "| Version | $(table_cell "$VERSION") |"
        echo "| Type | \`$(table_cell "$TYPE")\` |"
        echo "| Status | $(status_emoji "$STATUS") $(table_cell "$STATUS") |"
        echo "| Category | $LABEL |"
        echo "| Source | [\`$FILE\`]($BLOB_URL/$FILE) |"
        echo ''
        echo '## Install'
        echo ''
        echo "$(install_note "$TYPE")"
        echo ''
        echo 'See [installing a snippet](../../getting-started/install-a-snippet.md) for the folder'
        echo 'layout, and check the status before you deploy.'
        echo '<!-- END GENERATED: snippet -->'
    } > "$(out "$PAGE")"
done < "$META"

# ---------------------------------------------------------------------------- docs index

CATALOGUE="$STAGE/catalogue.md"
{
    printf '%s snippets in %s categories. Each page lists the version, type and status, and links\nto the source file.\n\n' \
        "$TOTAL_FILES" "$TOTAL_CATEGORIES"
} > "$CATALOGUE"

for CATEGORY in "${CATEGORIES[@]}"; do
    echo "### $(category_label "$CATEGORY")" >> "$CATALOGUE"
    echo '' >> "$CATALOGUE"
    echo '| Snippet | Version | Type | Status |' >> "$CATALOGUE"
    echo '| ------- | ------- | ---- | ------ |' >> "$CATALOGUE"
    while IFS=$'\t' read -r CAT LABEL FILE SLUG TITLE DESCRIPTION VERSION TYPE STATUS; do
        [[ $CAT == "$CATEGORY" ]] || continue
        printf '| [%s](./snippets/%s/%s.md) | %s | %s | %s |\n' \
            "$(table_cell "$TITLE")" "$CAT" "$SLUG" "$(table_cell "$VERSION")" \
            "$(table_cell "$TYPE")" "$(status_emoji "$STATUS")" >> "$CATALOGUE"
    done < "$META"
    echo '' >> "$CATALOGUE"
done

splice_to "$(out "$CATALOGUE_PAGE")" "$CATALOGUE_PAGE" '<!-- BEGIN GENERATED: catalogue -->' '<!-- END GENERATED: catalogue -->' "$CATALOGUE"

# ---------------------------------------------------------------------------- sidebar

SIDEBAR_BLOCK="$STAGE/sidebar.txt"
: > "$SIDEBAR_BLOCK"
for CATEGORY in "${CATEGORIES[@]}"; do
    ITEMS=""
    while IFS=$'\t' read -r CAT LABEL FILE SLUG TITLE DESCRIPTION VERSION TYPE STATUS; do
        [[ $CAT == "$CATEGORY" ]] || continue
        ITEMS="$ITEMS${ITEMS:+, }'snippets/$CAT/$SLUG'"
    done < "$META"
    printf "    {type: 'category', label: '%s', items: [%s]},\n" \
        "$(category_label "$CATEGORY")" "$ITEMS" >> "$SIDEBAR_BLOCK"
done

splice_to "$(out "$SIDEBARS_FILE")" "$SIDEBARS_FILE" '// BEGIN GENERATED: snippets' '// END GENERATED: snippets' "$SIDEBAR_BLOCK"

# ---------------------------------------------------------------------------- changelog page

mkdir -p "$(out "$(dirname "$CHANGELOG_PAGE")")"
{
    echo '---'
    echo 'title: Changelog'
    echo 'description: Every released version of the snippets, newest first.'
    echo '---'
    echo ''
    echo 'Releases are grouped by git tag. Commits since the last tag are on'
    echo "[GitHub]($REPO_URL/commits/main)."
    echo ''
    echo '<!-- BEGIN GENERATED: changelog -->'
    TAGS=()
    while IFS= read -r TAG; do
        [[ $TAG =~ ^v?[0-9] ]] && TAGS+=("$TAG")
    done < <(git tag --sort=-v:refname)
    for i in "${!TAGS[@]}"; do
        TAG=${TAGS[$i]}
        PREVIOUS=""
        if (( i + 1 < ${#TAGS[@]} )); then
            PREVIOUS=${TAGS[$((i + 1))]}
        fi
        echo "### $TAG ($(git log -1 --format=%cs "$TAG"))"
        echo ''
        if [[ -n $PREVIOUS ]]; then
            RANGE="$PREVIOUS..$TAG"
        else
            RANGE="$TAG"
        fi
        # Release commits carry the change list in their body; commits between tags
        # are the fallback when the body is empty.
        CHANGES=$(git log -1 --format=%b "$TAG" \
            | sed -E 's/^\([0-9a-f]{7,40}\)[[:space:]]*//; s/^\([^)]*\)[[:space:]]*//' \
            | awk 'NF { sub(/^[[:space:]]*[*+-][[:space:]]+/, ""); print "- " $0 }')
        if [[ -z $CHANGES ]]; then
            CHANGES=$(git log --no-merges --pretty='- %s (%h)' "$RANGE" \
                | grep -v -E '^- Release ' || true)
        fi
        if [[ -z $CHANGES ]]; then
            CHANGES='- Release only.'
        fi
        printf '%s\n' "$CHANGES" | sed 's/|/\\|/g'
        echo ''
    done
    echo '<!-- END GENERATED: changelog -->'
} > "$(out "$CHANGELOG_PAGE")"

# ---------------------------------------------------------------------------- README

DOCS_INDEX="$STAGE/docs-index.md"
{
    printf '%s snippets in %s categories, one page each.\n\n' "$TOTAL_FILES" "$TOTAL_CATEGORIES"
    echo '| Section | Where |'
    echo '| ------- | ----- |'
    printf '| Snippet catalogue | %s/ |\n' "$SITE_URL"
    printf '| Install a snippet | %s/getting-started/install-a-snippet |\n' "$SITE_URL"
    printf '| Snippet headers | %s/reference/snippet-headers |\n' "$SITE_URL"
    printf '| Add a snippet | %s/contributing/add-a-snippet |\n' "$SITE_URL"
    printf '| Changelog | %s/history/changelog |\n' "$SITE_URL"
} > "$DOCS_INDEX"

splice_to "$(out "$README_OUT")" "$README_TEMPLATE" '<!-- BEGIN docs-index -->' '<!-- END docs-index -->' "$DOCS_INDEX"

# ---------------------------------------------------------------------------- CHANGELOG.md

if (( ! CHECK )); then
    echo "# Changelog" > "$CHANGELOG_OUT"
    git log --pretty=format:"## %s%n%b%n" | sed '/^## /!{ /^[[:space:]]*$/!s/^/* /; }' >> "$CHANGELOG_OUT"
fi

# ---------------------------------------------------------------------------- check mode

if (( CHECK )); then
    FAILED=0
    for REL in "$README_OUT" "$CATALOGUE_PAGE" "$CHANGELOG_PAGE" "$SIDEBARS_FILE" "${GENERATED_PAGES[@]:-}"; do
        if ! cmp -s "$(out "$REL")" "$REL"; then
            printf 'stale: %s\n' "$REL"
            FAILED=1
        fi
    done
    for PAGE in "$SNIPPETS_DIR"/*/*.md; do
        [[ -e $PAGE ]] || continue
        if [[ " ${GENERATED_PAGES[*]:-} " != *" $PAGE "* ]]; then
            printf 'stale: %s (no snippet for this page)\n' "$PAGE"
            FAILED=1
        fi
    done
    if (( FAILED )); then
        echo ''
        echo 'Run ./generate-readme.sh and commit the result.'
        exit 1
    fi
    echo 'Generated files are up to date.'
    exit 0
fi

# ---------------------------------------------------------------------------- summary

echo ""
echo "📊 Generation Summary:"
echo "===================="
echo "Total PHP files processed: $TOTAL_FILES"
echo "Total categories: $TOTAL_CATEGORIES"
echo "Generated snippet pages: ${#GENERATED_PAGES[@]}"
echo "Files missing Plugin Name: $MISSING_PLUGIN_NAME"
echo "Files missing Version: $MISSING_VERSION"
echo "Files missing Type: $MISSING_TYPE"
echo "Files missing Status: $MISSING_STATUS"
echo "Files missing Description: $MISSING_DESCRIPTION"

if [[ $MISSING_PLUGIN_NAME -gt 0 || $MISSING_VERSION -gt 0 || $MISSING_TYPE -gt 0 || $MISSING_STATUS -gt 0 || $MISSING_DESCRIPTION -gt 0 ]]; then
    echo ""
    echo "🔧 To fix warnings, add the following to your PHP file headers:"
    echo "   * Plugin Name: Your Plugin Name"
    echo "   * Version: 1.0.0"
    echo "   * Type: plugin|theme|mu-plugin|snippet|script"
    echo "   * Status: Complete|WIP|Broken"
    echo "   * Description: Brief description of what this does"
else
    echo ""
    echo "✅ All files have complete metadata!"
fi
