<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Entity;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Driver\Wrapper\Entity\DriverEntityDrupal8;

/**
 * Tests the driver's handling of language entities.
 *
 * @group driver
 */
class ConfigurableLanguageTest extends DriverEntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language'];

  /**
   * Machine name of the entity type being tested.
   *
   * @var string
   */
  protected $entityType = 'configurable_language';

  /**
   * Our entity is a config entity.
   *
   * @var bool
   */
  protected $config = TRUE;

  /**
   * Test that a language can be created and deleted.
   */
  public function testLanguageCreateDelete() {
    $langcode = 'de';
    $language = (object) [
      'langcode' => $langcode,
    ];
    $languageReturn = $this->driver->languageCreate($language);

    // Test return object has langcode property.
    $this->assertEquals($langcode, $languageReturn->langcode);

    // Test language is created.
    $loadedLanguage = \Drupal::languageManager()->getLanguage($langcode);
    $this->assertNotNull($loadedLanguage);

    // Test false is returned if language already exists.
    $languageReturn = $this->driver->languageCreate($language);
    $this->assertFalse($languageReturn);

    // Check the language can be deleted.
    $this->driver->languageDelete($language);
    $loadedLanguage = ConfigurableLanguage::load($langcode);
    $this->assertNull($loadedLanguage);
  }

  /**
   * Test that a language can be created and deleted.
   */
  public function testLanguageCreateDeleteByWrapper() {
    $langcode = $this->randomMachineName();
    $label = $this->randomMachineName();
    $entity = new DriverEntityDrupal8(
        $this->entityType
    );
    $entity->set('id', $langcode);
    $entity->set('label', $label);
    $entity->save();

    $language = ConfigurableLanguage::load($langcode);
    $this->assertNotNull($language);
    $this->assertEquals($langcode, $language->get('id'));

    // Check the language can be deleted.
    $entity->delete();
    $language = ConfigurableLanguage::load($langcode);
    $this->assertNull($language);
  }

  /**
   * Test that a default label is provided based on langcode.
   */
  public function testLanguageDefaultLabel() {
    $langcode = 'hu';
    $label = 'Hungarian';
    $entity = new DriverEntityDrupal8(
    $this->entityType
    );
    $entity->set('id', $langcode);
    $entity->save();

    $language = ConfigurableLanguage::load($langcode);
    $this->assertNotNull($language);
    $this->assertEquals($label, $language->get('label'));

  }

}
