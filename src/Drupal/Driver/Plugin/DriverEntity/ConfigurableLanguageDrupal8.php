<?php

namespace Drupal\Driver\Plugin\DriverEntity;

use Drupal\Driver\Plugin\DriverEntityPluginDrupal8Base;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Language\LanguageInterface;

/**
 * A driver field plugin used to test selecting an arbitrary plugin.
 *
 * @DriverEntity(
 *   id = "configurable_language8",
 *   version = 8,
 *   weight = -100,
 *   entityTypes = {
 *     "configurable_language",
 *   },
 * )
 */
class ConfigurableLanguageDrupal8 extends DriverEntityPluginDrupal8Base {

  /**
   * The langcode of the attached language.
   *
   * @var string
   *
   * @deprecated Use id() instead.
   */
  public $langcode;

  /**
   * {@inheritdoc}
   */
  public function load($entityId) {
    $entity = parent::load($entityId);
    $this->langcode = $this->id();
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    // For new entities, fill in details for known languages.
    // This is something that ConfigurableLanguage::createFromLangcode() does,
    // in order to allow enabling a language by langcode alone.
    if ($this->getEntity()->isNew()) {
      $langcode = $this->getEntity()->id();
      $standard_languages = LanguageManager::getStandardLanguageList();
      if (isset($standard_languages[$langcode])) {
        $label = $this->getEntity()->get('label');
        // Label defaults to langcode.
        if (empty($label) || $label === $langcode) {
          $this->set('label', $standard_languages[$langcode][0]);
        }
        if (empty($this->getEntity()->get('direction'))) {
          $direction = isset($standard_languages[$langcode][2]) ? $standard_languages[$langcode][2] : LanguageInterface::DIRECTION_LTR;
          $this->set('direction', $direction);
        }
      }
    }
    parent::save();
    $this->langcode = $this->id();
  }

}
