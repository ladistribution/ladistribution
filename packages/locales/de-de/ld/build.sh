NAME="ld"
LOCALE="de_DE"
SOURCE="git://github.com/ladistribution/ladistribution.git"
FOLDER="locale"
PACKAGE="$NAME-locale-fr-fr.zip"

echo "# Get source from $SOURCE with git"
git clone $SOURCE tmp --quiet

echo "# Copy '$NAME/$LOCALE' from tmp"
cp -R "tmp/shared/locales/$NAME/$LOCALE/" $FOLDER

# Clean tmp
rm -rf tmp

# Create zip package
zip -qr $PACKAGE $FOLDER manifest.xml -q -x \*.svn/\* \*.preserve \*.DS_Store
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER