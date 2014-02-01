NAME="wordpress"
LOCALE="fr_FR"
VERSION="3.8"
SOURCE="http://svn.automattic.com/wordpress-i18n/$LOCALE/branches/$VERSION/"
PACKAGE="$NAME-locale-fr-fr.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with svn"

# Get source
mkdir languages
svn export "$SOURCE/messages/$LOCALE.mo" "languages/$LOCALE.mo" --quiet
svn export "$SOURCE/messages/continents-cities-$LOCALE.mo" "languages/continents-cities-$LOCALE.mo" --quiet
mkdir themes/twentythirteen
svn export "$SOURCE/messages/twentythirteen/$LOCALE.mo" "themes/twentythirteen/$LOCALE.mo" --quiet
mkdir themes/twentytwelve
svn export "$SOURCE/messages/twentytwelve/$LOCALE.mo" "themes/twentytwelve/$LOCALE.mo" --quiet

OLD_SOURCE="http://svn.automattic.com/wordpress-i18n/fr_FR/branches/3.6/"
mkdir themes/twentyten
svn export "$OLD_SOURCE/messages/twentyten/$LOCALE.mo" "themes/twentyten/$LOCALE.mo" --quiet
mkdir themes/twentyeleven
svn export "$OLD_SOURCE/messages/twentyeleven/$LOCALE.mo" "themes/twentyeleven/$LOCALE.mo" --quiet

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE dist languages themes -q -x \*.svn/\*
mv $PACKAGE ../../

# Clean
rm -rf languages
rm -rf themes/twentyten
rm -rf themes/twentyeleven
rm -rf themes/twentytwelve
rm -rf themes/twentythirteen