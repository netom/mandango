#!/bin/bash

case "${PHPVER}" in
    PHP71)
        yes "" | pecl install apcu-5.1.8
        yes "" | pecl install mongodb
        ;;
    PHP70)
        yes "" | pecl install apcu-5.1.8
        yes "" | pecl install mongodb
        ;;
    PHP56)
        yes "" | pecl install apcu-4.0.11
        yes "" | pecl install mongodb
        ;;
esac

echo 'extension = apcu.so' >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini
echo 'apc.enabled = 1' >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini
echo 'apc.enable_cli = 1' >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini
./composer.phar install
