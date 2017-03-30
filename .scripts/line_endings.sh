#!/bin/bash

RETURN=0
FILES=`find -L $1 -name "*"`
echo "Testing for files with DOS line endings..."
for FILE in $FILES
do
  file $FILE | grep CRLF
  if [ $? == 0 ]
  then
    RETURN=1
  fi
done
exit $RETURN
