SOURCE="http://ladistribution.net/svn/trunk/"
FOLDER="application"
PACKAGE="admin.zip"

# Export from SVN
svn export "$SOURCE/js" js

# Export from SVN
svn export "$SOURCE/shared/modules" modules

# Export from SVN
svn export "$SOURCE/admin/" $FOLDER --force

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

# Create zip package
zip -rqv $PACKAGE $FOLDER js modules dist -x "*/.svn/*"
mv $PACKAGE ../

# Clean
rm -rf js
rm -rf modules
rm -rf $FOLDER/*