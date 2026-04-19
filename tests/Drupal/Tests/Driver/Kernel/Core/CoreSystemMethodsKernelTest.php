<?php

declare(strict_types=1);

namespace Drupal\Tests\Driver\Kernel\Core;

use Drupal\Driver\Core\Core;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel tests for system-level methods on Core via the driver.
 *
 * Covers module install/uninstall, language create/delete, module list
 * retrieval, and the account switcher login/logout pair in a single class
 * to amortise per-method KernelTestBase bootstrap cost.
 */
#[Group('core')]
class CoreSystemMethodsKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var array<string>
   */
  protected static $modules = [
    'system',
    'user',
    'language',
  ];

  /**
   * The Core driver under test.
   */
  protected Core $core;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    // users_data is a legacy schema used by user module uninstall hooks;
    // install it so moduleUninstall does not crash on missing table.
    $this->installSchema('user', ['users_data']);
    $this->installConfig(['system', 'language']);

    $this->core = new Core($this->root);
  }

  /**
   * Tests that moduleInstall and moduleUninstall flip module state.
   *
   * 'contact' is chosen because installing it does not create dependent
   * config (unlike 'filter', which creates filter plugins referenced by the
   * default format and blocks later uninstall in kernel tests).
   */
  public function testModuleInstallAndUninstall(): void {
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('contact'), 'contact is not installed at setUp.');

    $this->core->moduleInstall('contact');
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('contact'), 'moduleInstall enabled contact.');

    $this->core->moduleUninstall('contact');
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('contact'), 'moduleUninstall disabled contact.');
  }

  /**
   * Tests that getModuleList exposes enabled modules.
   */
  public function testGetModuleListIncludesEnabledModules(): void {
    $modules = $this->core->getModuleList();

    $this->assertContains('system', $modules);
    $this->assertContains('user', $modules);
    $this->assertContains('language', $modules);
  }

  /**
   * Tests languageCreate with a fresh language and languageDelete removes it.
   */
  public function testLanguageLifecycle(): void {
    $this->assertNull(ConfigurableLanguage::load('fr'));

    $result = $this->core->languageCreate((object) ['langcode' => 'fr']);
    $this->assertNotFalse($result, 'languageCreate returned the stub for a new language.');
    $this->assertInstanceOf(ConfigurableLanguage::class, ConfigurableLanguage::load('fr'));

    $this->core->languageDelete((object) ['langcode' => 'fr']);
    $this->assertNull(ConfigurableLanguage::load('fr'));
  }

  /**
   * Tests that languageCreate returns FALSE when the language already exists.
   */
  public function testLanguageCreateReturnsFalseWhenLanguageExists(): void {
    $this->core->languageCreate((object) ['langcode' => 'fr']);

    $second = $this->core->languageCreate((object) ['langcode' => 'fr']);

    $this->assertFalse($second);
  }

  /**
   * Tests that login switches the active account and logout restores it.
   */
  public function testLoginAndLogoutSwitchesAccount(): void {
    $alice = User::create(['name' => 'alice', 'status' => 1]);
    $alice->save();

    $before_uid = \Drupal::currentUser()->id();

    $this->core->login((object) ['uid' => $alice->id()]);
    $this->assertSame((int) $alice->id(), (int) \Drupal::currentUser()->id(), 'login switched to alice.');

    $this->core->logout();
    $this->assertSame((int) $before_uid, (int) \Drupal::currentUser()->id(), 'logout restored the original account.');
  }

}
