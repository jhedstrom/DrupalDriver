Installation
============

To utilize the Drupal Drivers in your own project, they are installed via composer_.

.. literalinclude:: _static/snippets/composer.json
   :language: json

and then install and run composer

.. literalinclude:: _static/snippets/composer.bash
   :language: bash

.. _composer: https://getcomposer.org/

If you plan on using the Drush driver, then you should also copy
behat.d7.drush.inc or behat.d8.drush.inc, as appropriate, to the
`drush` directory in your target Drupal site.  These files may be
found in the `drush-extensions` directory of this project.
