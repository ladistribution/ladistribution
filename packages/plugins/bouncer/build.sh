SOURCE="http://ladistribution.net/svn/trunk/shared/plugins/bouncer.php"
FOLDER="plugin"
FILE="bouncer.php"
PACKAGE="plugin-bouncer.zip"

echo "# Building bouncer plugin package"

# echo "# Get source from $SOURCE with svn"
# mkdir $FOLDER
# svn export $SOURCE $FOLDER/bouncer.php --quiet

echo "# Copy '$FILE' from filesystem"
mkdir $FOLDER
cp -R ../../../shared/plugins/$FILE $FOLDER/$FILE

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER dist -q -x \*.svn/\* \*.preserve
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER