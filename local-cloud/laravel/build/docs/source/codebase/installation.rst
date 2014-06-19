============
Installation
============

1. Clone the `local cloud`_ repo and follow those install instructions
2. Clone this repo into local-cloud/laravel
3. Run

.. code-block:: bash

    ./install name

where name is the name you use to ssh into the production boxes

.. _local cloud: https://github.com/pinleague/local-cloud

--------------------------
Building SASS with compass
--------------------------

1. Install bundler

.. code-block:: bash

    sudo gem install bundler

2. install the required gems

.. code-block:: bash

    sudo bundle install

3. From time to time you may need to run

.. code-block:: bash

    bundle update

or

.. code-block:: bash

    bundle install

if the gem has changed.

--------------
Compiling SCSS
--------------

* Each app has it's own sccs that compiles into one css file. To complile it,

.. code-block:: bash

    cd build/catalog && compass compile

Alternatively, you can have compass watch for changes using

.. code-block:: bash

    compass watch

on the appropriate folder. Any file without an _ before it will be compiled to its public folder. ie

.. code-block:: bash

    app/scss/catalog/app.scss => /public/catalog/css/app.css

but _foo.scss will not compile to foo.css. _ files can only be imported into non _ files
