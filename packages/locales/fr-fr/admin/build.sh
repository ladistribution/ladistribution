NAME="admin"
LOCALE="fr_FR"
SOURCE="http://ladistribution.net/svn/trunk/shared/locales/$NAME/$LOCALE/"
FOLDER="locale"
PACKAGE="$NAME-locale-fr-fr.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with svn"
svn export $SOURCE $FOLDER --quiet

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER dist -q -x \*.svn/\* \*.preserve
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER