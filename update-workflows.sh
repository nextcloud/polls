#!/bin/sh

# Update GitHub workflows from the Nextcloud template repository.
# This script is meant to be run from the root of the repository.

# Sanity check
[ ! -d ./.github/workflows/ ] && echo "Error: .github/workflows does not exist" && exit 1

# Clone template repository
temp="$(mktemp -d)"
git clone --depth=1 https://github.com/nextcloud/.github.git "$temp"

# Update workflows
rsync -vr \
    --existing \
    --include='*/' \
    --include='*.yml' \
    --exclude='*' \
    "$temp/workflow-templates/" \
    ./.github/workflows/

# Cleanup
rm -rf "$temp"
