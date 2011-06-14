NAME="wordpress"
LOCALE="fr_FR"
VERSION="3.2"
SOURCE="http://svn.automattic.com/wordpress-i18n/$LOCALE/branches/$VERSION/"
PACKAGE="$NAME-locale-fr-fr.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with svn"

# Get source
mkdir languages
svn export "$SOURCE/messages/$LOCALE.mo" "languages/$LOCALE.mo" --quiet
svn export "$SOURCE/messages/continents-cities-$LOCALE.mo" "languages/continents-cities-$LOCALE.mo" --quiet
mkdir themes/twentyten
svn export "$SOURCE/messages/twentyten/$LOCALE.mo" "themes/twentyten/$LOCALE.mo" --quiet
mkdir themes/twentyeleven
svn export "$SOURCE/messages/twentyeleven/$LOCALE.mo" "themes/twentyeleven/$LOCALE.mo" --quiet

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE dist languages themes -q -x \*.svn/\*
mv $PACKAGE ../../

# Clean
rm -rf languages
rm -rf themes/twentyten
rm -rf themes/twentyeleven