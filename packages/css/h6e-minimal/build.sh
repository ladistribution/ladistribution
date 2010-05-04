SOURCE="git://github.com/znarf/h6e-minimal.git"
FOLDER="css"
PACKAGE="css-h6e-minimal.zip"
# Get source
git clone $SOURCE $FOLDER
rm -rf $FOLDER/.git
# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete
# Create zip package
zip -rqv $PACKAGE $FOLDER manifest.xml -x \*.svn/\* \*.preserve
mv $PACKAGE ../../
#Clean
rm -rf $FOLDER