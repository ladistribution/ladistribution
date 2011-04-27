NAME="css-h6e-minimal"
SOURCE="git://github.com/znarf/h6e-minimal.git"
FOLDER="css"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE with git"
git clone $SOURCE $FOLDER --quiet
rm -rf $FOLDER/.git

echo "# Merging"
cd $FOLDER
cat h6e-reset.css h6e-typography.css h6e-colors.css h6e-forms.css h6e-layout.css\
    h6e-tags.css h6e-pagination.css h6e-comments.css > h6e-minimal.merged.css
cd ..

echo "# Compressing"
java -jar ../../../bin/yuicompressor.jar --charset UTF-8 "$FOLDER/h6e-minimal.merged.css" -o "$FOLDER/h6e-minimal.compressed.css"

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER manifest.xml -q -x \*.svn/\* \*.preserve
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER