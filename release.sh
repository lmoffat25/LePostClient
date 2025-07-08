#!/bin/bash

# Exit if any command fails
set -e

# Check if version is provided
if [ -z "$1" ]; then
    echo "Error: No version number provided"
    echo "Usage: ./release.sh <version-number>"
    echo "Example: ./release.sh 1.0.1"
    exit 1
fi

VERSION=$1
CURRENT_DATE=$(date +%Y-%m-%d)

# Validate version format (semver)
if ! [[ $VERSION =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "Error: Version must be in format x.y.z (e.g., 1.0.1)"
    exit 1
fi

echo "Preparing release v$VERSION..."

# Update version in plugin header
sed -i '' "s/Version:     [0-9]*\.[0-9]*\.[0-9]*/Version:     $VERSION/" lepostclient.php

# Update version constant
sed -i '' "s/define( 'LEPOSTCLIENT_VERSION', '[0-9]*\.[0-9]*\.[0-9]*' );/define( 'LEPOSTCLIENT_VERSION', '$VERSION' );/" lepostclient.php

# Update CHANGELOG.md
# Check if version already exists in CHANGELOG
if grep -q "\## \[$VERSION\]" CHANGELOG.md; then
    echo "Version $VERSION already exists in CHANGELOG.md"
else
    # Add new version to CHANGELOG.md
    awk -v ver="$VERSION" -v date="$CURRENT_DATE" '
    /^## / { 
        if (!found) { 
            print "## [" ver "] - " date "\n\n### Added\n- \n\n### Changed\n- \n\n### Fixed\n- \n"; 
            found=1; 
        }
        print; 
        next; 
    } 
    !found && /^# Changelog/ { 
        print; 
        print ""; 
        print "## [" ver "] - " date "\n\n### Added\n- \n\n### Changed\n- \n\n### Fixed\n- \n"; 
        found=1; 
        next; 
    } 
    { print } 
    ' CHANGELOG.md > CHANGELOG.md.tmp && mv CHANGELOG.md.tmp CHANGELOG.md
    
    echo "Added version $VERSION to CHANGELOG.md"
    echo "Please update the CHANGELOG.md with your changes"
    echo "Press Enter to continue when done..."
    read
fi

# Make sure all dependencies are installed
composer install --no-dev

# Commit changes
git add lepostclient.php CHANGELOG.md vendor/*
git commit -m "Prepare release v$VERSION"

# Create a new tag
git tag "v$VERSION"

echo "Release v$VERSION prepared successfully!"
echo ""
echo "Next steps:"
echo "1. Review the changes: git show v$VERSION"
echo "2. Push the changes: git push origin main"
echo "3. Push the tag: git push origin v$VERSION"
echo "4. Create a new release on GitHub with tag v$VERSION"
echo "5. Upload the zipped plugin as an asset to the release"
echo ""
echo "To create a zip file for the release, run:"
echo "git archive --format=zip --prefix=lepostclient/ v$VERSION > lepostclient-$VERSION.zip" 