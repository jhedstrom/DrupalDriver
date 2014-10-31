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

Blackbox
--------

Note, the blackbox driver has no ability to control Drupal, and is provided as a fallback for when some tests can run without such access.

Any testing application should catch unsupported driver exceptions.

.. literalinclude:: _static/snippets/usage-blackbox.php
   :language: php
   :linenos:
   :emphasize-lines: 8,19
