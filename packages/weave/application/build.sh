NAME="weave"
VERSION="1.0"
REVISION_SYNC="1579e64c47ef"
SOURCE_REGISTRATION="http://hg.mozilla.org/labs/weaveserver-registration"
SOURCE_SYNC="http://hg.mozilla.org/labs/weaveserver-sync"
FOLDER="application"
PACKAGE="$NAME.zip"

# Get source
# mkdir $FOLDER
# hg clone $SOURCE_REGISTRATION $FOLDER/registration
hg clone --rev $REVISION_SYNC $SOURCE_SYNC $FOLDER/sync

# Apply patches
# patch -p0 -d $FOLDER < patches/registration_index.diff
# patch -p0 -d $FOLDER < patches/registration_constants.diff
# patch -p0 -d $FOLDER < patches/registration_sql.diff
patch -p0 -d $FOLDER < patches/sync_index.diff
# patch -p0 -d $FOLDER < patches/sync_user_sql.diff

# mv
# mv $FOLDER/registration/1.0/weave_user_constants.php.dist $FOLDER/registration/1.0/weave_user_constants.php
mv $FOLDER/sync/1.0/default_constants.php.dist $FOLDER/sync/1.0/default_constants.php

# rm
# rm -rf $FOLDER/registration/.hg
# rm $FOLDER/registration/.hgtags
rm -rf $FOLDER/sync/.hg
rm $FOLDER/sync/.hgtags
# rm $FOLDER/registration/README
rm $FOLDER/sync/README
# rm -rf $FOLDER/registration/tests
rm -rf $FOLDER/sync/tests
rm $FOLDER/sync/1.0/shard_constants.php.dist


# Remove some unwanted files (mac)
find . -name '*.DS_Store' -type f -delete

# Create zip package
zip -rqv $PACKAGE $FOLDER dist auth -x "*/.svn/*"
mv $PACKAGE ../../

# Clean
rm -rf $FOLDER/registration
rm -rf $FOLDER/sync
