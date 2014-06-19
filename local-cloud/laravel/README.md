#Tailwind Analytics
The unified codebase for the catalog website, analytics frontend & dataengines.

### Catalog Site
* [www.tailwindapp.com](http://www.tailwindapp.com)
* [staging.tailwindapp.com](http://staging.tailwindapp.com)
 

### Analytics App
* [analytics.tailwindapp.com](http://analytics.tailwindapp.com)
* [staging.analytics.tailwindapp.com](http://staging.analytics.tailwindapp.com)
* [beta.analytics.tailwindapp.com](http://beta.analytics.tailwindapp.com)
 
### Internal Admin App
* [admin.tailwindapp.com](http://admin.analytics.tailwindapp.com)

##Installation
1. Clone the [local cloud](https://github.com/pinleague/local-cloud) repo and follow those install instructions
2. Clone this repo into local-cloud/laravel
3. Run ```./install name``` where name is the name you use to ssh into the production boxes


## Deployment
We use git to deploy to Rackspace servers, run off of a bash script. Each application has it's own remote in git. To deploy,
```
push {remote} {env} {branch}
```
i.e.
```
push fe prod master
```

These are the options

```
push cat prod master
push cat stag master
push fe prod master
push fe stag master
push fe beta master
push calcs prod master
push calcs stag master
push pulls prod master
push pulls stag master
push feeds prod master
push feeds stag master
```
You can also just push a branch EVERYWHERE
```
push.sh prod master
```
Or just to the DataEngines (Calcs | Pulls | Feeds )
```
push de-all prod master
```
Depending on your system, you may need to type a ./ before push, like so
```
./push prod master
```
Composer install is run on the server after it is deployed, so thats all that text you see. The Engineering room in hipchat will be notified of your push.

## Building SASS with compass/gulp
### Install

1. Install bundler
```cd build && sudo gem install bundler```
2. install the required gems
```cd build && sudo bundle install```
From time to time you may need to run ```bundle update``` or ```bunlde install``` if the gem has changed.
3. CD into build directory and install node_modules
```cd build/analytics && npm install```

This should have automatically been done during vagrant up

### Compiling SCSS
Each app has it's own sccs that compiles into one css file. To complile it,

```cd build/catalog && gulp sass```

Alternatively, you can have compass watch for changes using

```gulp watch```

on the appropriate folder. Any file without an _ before it will be compiled to its public folder. ie

```/app/scss/catalog/app.scss``` => ```/public/catalog/css/app.css``` 

but _foo.scss will not compile to foo.css. _ files can only be imported into non _ files

## CLI tools for developement
### Model & Collection Generator
Models can be generated using the artisan command
```
php artisan model:make
```
You can also pass arguments to the command
```
php artisan model:make --type=eloquent --table=table_name --name='ModelName'
```
If you don't pass any arguments, the program will still ask for them (which makes it easier if you forgot)

### Production Data export
Data can be exported from the production database to your local environment using
```
php artisan user:export
```

Logging
----------
You can see the logs at the [Beaver Dam](http://beaver.dam.tailwindapp.com)

Tailwind uses Monolog, which supports the logging levels described by [RFC 5424](http://tools.ietf.org/html/rfc5424).

- **DEBUG** (100): Detailed debug information. [ Orange ]

- **INFO** (200): Interesting events. Examples: User logs in, SQL logs. [ Green ]

- **NOTICE** (250): Normal but significant events. [ Blue ]

- **WARNING** (300): Exceptional occurrences that are not errors. Examples:
  Use of deprecated APIs, poor use of an API, undesirable things that are not
  necessarily wrong. [ Yellow ]

- **ERROR** (400): Runtime errors that do not require immediate action but
  should typically be logged and monitored. [ Red ] 

- **CRITICAL** (500): Critical conditions. Example: Application component
  unavailable, unexpected exception. [ Dark Red ]

- **ALERT** (550): Action must be taken immediately. Example: Entire website
  down, database unavailable, etc. This should trigger the SMS alerts and wake
  you up. [ Darker Red ]

- **EMERGENCY** (600): Emergency: system is unusable. [ Deepest Red ]
