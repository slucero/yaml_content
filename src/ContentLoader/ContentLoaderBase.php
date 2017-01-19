<?php

namespace Drupal\yaml_content\ContentLoader;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigValueException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldException;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Symfony\Component\Config\Definition\Exception\Exception;
use \Symfony\Component\Yaml\Parser;

/**
 * Class ContentLoaderBase
 * @package Drupal\yaml_content\ContentLoader
 *
 * @todo Extend this class as an EntityLoader to support later support options.
 */
class ContentLoaderBase implements ContentLoaderInterface {

  /**
   * @var \Symfony\Component\Yaml\Parser
   */
  protected $parser;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  protected $parsed_content;

  /**
   * ContentLoader constructor.
   *
   * @todo Refactor to accept Yaml Parser via dependency injection.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->parser = new Parser();
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Set a path prefix for all content files to be loaded from.
   *
   * @param string $path
   */
  public function setContentPath($path) {
    $this->path = $path;
  }

  /**
   * {@inheritdoc}
   */
  public function parseContent($content_file) {
    // @todo Handle parsing failures.
    $this->content_file = $this->path . '/' . $content_file;
    $this->parsed_content = $this->parser->parse(file_get_contents($this->content_file));

    return $this->parsed_content;
  }

  /**
   * {@inheritdoc}
   */
  public function loadContent($content_file, bool $save = TRUE) {
    // @todo Should this method require the already parsed content?
    $content = $this->parseContent($content_file);

    $loaded_content = array();

    // Create each entity defined in the yaml content.
    $context = array();
    foreach ($content as $content_item) {
      $entity = $this->importData($content_item, $context);
      if ($save) {
        $entity->save();
      }
      $loaded_content[] = $entity;
    }

    return $loaded_content;
  }

  /**
   * Import the provided data.
   *
   * Content data may be manipulated using the following keys:
   *   - `#preprocess`: Processors are executed prior to content creation to
   *     dynamically alter the defined configuration.
   *   - `#postprocess`: Processors are executed on the created content items
   *     prior to saving.
   *
   * @param array $content_data
   * @param array $context
   */
  public function importData(array $content_data, $context = array()) {
    // Are we building an entity?
    if (isset($content_data['entity'])) {
      $import_content = $this->buildEntity($content_data['entity'], $content_data, $context);
    }
    // Run processing on content data.
    else {
      // Pass back this content data since it's passed through processing.
      $import_content = $content_data;
    }

    return $import_content;
  }

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
   *   A built and populated entity object containing the imported data.
   */
  public function buildEntity(string $entity_type, array $content_data, array &$context) {
    if (!$this->entityTypeManager->hasDefinition($entity_type)) {
      // @todo Update this to use `t()`.
      throw new Exception(sprintf('Invalid entity type: %s', $entity_type));
    }

    // Load entity type definition.
    $entity_definition = $this->entityTypeManager->getDefinition($entity_type);
    $entity_keys = $entity_definition->getKeys();

    // Load entity type handler.
    $entity_handler = $this->entityTypeManager->getStorage($entity_type);

    // Map generic entity keys into entity-specific values.
    $properties = array();
    foreach ($entity_keys as $source => $target) {
      if (isset($content_data[$source])) {
        $properties[$target] = $content_data[$source];
      }
      elseif (isset($content_data[$target])) {
        $properties[$target] = $content_data[$target];
      }
    }

    // Create entity.
    $context['entity'] = $entity = $entity_handler->create($properties);

    // Keys for exclusion due to use in content structuring.
    $structure_keys = array(
      'entity' => '',
    );

    // All keys to be excluded from content generation.
    $excluded_keys = array_merge(
      $structure_keys,
      $entity_keys,
      $properties
    );

    $this->importEntityFields($entity, array_diff_key($content_data, $excluded_keys), $content_data);

    return $entity;
  }

  /**
   * Import multiple fields on an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param array $field_content
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The `$entity` parameter with all field data imported into it.
   */
  protected function importEntityFields(EntityInterface $entity, array $field_content) {
    // Iterate over each field with data.
    foreach ($field_content as $field_name => $field_data) {
      try {
        if ($entity->hasField($field_name)) {
           // Run import process for each field.
          $this->importFieldData($entity, $field_name, $field_data);
        }
        else {
          throw new FieldException('Undefined field: ' . $field_name);
        }
      }
      catch (FieldException $exception) {
        watchdog_exception('yaml_content', $exception);
      }
      catch (MissingDataException $exception) {
        watchdog_exception('yaml_content', $exception);
      }
      catch (ConfigValueException $exception) {
        watchdog_exception('yaml_content', $exception);
      }
    }

    return $entity;
  }

  /**
   * Import field items for an individual field.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $field_name
   * @param array $field_data
   */
  public function importFieldData(EntityInterface $entity, string $field_name, $field_data) {
    if (!is_array($field_data)) {
      $field_data = array($field_data);
    }

    // Process each individual field item.
    foreach ($field_data as $data_item) {

      // @todo Process complex data values like references.

      // Assign the field item to the field.
      $entity->$field_name->appendItem($data_item);
    }
  }

  /**
   * Populate field content into the provided field.
   *
   * @param $field
   * @param array $field_data
   *
   * @todo Handle field data types more dynamically with typed data.
   */
  protected function populateField($field, array &$field_data) {
    // Iterate over each value.
    foreach ($field_data as &$field_item) {

      $is_reference = isset($field_item['entity']);

      if ($is_reference) {
        // @todo Dynamically determine the type of reference.

        // Create entity.
        $field_item = $this->buildEntity($field_item['entity'], $field_item);
      }

      $field->appendItem($field_item);
    }
  }
}
