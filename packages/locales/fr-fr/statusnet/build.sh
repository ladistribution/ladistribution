VERSION="0.9.x"
NAME="statusnet"
FOLDER="application"
SOURCE="http://gitorious.org/statusnet/ladistribution/archive-tarball/$VERSION"
FOLDER="locale"
PACKAGE="$NAME-locale-fr-fr.zip"

echo "# Building $NAME package"

# Get from GIT
# git clone $SOURCE $FOLDER
# cd $FOLDER
# git checkout -b $VERSION origin/$VERSION
# cd ..
# rm -rf $FOLDER/.git

echo "# Get source from $SOURCE with curl"
curl $SOURCE -# > statusnet.gz
tar -x -f statusnet.gz
rm statusnet.gz

mv statusnet-ladistribution/locale/fr_FR $FOLDER
rm -rf statusnet-ladistribution

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER dist -q -x \*.svn/\*
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
