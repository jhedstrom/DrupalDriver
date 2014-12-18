<?php

/**
 * @file
 * Contains \Drupal\Component\Utility\RandomInterface.
 */

namespace Drupal\Component\Utility;

/**
 * Defines a utility class for creating random data.
 */
interface RandomInterface {

  /**
   * Generates a random string of ASCII characters of codes 32 to 126.
   *
   * The generated string includes alpha-numeric characters and common
   * miscellaneous characters. Use this method when testing general input
   * where the content is not restricted.
   *
   * @param int $length
   *   Length of random string to generate.
   * @param bool $unique
   *   (optional) If TRUE ensures that the random string returned is unique.
   *   Defaults to FALSE.
   * @param callable $validator
   *   (optional) A callable to validate the the string. Defaults to NULL.
   *
   * @return string
   *   Randomly generated string.
   *
   * @see \Drupal\Component\Utility\Random::name()
   */
  public function string($length = 8, $unique = FALSE, $validator = NULL);

  /**
   * Generates a random string containing letters and numbers.
   *
   * The string will always start with a letter. The letters may be upper or
   * lower case. This method is better for restricted inputs that do not
   * accept certain characters. For example, when testing input fields that
   * require machine readable values (i.e. without spaces and non-standard
   * characters) this method is best.
   *
   * @param int $length
   *   Length of random string to generate.
   * @param bool $unique
   *   (optional) If TRUE ensures that the random string returned is unique.
   *   Defaults to FALSE.
   *
   * @return string
   *   Randomly generated string.
   *
   * @see \Drupal\Component\Utility\Random::string()
   */
  public function name($length = 8, $unique = FALSE);

  /**
   * Generates a random PHP object.
   *
   * @param int $size
   *   The number of random keys to add to the object.
   *
   * @return \stdClass
   *   The generated object, with the specified number of random keys. Each key
   *   has a random string value.
   */
  public function object($size = 4);

}
