NAME="bbpress"
LOCALE="fr_FR"
VERSION="1.0.2"
SOURCE="http://svn.automattic.com/bbpress-i18n/$LOCALE/tags/$VERSION/"
PACKAGE="$NAME-locale-fr-fr.zip"

# Get source
mkdir languages
svn export "$SOURCE/messages/$LOCALE.mo" "languages/$LOCALE.mo"

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

# Create zip package
zip -rqv $PACKAGE dist languages -x "*/.svn/*"
mv $PACKAGE ../../

# Clean
rm -rf languages