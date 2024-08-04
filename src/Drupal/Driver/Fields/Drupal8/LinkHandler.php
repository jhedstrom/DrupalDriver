<?php

namespace Drupal\Driver\Fields\Drupal8;

/**
 * Link field handler for Drupal 8.
 */
class LinkHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $return = [];
    foreach ($values as $value) {
      $uri = $value[1];

      // If the uri has no scheme (and is not protocol relative) attempt to find
      // a node with that label.
      if (empty($uri_parts['scheme']) && strpos($uri, '//') !== 0) {
        $entity_type_id = 'node';
        $query = \Drupal::entityQuery($entity_type_id)->condition('title', $uri);
        $entities = $query->execute();
        if (!empty($entities)) {
          $nid = array_shift($entities);
          $uri = 'entity:' . $entity_type_id . '/' . $nid;
        }
      }

      // 'options' is required to be an array, otherwise the utility class
      // Drupal\Core\Utility\UnroutedUrlAssembler::assemble() will complain.
      $options = [];
      if (!empty($value[2])) {
        parse_str($value[2], $options);
      }
      $return[] = [
        'options' => $options,
        'title' => $value[0],
        'uri' => $uri,
      ];
    }
    return $return;
  }

}
