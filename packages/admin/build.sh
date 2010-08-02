NAME="admin"
SOURCE="http://ladistribution.net/svn/trunk/"
FOLDER="application"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

mkdir $FOLDER

echo "# Get source from $SOURCE with svn"

# Export from SVN
svn export "$SOURCE/js/ld" js --force --quiet
# Local Export
# mkdir js
# cp -R /Web/ld/js/ld js/ld

# Export from SVN
svn export "$SOURCE/shared/modules" modules --force --quiet
# Local Export
# cp -R /Web/ld/shared/modules modules

# Export from SVN
svn export "$SOURCE/shared/plugins" plugins --force --quiet
# Local Export
# cp -R /Web/ld/shared/plugins plugins

# Export from SVN
svn export "$SOURCE/admin/" $FOLDER --force --quiet
# Local Export
# cp -R /Web/ld/admin/* $FOLDER

mkdir locales
# Export from SVN
svn export "$SOURCE/shared/locales/admin/en_US" locales/en_US --force --quiet
# Local Export
# cp -R /Web/ld/shared/locales/admin/en_US locales/en_US

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER js modules plugins locales dist --quiet --exclude \*.svn/\* \*.preserve
mv $PACKAGE ..

# Clean
rm -rf js
rm -rf modules
rm -rf plugins
rm -rf locales
rm -rf $FOLDER
