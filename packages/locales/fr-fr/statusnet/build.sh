VERSION="0.9.x"
NAME="statusnet"
FOLDER="locale"
PACKAGE="$NAME-locale-fr-fr.zip"

# Get from GIT
# git clone $SOURCE $FOLDER
# cd $FOLDER
# git checkout -b $VERSION origin/$VERSION
# cd ..
# rm -rf $FOLDER/.git

# Get from GZ
curl http://gitorious.org/statusnet/ladistribution/archive-tarball/0.9.x > statusnet.gz
tar zxvf statusnet.gz
rm statusnet.gz

# Get from Github with SVN
# svn checkout http://svn.github.com/znarf/statusnet-ladistribution.git $FOLDER

mv statusnet-ladistribution/locale/fr_FR $FOLDER
rm -rf statusnet-ladistribution

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

# Create zip package
zip -rqv $PACKAGE $FOLDER dist -x "*/.svn/*"
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER
