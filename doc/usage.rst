Usage
=====

Drupal API driver
-----------------

.. literalinclude:: _static/snippets/usage-drupal.php
   :language: php
   :linenos:
   :emphasize-lines: 14-15

Drush driver
------------

.. literalinclude:: _static/snippets/usage-drush.php
   :language: php
   :linenos:
   :emphasize-lines: 7-8

In order for the Drush driver to create content, it needs
to have the behat-drush-endpoint installed on the target
Drupal site.  Place it in the 'drush' folder at the Drupal
root.  If you use Composer to require the DrupalDriver in
your Drupal site, then the behat-drush-endpoint will be
placed in the right location automatically.

https://github.com/pantheon-systems/behat-drush-endpoint

Blackbox
--------

Note, the blackbox driver has no ability to control Drupal, and is provided as a fallback for when some tests can run without such access.

Any testing application should catch unsupported driver exceptions.

.. literalinclude:: _static/snippets/usage-blackbox.php
   :language: php
   :linenos:
   :emphasize-lines: 8,19

Practical example with PHPUnit
------------------------------

By using the phpunit/mink project in conjunction with the Drupal Driver, one can use PHPUnit to drive browser sessions and control Drupal.

To install:

.. literalinclude:: _static/snippets/phpunit-composer.json
   :language: json
   :linenos:

and then, in the tests directory, a sample test:

.. literalinclude:: _static/snippets/phpunitDrupalDriver.php
   :language: php
   :linenos:
