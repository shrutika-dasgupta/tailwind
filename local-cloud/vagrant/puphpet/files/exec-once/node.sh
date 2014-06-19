#! /bin/sh

# Node and npm
#https://gist.github.com/x-Code-x/2562576

cd ~
git clone https://github.com/joyent/node.git
cd node
./configure
make
sudo make install
