=============
Documentation
=============

The two main things to remember about Sphinx is that:

    - The entire layout is structured as a filesystem
    - And everything is based on indentation

`Reference`_ for a ReST primer.

-------------
Install Setup
-------------

1. In your vagrant box, install pip

.. code-block:: bash

    sudo apt-get install python-pip python-dev build-essential

    sudo pip install --upgrade pip

2. 'cd' into the app root folder

.. code-block:: bash

    cd /var/www

3. Install the required libraries

.. code-block:: bash

    sudo pip install -r requirements.txt

-----------------
Creating the Docs
-----------------

Sphinx works in terms of the directory structure. Which means, if you go to
sphinx root folder

.. code-block:: bash

    cd /var/www/build/docs/source

the **index.rst** is basically the site map for all the docs. We are going to refer to
this index.rst as *Primary Index*

Creating a new Section
^^^^^^^^^^^^^^^^^^^^^^

1. If you want to create a section; say **Logging** which contains a page **Redis**.
There are three things that need to done:

    - Create a folder logging

    .. code-block:: bash

        mkdir logging


    - Add the folder to the Primary Index

    .. code-block:: bash

        logging/index.rst

    - Create a Secondary index.rst within the logging folder

    .. code-block:: bash

        touch index.rst

    and within the secondary index.rst add the following:

    .. code-block:: rst

        Contents:

        .. toctree::
           :maxdepth: 2

           redis.rst


.. _Reference: http://docutils.sourceforge.net/docs/user/rst/quickref.html
