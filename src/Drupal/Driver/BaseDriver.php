<?php

namespace Drupal\Driver;

use Drupal\Driver\Exception\UnsupportedDriverActionException;

/**
 * Implements DriverInterface.
 */
abstract class BaseDriver implements DriverInterface {

  /**
   * {@inheritDoc}
   */
  public function getRandom() {
    throw new UnsupportedDriverActionException($this->errorString('generate random'), $this);
  }

  /**
   * {@inheritDoc}
   */
  public function bootstrap() {
  }

  /**
   * {@inheritDoc}
   */
  public function isBootstrapped() {
  }

  /**
   * {@inheritDoc}
   */
  public function userCreate(\stdClass $user) {
    throw new UnsupportedDriverActionException($this->errorString('create users'), $this);
  }

  /**
   * {@inheritDoc}
   */
  public function userDelete(\stdClass $user) {
    throw new UnsupportedDriverActionException($this->errorString('delete users'), $this);
  }

  public function processBatch() {
    throw new UnsupportedDriverActionException($this->errorString('process batch actions'), $this);
  }

  /**
   * {@inheritDoc}
   */
  public function userAddRole(\stdClass $user, $role) {
    throw new UnsupportedDriverActionException($this->errorString('add roles'), $this);
  }

  /**
   * {@inheritDoc}
   */
  public function fetchWatchdog($count = 10, $type = NULL, $severity = NULL) {
    throw new UnsupportedDriverActionException($this->errorString('access watchdog entries'), $this);
  }

  /**
   * {@inheritDoc}
   */
  public function clearCache($type = NULL) {
    throw new UnsupportedDriverActionException($this->errorString('clear Drupal caches'), $this);
  }

  /**
   * {@inheritDoc}
   */
  public function createNode($node) {
    throw new UnsupportedDriverActionException($this->errorString('create nodes'), $this);
  }

  /**
   * {@inheritDoc}
   */
  public function nodeDelete($node) {
    throw new UnsupportedDriverActionException($this->errorString('delete nodes'), $this);
  }

  /**
   * {@inheritDoc}
   */
  public function runCron() {
    throw new UnsupportedDriverActionException($this->errorString('run cron'), $this);
  }

  /**
   * {@inheritDoc}
   */
  public function createTerm(\stdClass $term) {
    throw new UnsupportedDriverActionException($this->errorString('create terms'), $this);
  }

  /**
   * {@inheritDoc}
   */
  public function termDelete(\stdClass $term) {
    throw new UnsupportedDriverActionException($this->errorString('delete terms'), $this);
  }

  /**
   * {@inheritDoc}
   */
  public function roleCreate(array $permissions) {
    throw new UnsupportedDriverActionException($this->errorString('create roles'), $this);
  }

  /**
   * {@inheritDoc}
   */
  public function roleDelete($rid) {
    throw new UnsupportedDriverActionException($this->errorString('delete roles'), $this);
  }

  /**
   * {@inheritDoc}
   */
  public function isField($entity_type, $field_name) {
    return FALSE;
  }

  /**
   * Error printing exception
   *
   * @param string $error
   *   The term, node, user or permission.
   *
   * @return String
   *   A formatted string reminding people to use an api driver.
   */
  private function errorString($error) {
    return sprintf('No ability to %s in %%s. Put `@api` into your feature and add an api driver (ex: `api_driver: drupal`) in behat.yml.', $error);
  }
}
