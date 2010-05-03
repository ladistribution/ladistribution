NAME="wordpress"
LOCALE="fr_FR"
VERSION="3.0"
SOURCE="http://svn.automattic.com/wordpress-i18n/$LOCALE/branches/$VERSION/"
PACKAGE="$NAME-locale-fr-fr.zip"

# Get source
mkdir languages
svn export "$SOURCE/messages/$LOCALE.mo" "languages/$LOCALE.mo"
svn export "$SOURCE/messages/continents-cities-$LOCALE.mo" "languages/continents-cities-$LOCALE.mo"
mkdir themes/default
svn export "$SOURCE/messages/kubrick/$LOCALE.mo" "themes/default/$LOCALE.mo"
mkdir themes/twentyten
svn export "$SOURCE/messages/twentyten/$LOCALE.mo" "themes/twentyten/$LOCALE.mo"

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

# Create zip package
zip -rqv $PACKAGE dist languages themes -x "*/.svn/*"
mv $PACKAGE ../../

# Clean
rm -rf languages
rm -rf themes/default