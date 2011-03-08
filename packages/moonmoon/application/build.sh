NAME="moonmoon"
SOURCE="git://github.com/znarf/moonmoon.git"
FOLDER="application"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with git"
git clone --quiet git://github.com/znarf/moonmoon.git $FOLDER
rm -rf $FOLDER/.git
rm $FOLDER/.gitignore

rm -rf $FOLDER/dist
# mv $FOLDER/dist .

# Remove some useless (or not desired) files
rm $FOLDER/install.php
rm $FOLDER/admin/changepassword.php
rm $FOLDER/admin/inc/pwd.inc.php

# Add dummy file in cache folder
mkdir $FOLDER/cache
echo "dummy" > $FOLDER/cache/dummy

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER dist -q -x \*.svn/\*
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER