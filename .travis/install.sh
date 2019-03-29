#!/bin/bash

yes "" | pecl install apcu-5.1.8
yes "" | pecl install mongodb

echo 'extension = apcu.so' >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini
echo 'apc.enabled = 1' >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini
echo 'apc.enable_cli = 1' >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini

if php -r 'exit(extension_loaded("mongodb")?0:1);'
then
    echo "The 'mongodb' extension is already loaded into PHP, skipping installation."
else
    echo "Installing mongodb extension into php..."
    echo 'extension = mongodb.so'  >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini
    echo "...done."
fi

./composer.phar install
