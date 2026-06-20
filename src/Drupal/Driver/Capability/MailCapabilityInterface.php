<?php

declare(strict_types=1);

namespace Drupal\Driver\Capability;

/**
 * Capability: collect and send mail.
 */
interface MailCapabilityInterface {

  /**
   * Starts collecting outgoing mail for later inspection.
   */
  public function mailStartCollecting(): void;

  /**
   * Stops collecting mail and restores normal delivery.
   */
  public function mailStopCollecting(): void;

  /**
   * Returns the mail that has been collected.
   *
   * @return array<int, array<string, mixed>>
   *   Collected mail messages, each formatted as a Drupal mail array.
   */
  public function mailGet(): array;

  /**
   * Empties the collected-mail store.
   */
  public function mailClear(): void;

  /**
   * Sends a mail message.
   *
   * @param string $body
   *   The message body.
   * @param string $subject
   *   The message subject.
   * @param string $to
   *   The recipient email address.
   * @param string $langcode
   *   The language code for subject and body.
   * @param array<int, array<string, mixed>> $attachments
   *   Optional attachments. Each is an associative array carrying at least a
   *   'filename', following Drupal's standard attachment structure. When
   *   provided, they are recorded on the sent message's 'params' under the
   *   'attachments' key.
   *
   * @return bool
   *   TRUE if the message was accepted for delivery.
   */
  public function mailSend(string $body, string $subject, string $to, string $langcode, array $attachments = []): bool;

}
