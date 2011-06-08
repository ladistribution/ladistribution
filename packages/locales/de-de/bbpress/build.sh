NAME="bbpress"
LOCALE="de_DE"
VERSION="1.0.2"
SOURCE="http://svn.automattic.com/bbpress-i18n/$LOCALE/trunk/"
PACKAGE="$NAME-locale-fr-fr.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with svn"
mkdir languages
svn export "$SOURCE/messages/$LOCALE.mo" "languages/$LOCALE.mo" --quiet

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE dist languages -q -x "*/.svn/*"
mv $PACKAGE ../../

# Clean
rm -rf languages