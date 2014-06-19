Local Cloud
============

A vagrant stack of the rackspace cloud. Great for local development.

##Installation

###Prerequistes
1. Vagrant is installed
2. VirtualBox is installed

###Create Your Environment

1. Clone this repo
2. Clone the [pinleague/analytics.git](https://github.com/pinleague/analytics) repo into the local-cloud/laravel folder
   ```git clone git@github.com:pinleague/analytics.git ~/<Path-to-local-cloud>/laravel```
3. ```cd local-cloud/vagrant```
4. ```vagrant up```

 
The first time will take much longer than the subsequent loads. 

###Setup Hosts File
Edit your hosts file to include this

```
33.33.33.99 admin.tailwindapp.dev                                           
33.33.33.99 analytics.tailwindapp.dev                                       
33.33.33.99 www.tailwindapp.dev 
33.33.33.99 api.tailwindapp.dev 
33.33.33.99 contests.tailwindapp.dev 
```

Your hosts file is usually located at /etc/hosts 
```
vim /etc/hosts
```

You can now access local environments of each dev site with a browser and going to those urls.

```
http://admin.tailwindapp.dev
http://analytics.tailwindapp.dev
http://www.tailwindapp.dev
````

###Play with Dataengines
You can access the command line of the virtual box by going to local-cloud/vagrant and using ```vagrant ssh```

#### To get Sphinx set up in vagrant. Please follows the instructions in the following Gist
https://gist.github.com/Yeshwanthyk/7c0b796d02055d7b5e07

###Access Via SequelPro
You can access the database via SequelPro like any other server.
* Username: root
* password: root
* SSH User: vagrant
* SSH Pass: puphpet/files/ssh/id_rsa

