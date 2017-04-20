<?php

namespace Drupal\yaml_content\ContentLoader;

/**
 * Interface for loading and parsing content from YAML files.
 */
interface ContentLoaderInterface {

  /**
   * Parse the given yaml content file into an array.
   *
   * @param string $content_file
   *   A file name for the content file to be loaded. The file is assumed to be
   *   located within a directory set by `setPath()`.
   *
   * @return array
   *   The parsed content array from the file.
   */
  public function parseContent($content_file);

  /**
   * Load all demo content for this loader.
   *
   * @param $content_file
   *   A file name for the content file to be loaded. The file is assumed to be
   *   located within cfr_demo_content/content/.
   *
   * @return array
   *   An array of generated entity Ids.
   */
  public function loadContent($content_file);

  /**
   * Build an entity from the provided content data.
   *
   * @param $entity_type
   * @param array $content_data
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function buildEntity($entity_type, array $content_data);

}
