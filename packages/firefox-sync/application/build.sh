NAME="firefox-sync"
VERSION="1.3.100924"
REVISION_SYNC="0cbbe2093e9ecc0f945db609d84102e9bfad7951"
SOURCE_SYNC="http://hg.mozilla.org/services/sync-server/"
FOLDER="application"
PACKAGE="$NAME.zip"

echo "# Building $NAME package"

echo "# Get source from $SOURCE_SYNC with hg"
hg clone --quiet --rev $REVISION_SYNC $SOURCE_SYNC $FOLDER/sync

echo "# Apply patches"
patch -p0 -d $FOLDER < patches/sync_index.diff
# patch -p0 -d $FOLDER < patches/sync_storage_mysql.diff

# mv
mv $FOLDER/sync/1.0/default_constants.php.dist $FOLDER/sync/1.0/default_constants.php

echo "# Remove files"

# rm
rm -rf $FOLDER/sync/.hg
rm $FOLDER/sync/.hgtags
rm $FOLDER/sync/README
rm -rf $FOLDER/sync/tests
rm $FOLDER/sync/1.0/shard_constants.php.dist

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

echo "# Packing $PACKAGE"
zip -r $PACKAGE $FOLDER dist auth -q -x \*.svn/\*
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER/sync
