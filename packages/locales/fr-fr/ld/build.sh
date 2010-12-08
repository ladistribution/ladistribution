NAME="ld"
LOCALE="fr_FR"
SOURCE="http://ladistribution.net/svn/trunk/shared/locales/$NAME/$LOCALE/"
FOLDER="locale"
PACKAGE="$NAME-locale-fr-fr.zip"

# Get source
svn export $SOURCE $FOLDER --quiet

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

# Create zip package
zip -r $PACKAGE $FOLDER manifest.xml -q -x \*.svn/\* \*.preserve
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER