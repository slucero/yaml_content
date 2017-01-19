<?php

namespace Drupal\yaml_content\ContentLoader;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

interface ContentLoaderInterface {

  public function __construct(PluginManagerInterface $processorPluginManager, EntityTypeManagerInterface $entityTypeManager);

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
   * @param $entity_type
   * @param array $content_data
   * @param array $context
   * @return \Drupal\Core\Entity\EntityInterface
   */
  function buildEntity($entity_type, array $content_data, array &$context);
}