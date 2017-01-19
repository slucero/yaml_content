<?php

namespace Drupal\yaml_content\ContentLoader;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

interface ContentLoaderInterface {

  /**
   * Parse the given yaml content file into an array.
   *
   * @param $content_file
   *   A file name for the content file to be loaded.
   * @return array
   *   The parsed content array from the file.
   */
  function parseContent($content_file);

  /**
   * Load all demo content for this loader.
   *
   * @param $content_file
   *   A file name for the content file to be loaded.
   * @param bool $save
   *   Flag indicator to determine whether loaded entities should be saved.
   *
   * @return array
   *   An array of generated entity Ids.
   */
  function loadContent($content_file, bool $save = TRUE);

  /**
   * Build an entity from the provided content data.
   *
   * @param string $entity_type
   * @param array $content_data
   *   Parameters:
   *     - `entity`: (required) The entity type machine name.
   *     - `bundle`: (required) The bundle machine name.
   *     - Additional field and property data keyed by field or property name.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  function buildEntity(string $entity_type, array $content_data, array &$context);
}