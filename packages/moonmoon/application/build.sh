NAME="moonmoon"
VERSION="8.12"
SOURCE="http://svn.sharesource.org/svn/$NAME/branches/$VERSION/"
FOLDER="application"
PACKAGE="$NAME.zip"

# Get source
svn export $SOURCE $FOLDER --force

# Remove some useless (or not desired) files
rm $FOLDER/install.php

# Add dummy file in cache folder
echo "dummy" > $FOLDER/cache/dummy

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

# Create zip package
zip -rqv $PACKAGE $FOLDER dist -x "*/.svn/*"
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER