#!/bin/bash
USER=photohunt
PASS=photohunt
DB=photohunt

mysql=/usr/bin/mysql

for file in *.sql
do
    $mysql -u $USER -p$PASS -D $DB < $file
done