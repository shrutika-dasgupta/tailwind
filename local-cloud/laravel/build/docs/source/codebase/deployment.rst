==========
Deployment
==========

We use git to deploy to Rackspace servers, run off of a bash script. Each application
has it's own remote in git. To deploy,

.. code-block:: bash

    push {remote} {env} {branch}

    push fe prod master

These are the options

.. code-block:: bash

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

You can also just push a branch EVERYWHERE

.. code-block::  bash

    push.sh prod master

Or just to the DataEngines (Calcs | Pulls | Feeds )

.. code-block:: bash

    push de-all prod master

Depending on your system, you may need to type a ./ before push, like so

.. code-block:: bash

    ./push prod master

Composer install is run on the server after it is deployed, so thats all that text you see. The Engineering room in hipchat will be notified of your push.