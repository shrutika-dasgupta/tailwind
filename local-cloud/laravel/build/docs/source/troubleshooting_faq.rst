=====================
Troubleshooting FAQ's
=====================
-------------
Server Issues
-------------

Why aren't DevAlerts being sent out?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

1. Make sure cron is running

.. code-block:: bash

    service crond status
    service crond restart

2. Make sure sendmail / postfix is running

.. code-block:: bash

    service postfix restart

---------------
Database Issues
---------------

1. Why am I getting "Error in exception handler." when I run a file on the server.
    -If in the server you aren't able to access the database(Or you get back NULL), it just might mean that there are too many connections to the database. The way to solve this would be to flush hosts in db.
    - Also, "Error in exception handler." error when you run a file on the server might be a result of the above stated issue. What is happening is there DB connection can't be established and when it goes to write the error to the log file, permission on the logs aren't allowing it to write to it.

-----------------
Deployment Issues
-----------------

1. I can't push to ____. It says the git repo couldn't be found.
    - Can you ssh into all the boxes?
    - Did you run ./install.sh {your-name} since the last time a repo was added?


---------------
Composer Issues
---------------

1. Composer dies with some memory issue and I can't run composer update?
    - Does composer install work?
    - Composer has some issues with php 5.3 and memory management. Do this:

        .. code-block:: bash

            /bin/dd if=/dev/zero of=/var/swap.1 bs=1M count=1024
            /sbin/mkswap /var/swap.1
            /sbin/swapon /var/swap.1

More details about this `black magic here`_

2. Composer dies with a "Class not found" error?
    -Try running composer update --no-scripts first, then rerun

.. _black magic here: http://yourstory.com/2012/02/adding-swap-space-to-amazon-ec2-linux-micro-instance-to-increase-the-performance/


