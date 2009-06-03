SOURCE="http://ladistribution.h6e.net/svn/trunk/modules/"
FOLDER="application"
PACKAGE="admin.zip"

# Export from SVN
svn export $SOURCE modules

# Move bootstrap
mv modules/Bootstrap.php $FOLDER

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

# Create zip package
zip -rqv $PACKAGE $FOLDER modules dist -x "*/.svn/*"
mv $PACKAGE ../

# Clean
rm -rf modules
rm -rf $FOLDER/Bootstrap.php