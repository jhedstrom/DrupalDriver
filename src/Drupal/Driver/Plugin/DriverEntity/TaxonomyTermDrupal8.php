<?php
namespace Drupal\Driver\Plugin\DriverEntity;

use Drupal\Driver\Plugin\DriverEntityPluginDrupal8Base;

/**
 * A driver field plugin used to test selecting an arbitrary plugin.
 *
 * @DriverEntity(
 *   id = "taxonomy_term8",
 *   version = 8,
 *   weight = -100,
 *   entityTypes = {
 *     "taxonomy_term",
 *   },
 * )
 */
class TaxonomyTermDrupal8 extends DriverEntityPluginDrupal8Base {

  /**
   * The id of the attached term.
   *
   * @var integer;
   *
   * @deprecated Use id() instead.
   */
  public $tid;

  /**
   * {@inheritdoc}
   */
  public function save() {
    parent::save();
    $this->tid = $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleKeyLabels() {
    // Previously we made 'vocabulary_machine_name' available as a more
    // human-friendly alternative to 'vid' for the bundle field identifier.
    // This is now unnecessary as the label 'vocabulary' is available
    // automatically, but it is supported here for backwards-compatibility.
    $bundleKeyLabels = parent::getBundleKeyLabels();
    $bundleKeyLabels[] = 'vocabulary_machine_name';
    return $bundleKeyLabels;
  }
}