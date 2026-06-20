<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core;

use Drupal\Driver\Core\Core;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel test for mail-related methods on Core via the driver.
 *
 * @group core
 */
#[Group('core')]
class CoreMailMethodsKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = ['system'];

  /**
   * The Core driver under test.
   */
  protected Core $core;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['system']);
    $this->core = new Core($this->root);
  }

  /**
   * Tests that 'mailStartCollecting()' swaps the default mail interface.
   */
  public function testMailStartCollectingSwapsInterface(): void {
    $this->core->mailStartCollecting();

    $interface = \Drupal::configFactory()->getEditable('system.mail')->get('interface');
    $this->assertSame('test_mail_collector', $interface['default'] ?? NULL);
  }

  /**
   * Tests the collect -> send -> get -> clear -> stop lifecycle.
   */
  public function testMailLifecycleRoundTrip(): void {
    $this->core->mailStartCollecting();

    $sent = $this->core->mailSend('Body text', 'Subject line', 'to@example.com', 'en');
    $this->assertTrue($sent);

    $mail = $this->core->mailGet();
    $this->assertCount(1, $mail);
    $this->assertSame('to@example.com', $mail[0]['to']);
    $this->assertSame('Subject line', $mail[0]['subject'] ?? $mail[0]['params']['context']['subject'] ?? NULL);

    $this->core->mailClear();
    $this->assertSame([], $this->core->mailGet());

    // 'mailStopCollecting()' must not throw and must leave the collector in a
    // reset state. Detailed pre-start restoration is out of scope for this
    // kernel test because KernelTestBase pre-seeds the mail system.
    $this->core->mailStopCollecting();
    $this->assertSame([], $this->core->mailGet());
  }

  /**
   * Tests that 'mailSend()' carries attachments through to the collected mail.
   */
  public function testMailSendCarriesAttachments(): void {
    $this->core->mailStartCollecting();

    $attachments = [
      [
        'filecontent' => 'PDF bytes',
        'filename' => 'document.pdf',
        'filemime' => 'application/pdf',
      ],
    ];
    $sent = $this->core->mailSend('Body text', 'Subject line', 'to@example.com', 'en', $attachments);
    $this->assertTrue($sent);

    $mail = $this->core->mailGet();
    $this->assertCount(1, $mail);
    $this->assertSame($attachments, $mail[0]['params']['attachments']);
  }

  /**
   * Tests that an explicit empty attachments array omits the params key.
   */
  public function testMailSendWithEmptyAttachmentsOmitsKey(): void {
    $this->core->mailStartCollecting();

    $sent = $this->core->mailSend('Body text', 'Subject line', 'to@example.com', 'en', []);
    $this->assertTrue($sent);

    $mail = $this->core->mailGet();
    $this->assertCount(1, $mail);
    $this->assertArrayNotHasKey('attachments', $mail[0]['params']);
  }

  /**
   * Tests mail collection swaps mailsystem senders when the module is on.
   *
   * Exercises 'replaceMailSenders()', 'mailStartCollectingSystemMail()', and
   * 'mailStopCollectingSystemMail()'.
   */
  public function testMailCollectionRedirectsMailsystemSenders(): void {
    \Drupal::service('module_installer')->install(['mailsystem']);

    $config = \Drupal::configFactory()->getEditable('mailsystem.settings');
    $config->set('defaults.sender', 'php_mail')
      ->set('defaults.formatter', 'php_mail')
      ->save();
    $original = $config->get();

    $this->core->mailStartCollecting();

    $swapped = \Drupal::configFactory()->get('mailsystem.settings')->get();
    $this->assertSame('test_mail_collector', $swapped['defaults']['sender']);

    $this->core->mailStopCollecting();

    $restored = \Drupal::configFactory()->get('mailsystem.settings')->get();
    $this->assertSame($original['defaults']['sender'], $restored['defaults']['sender']);
  }

}
