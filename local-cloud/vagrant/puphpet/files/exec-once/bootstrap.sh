#!/usr/bin/env bash
#The DEBIAN_FRONTEND=noninteractive setting
# will prevent dialogs that ask you to enter
# settings while installing and/or updating packages
# and will use the default instead
export DEBIAN_FRONTEND=noninteractive

mkdir /var/storage
mkdir /var/storage/logs
mkdir /var/storage/cache
mkdir /var/storage/meta
mkdir /var/storage/sessions
mkdir /var/storage/views

chown root:www-data /var/storage -R
chmod 777 /var/storage -R

curl https://raw.github.com/git/git/master/contrib/completion/git-completion.bash -o ~/.git-completion.bash

"if [ -f ~/.git-completion.bash ]; then
    . ~/.git-completion.bash
fi" >> /home/vagrant/.bashrc

sudo apt-get update

cd /vagrant

mysql -uroot -proot < "datastore.sql"

cd /var/www

composer install

sudo php artisan --env=local migrate
