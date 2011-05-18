NAME="admin"
# SOURCE="http://ladistribution.net/svn/trunk/"
SOURCE="git@github.com:ladistribution/ladistribution.git"
FOLDER="application"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

# echo "# Get source from $SOURCE with svn"
# svn export "$SOURCE/admin/" $FOLDER --force --quiet

echo "# Get source from $SOURCE with git"
git clone $SOURCE tmp --quiet

# Copy from Git
cp -R tmp/admin $FOLDER

# Local Export
# cp -R /Web/ld/admin/* $FOLDER

# Clean tmp
rm -rf tmp

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER dist -q -x \*.svn/\* \*.preserve
mv $PACKAGE ..

# Clean
rm -rf $FOLDER