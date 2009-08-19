SOURCE="http://ladistribution.net/svn/trunk/"
FOLDER="application"
PACKAGE="admin.zip"

# Export from SVN
svn export "$SOURCE/js" js

# Export from SVN
svn export "$SOURCE/shared/modules" modules
# cp -R /Web/ld/shared/modules modules

# Export from SVN
svn export "$SOURCE/admin/" $FOLDER --force

# en_US locales
mkdir locales
svn export "$SOURCE/shared/locales/admin/en_US" locales/en_US

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

# Create zip package
zip -rqv $PACKAGE $FOLDER js modules locales dist -x "*/.svn/*"
mv $PACKAGE ../

# Clean
rm -rf js
rm -rf modules
rm -rf locales
rm -rf $FOLDER/*