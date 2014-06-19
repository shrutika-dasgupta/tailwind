#! /bin/sh
cd /var/www && gem install bundler
cd /var/www && bundle install

apachectl restart
