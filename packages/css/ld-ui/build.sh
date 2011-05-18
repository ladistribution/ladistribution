NAME="css-ld-ui"
# SOURCE="http://ladistribution.net/svn/trunk/css/ld-ui"
SOURCE="git@github.com:ladistribution/ladistribution.git"
FOLDER="css"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

# echo "# Get source from $SOURCE with svn"
# svn export $SOURCE $FOLDER --force --quiet

echo "# Get source from $SOURCE with git"
git clone $SOURCE tmp --quiet

# Copy from Git
cp -R tmp/css/ld-ui $FOLDER

# Local Export
# cp -R /Web/ld/css/ld-ui $FOLDER

echo "# Merging"
cd $FOLDER
cat ld-slotter.css ld-merger.css ld-login.css ld-data.css ld-bars.css ld-panel.css\
    ld-instances.css ld-users.css ld-theme-base.css > ld-ui.merged.css
cd ..

echo "# Compressing"
java -jar ../../../bin/yuicompressor.jar --charset UTF-8 "$FOLDER/ld-ui.merged.css" -o "$FOLDER/ld-ui.compressed.css"

# Clean tmp
rm -rf tmp

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER manifest.xml -q -x \*.svn/\* \*.preserve
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER