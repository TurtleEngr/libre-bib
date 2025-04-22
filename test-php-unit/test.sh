#!/bin/bash

# For this to work with php7.2 the following is needed:
# phpunit-8.phar
# install php7.2-xml
# install php7.2-mbstring

php phpunit-8.phar test/DateFormatTest.php
