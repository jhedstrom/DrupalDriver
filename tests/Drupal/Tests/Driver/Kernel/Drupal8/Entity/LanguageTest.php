<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Entity;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Driver\Wrapper\Entity\DriverEntityDrupal8;

/**
 * Tests the driver's handling of language entities.
 *
 * @group driver
 */
class LanguageTest extends DriverEntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language',];

  /**
   * Machine name of the entity type being tested.
   *
   * @string
   */
  protected $entityType = 'configurable_language';

  /**
   * Our entity is a config entity.
   *
   * @boolean
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
    $name = $this->randomMachineName();
    $langcode = 'de';
    $entity = New DriverEntityDrupal8(
      $this->entityType
    );
    $entity->set('id', $name);
    $entity->set('langcode', $langcode);
    $entity->save();

    $language = ConfigurableLanguage::load($name);
    $this->assertNotNull($language);
    $this->assertEquals($langcode, $language->get('langcode'));

    // Check the role can be deleted.
    $entity->delete();
    $language = ConfigurableLanguage::load($name);
    $this->assertNull($language);
  }

}
