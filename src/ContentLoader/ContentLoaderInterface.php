<?php

namespace Drupal\yaml_content\ContentLoader;

interface ContentLoaderInterface {

  public function __construct();

  /**
   * Parse the given yaml content file into an array.
   *
   * @param $content_file
   *   A file name for the content file to be loaded. The file is assumed to be
   *   located within cfr_demo_content/content/.
   * @return array
   *   The parsed content array from the file.
   */
  function parseContent($content_file);

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
  function loadContent($content_file);

  /**
   * Build an entity from the provided content data.
   *
   * @param $entity_type
   * @param array $content_data
   * @return \Drupal\Core\Entity\EntityInterface
   */
  function buildEntity($entity_type, array $content_data);
}