#!/usr/bin/env bash
# Generate README.md
# Usage: ./generate-readme.sh

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
    echo "| Title | Type | Status | Description |" >> $README_SNIPPETS_FILE
    echo "| ----- | ---- | ------ | ----------- |" >> $README_SNIPPETS_FILE
    for FILE in $DIRECTORY/*.php; do
        FILE_NAME=$(basename $FILE)
        if [ "$FILE_NAME" != "README.md" ] && [ "$FILE_NAME" != "$README_SNIPPETS_FILE" ]; then            
            # -- Title
            TITLE=$(grep -i "Plugin Name:" "$FILE" | tail -n 1 | sed 's/ \* Plugin Name: //')
            if [[ -z "$TITLE" ]]; then
                TITLE="$FILE_NAME"
            fi

            # -- Type
            TYPE=$(grep -i "Type:" "$FILE" | tail -n 1 | sed 's/ \* Type: //')

            # -- Status
            STATUS=$(grep -i "Status:" "$FILE" | tail -n 1 | sed 's/ \* Status: //')
            if [[ -z $STATUS ]]; then
                STATUS="Unknown"
                echo "Warning: No status found for $FILE"
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
                echo "Warning: No description found for $FILE"
            fi

            # -- Set default values for plugins
            if [[ -z $TYPE && -n $TITLE ]]; then
                TYPE="Plugin"                
            fi
            if [[ -z $TYPE && -z $TITLE ]]; then
                TYPE="Unknown"
                echo "Warning: No type found for $FILE"
            fi
            
            echo "| [$TITLE]($FILE) | $TYPE | $STATUS | $DESCRIPTION |" >> $README_SNIPPETS_FILE
        fi
    done
    echo "" >> $README_SNIPPETS_FILE
done

# -- Generate CHANGELOG.md
echo "# Changelog" > CHANGELOG.md
git log --pretty=format:"## %s%n%b%n" | sed '/^## /b; /^[[:space:]]*$/b; s/^/* /' >> CHANGELOG.md

# Generate README.md
cat README-header.md $README_SNIPPETS_FILE CHANGELOG.md > README.md


