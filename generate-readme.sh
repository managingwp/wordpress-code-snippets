#!/usr/bin/env bash
# Generate README.md
# Usage: ./generate-readme.sh

# Initialize warning counters
MISSING_PLUGIN_NAME=0
MISSING_TYPE=0
MISSING_STATUS=0
MISSING_DESCRIPTION=0
MISSING_VERSION=0
TOTAL_FILES=0

# Get all directories in the current directory
DIRECTORIES=$(find . -mindepth 1 -maxdepth 1 -type d)
README_SNIPPETS_FILE="README-snippets.md"

# Sort directories by name
DIRECTORIES=$(echo "$DIRECTORIES" | tr ' ' '\n' | sort)

# Generate $README_SNIPPETS_FILE
echo "# Minor Snippets" > $README_SNIPPETS_FILE
for DIRECTORY in $DIRECTORIES; do
    if [[ $DIRECTORY == "./.git" ]]; then
        continue
    fi
    DIRECTORY_NAME=$(basename $DIRECTORY)
    echo "## [$DIRECTORY_NAME]($DIRECTORY_NAME)" >> $README_SNIPPETS_FILE
    echo "" >> $README_SNIPPETS_FILE
    echo "| Title | Version | Type | Status | Description |" >> $README_SNIPPETS_FILE
    echo "| ----- | ------- | ---- | ------ | ----------- |" >> $README_SNIPPETS_FILE
    for FILE in $DIRECTORY/*.php; do
        FILE_NAME=$(basename $FILE)
        if [ "$FILE_NAME" != "README.md" ] && [ "$FILE_NAME" != "$README_SNIPPETS_FILE" ]; then
            TOTAL_FILES=$((TOTAL_FILES + 1))
            
            # -- Title
            TITLE=$(grep -i "Plugin Name:" "$FILE" | tail -n 1 | sed 's/ \* Plugin Name: //')
            if [[ -z "$TITLE" ]]; then
                TITLE="$FILE_NAME"
                echo "⚠️  Warning: No 'Plugin Name:' found in $FILE - using filename as title"
                MISSING_PLUGIN_NAME=$((MISSING_PLUGIN_NAME + 1))
            fi

            # -- Version
            VERSION=$(grep -i "Version:" "$FILE" | tail -n 1 | sed 's/ \* Version: //')
            if [[ -z "$VERSION" ]]; then
                VERSION="Unknown"
                echo "⚠️  Warning: No 'Version:' found in $FILE"
                MISSING_VERSION=$((MISSING_VERSION + 1))
            fi

            # -- Type
            TYPE=$(grep -i "Type:" "$FILE" | tail -n 1 | sed 's/ \* Type: //')
            if [[ -z "$TYPE" ]]; then
                echo "⚠️  Warning: No 'Type:' found in $FILE"
                MISSING_TYPE=$((MISSING_TYPE + 1))
            fi

            # -- Status
            STATUS=$(grep -i "Status:" "$FILE" | tail -n 1 | sed 's/ \* Status: //')
            if [[ -z $STATUS ]]; then
                STATUS="Unknown"
                echo "⚠️  Warning: No 'Status:' found in $FILE"
                MISSING_STATUS=$((MISSING_STATUS + 1))
            fi
            # -- Set Status emoji
            if [[ $STATUS == "Complete" ]]; then
                STATUS=":white_check_mark:"
            elif [[ $STATUS == "Broken" ]]; then
                STATUS=":x:"
            elif [[ $STATUS == "WIP" ]]; then
                STATUS=":construction:"
            else
                STATUS=":question:"
            fi
            
            # -- Description
            DESCRIPTION=$(grep " * Description" "$FILE" | tail -n 1 | sed 's/ \* Description: //')
            if [[ -z "$DESCRIPTION" ]]; then
                DESCRIPTION="No description"
                echo "⚠️  Warning: No 'Description:' found in $FILE"
                MISSING_DESCRIPTION=$((MISSING_DESCRIPTION + 1))
            fi

            # -- Set default values for plugins
            if [[ -z $TYPE && -n $TITLE ]]; then
                TYPE="Plugin"
                echo "ℹ️  Info: Setting default type 'Plugin' for $FILE"
            fi
            if [[ -z $TYPE && -z $TITLE ]]; then
                TYPE="Unknown"
                echo "⚠️  Warning: No type or title found for $FILE - setting both to 'Unknown'"
            fi
            
            echo "| [$TITLE]($FILE) | $VERSION | $TYPE | $STATUS | $DESCRIPTION |" >> $README_SNIPPETS_FILE
        fi
    done
    echo "" >> $README_SNIPPETS_FILE
done

# -- Generate CHANGELOG.md
echo "# Changelog" > CHANGELOG.md
git log --pretty=format:"## %s%n%b%n" | sed '/^## /!{ /^[[:space:]]*$/!s/^/* /; }' >> CHANGELOG.md

# Generate README.md
cat README-header.md $README_SNIPPETS_FILE CHANGELOG.md > README.md

# Print summary
echo ""
echo "📊 Generation Summary:"
echo "===================="
echo "Total PHP files processed: $TOTAL_FILES"
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
    echo "   * Type: mu-plugin|plugin|script|etc"
    echo "   * Status: Complete|WIP|Broken"
    echo "   * Description: Brief description of what this does"
else
    echo ""
    echo "✅ All files have complete metadata!"
fi


