#!/bin/bash

source ../../path.sh

cd /mnt/storage/category_feed

for i in `find . -maxdepth 1 -type d ! -name $(date +%b-%-d-%Y) ! -path .`
do
    tar -cvzf /mnt/cbsvolume1/category_feed_$(basename $i).tar.gz $i
    rm -rf $i
done
