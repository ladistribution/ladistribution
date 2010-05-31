NAME="firefox-sync"
VERSION="1.0"
REVISION_SYNC="3032e32701c1"
SOURCE_SYNC="http://hg.mozilla.org/labs/weaveserver-sync"
FOLDER="application"
PACKAGE="$NAME.zip"

# Get source
# mkdir $FOLDER
hg clone --rev $REVISION_SYNC $SOURCE_SYNC $FOLDER/sync

# Apply patches
patch -p0 -d $FOLDER < patches/sync_index.diff

# mv
mv $FOLDER/sync/1.0/default_constants.php.dist $FOLDER/sync/1.0/default_constants.php

# rm
rm -rf $FOLDER/sync/.hg
rm $FOLDER/sync/.hgtags
rm $FOLDER/sync/README
rm -rf $FOLDER/sync/tests
rm $FOLDER/sync/1.0/shard_constants.php.dist

# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

# Create zip package
zip -rqv $PACKAGE $FOLDER dist auth -x "*/.svn/*"
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER/sync
