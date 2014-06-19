#! /bin/sh

#Install Python, pip and virtualenv
apt-get update > /dev/null
apt-get -y install vim git-core python-setuptools
apt-get -y install build-essential python-dev

sudo easy_install pip
sudo pip install virtualenv

#Install Virtualenvwrapper
sudo pip install virtualenvwrapper
mkdir ~/virtualenvs

#http://stackoverflow.com/questions/12626370/virtualenv-shell-errors
wget http://python-distribute.org/distribute_setup.py
python distribute_setup.py

"export WORKON_HOME=~/virtualenvs" >> ~/.bashrc
"source /usr/local/bin/virtualenvwrapper.sh" >> ~/.bashrc
"export PIP_VIRTUALENV_BASE=~/virtualenvs" >> ~/.bashrc
