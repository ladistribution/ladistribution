NAME="admin"
LOCALE="fr_FR"
SOURCE="git://github.com/ladistribution/ladistribution.git"
FOLDER="locale"
PACKAGE="$NAME-locale-fr-fr.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with git"
git clone $SOURCE tmp --quiet

echo "# Copy '$NAME/$LOCALE' from tmp"
cp -R "tmp/shared/locales/$NAME/$LOCALE/" $FOLDER

# Clean tmp
rm -rf tmp

echo "# Packing $PACKAGE"
zip -qr $PACKAGE $FOLDER dist -x \*.svn/\* \*.preserve \*.DS_Store
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER