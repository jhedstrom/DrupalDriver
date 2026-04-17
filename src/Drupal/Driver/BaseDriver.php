<?php

declare(strict_types=1);

namespace Drupal\Driver;

use Drupal\Component\Utility\Random;
use Drupal\Driver\Exception\UnsupportedDriverActionException;

/**
 * Implements DriverInterface.
 */
abstract class BaseDriver implements DriverInterface {

  /**
   * {@inheritdoc}
   */
  public function getRandom(): Random {
    throw new UnsupportedDriverActionException($this->errorString('generate random'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function bootstrap(): void {
  }

  /**
   * {@inheritdoc}
   */
  public function isBootstrapped(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function userCreate(\stdClass $user): void {
    throw new UnsupportedDriverActionException($this->errorString('create users'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function userDelete(\stdClass $user): void {
    throw new UnsupportedDriverActionException($this->errorString('delete users'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function processBatch(): void {
    throw new UnsupportedDriverActionException($this->errorString('process batch actions'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function userAddRole(\stdClass $user, string $role): void {
    throw new UnsupportedDriverActionException($this->errorString('add roles'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchWatchdog(int $count = 10, ?string $type = NULL, ?string $severity = NULL): string {
    throw new UnsupportedDriverActionException($this->errorString('access watchdog entries'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache(?string $type = NULL): void {
    throw new UnsupportedDriverActionException($this->errorString('clear Drupal caches'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function clearStaticCaches(): void {
    throw new UnsupportedDriverActionException($this->errorString('clear static caches'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function createNode(object $node): object {
    throw new UnsupportedDriverActionException($this->errorString('create nodes'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function nodeDelete(object $node): void {
    throw new UnsupportedDriverActionException($this->errorString('delete nodes'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function runCron(): bool {
    throw new UnsupportedDriverActionException($this->errorString('run cron'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function createTerm(\stdClass $term): object {
    throw new UnsupportedDriverActionException($this->errorString('create terms'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function termDelete(\stdClass $term): bool {
    throw new UnsupportedDriverActionException($this->errorString('delete terms'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function roleCreate(array $permissions): string {
    throw new UnsupportedDriverActionException($this->errorString('create roles'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function roleDelete(string $rid): void {
    throw new UnsupportedDriverActionException($this->errorString('delete roles'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function isField(string $entity_type, string $field_name): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isBaseField(string $entity_type, string $field_name): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function configGet(string $name, string $key): mixed {
    throw new UnsupportedDriverActionException($this->errorString('config get'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function configSet(string $name, string $key, mixed $value): void {
    throw new UnsupportedDriverActionException($this->errorString('config set'), $this);
  }

  /**
   * Error printing exception.
   *
   * @param string $error
   *   The term, node, user or permission.
   *
   * @return string
   *   A formatted string reminding people to use an API driver.
   */
  private function errorString(string $error): string {
    return sprintf('No ability to %s in %%s. Put `@api` into your feature and add an API driver (ex: `api_driver: drupal`) in behat.yml.', $error);
  }

  /**
   * {@inheritdoc}
   */
  public function createEntity(string $entity_type, \stdClass $entity): object {
    throw new UnsupportedDriverActionException($this->errorString('create entities using the generic Entity API'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function entityDelete(string $entity_type, \stdClass $entity): void {
    throw new UnsupportedDriverActionException($this->errorString('delete entities using the generic Entity API'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function startCollectingMail(): void {
    throw new UnsupportedDriverActionException($this->errorString('work with mail'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function stopCollectingMail(): void {
    throw new UnsupportedDriverActionException($this->errorString('work with mail'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function getMail(): array {
    throw new UnsupportedDriverActionException($this->errorString('work with mail'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function clearMail(): void {
    throw new UnsupportedDriverActionException($this->errorString('work with mail'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function sendMail(string $body, string $subject, string $to, string $langcode): bool {
    throw new UnsupportedDriverActionException($this->errorString('work with mail'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function moduleInstall(string $module_name): void {
    throw new UnsupportedDriverActionException($this->errorString('install modules'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function moduleUninstall(string $module_name): void {
    throw new UnsupportedDriverActionException($this->errorString('uninstall modules'), $this);
  }

}
