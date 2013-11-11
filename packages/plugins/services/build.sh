FOLDER="plugin"
PACKAGE="plugin-services.zip"

echo "# Building services plugin package"

echo "# Copy files from filesystem"
cp -R ../../../shared/plugins/services $FOLDER
mv $FOLDER/manifest.xml manifest.xml

echo "# Packing $PACKAGE"
zip -rq $PACKAGE $FOLDER manifest.xml -x \*.svn/\* \*.preserve \*.DS_Store
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
rm manifest.xml
