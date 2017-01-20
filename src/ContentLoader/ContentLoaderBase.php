<?php

namespace Drupal\yaml_content\ContentLoader;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
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
    foreach ($content as $content_item) {
      $entity = $this->importEntity($content_item);
      if ($save) {
        $entity->save();
      }
      $loaded_content[] = $entity;
    }

    return $loaded_content;
  }

  /**
   * Load an entity from a loaded import data outline.
   *
   * @param array $content_data
   *   The loaded array of content data to populate into this entity.
   *
   *   Required keys:
   *     - `entity`: The entity type machine name.
   *
   * @return EntityInterface
   *
   * @throws \Exception
   */
  public function importEntity(array $content_data) {
    // @todo Preprocess entity content data.

    // @todo Validate entity information for building.
    if (!isset($content_data['entity'])) {
      throw new \Exception('An entity type is required in the "entity" key.');
    }
    else {
      $entity_type = $content_data['entity'];
    }

    if (!$this->entityTypeManager->hasDefinition($entity_type)) {
      // @todo Update this to use `t()`.
      throw new \Exception(sprintf('Invalid entity type: %s', $entity_type));
    }

    // Build the basic entity structure.
    $entity = $this->buildEntity($entity_type, $content_data);

    // @todo Break this out into `$this->importEntityFields()`.
    // Import the entity fields if applicable.
    if ($entity instanceof FieldableEntityInterface) {

      $field_definitions = $entity->getFieldDefinitions();

      // Iterate across each field value in the import content.
      foreach (array_intersect_key($content_data, $field_definitions) as $field_name => $field_data) {
        // Ensure data is wrapped as an array to handle field values as a list.
        if (!is_array($field_data)) {
          $field_data = [$field_data];
        }

        $this->importEntityField($field_data, $entity, $field_definitions[$field_name]);
      }
    }

    // @todo Postprocess loaded entity object.

    return $entity;
  }

  /**
   * Process import data into an appropriate field value and assign it.
   *
   * @param array $field_data
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   */
  public function importEntityField(array $field_data, EntityInterface $entity, FieldDefinitionInterface $field_definition) {
    // @todo Preprocess field content data.

    // Iterate over each field value.
    foreach ($field_data as $field_item) {
      $field_value = $this->importFieldItem($field_item, $entity, $field_definition);

      // Assign or append field item value.
      $this->assignFieldValue($entity, $field_definition->getName(), $field_value);
    }

    // @todo Postprocess loaded field object.
  }

  /**
   * Process import data for an individual field list item value.
   *
   * @param $field_item_data
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *
   * @return mixed
   *   The processed field item value for storage in the field.
   */
  public function importFieldItem($field_item_data, EntityInterface $entity, FieldDefinitionInterface $field_definition) {
    // @todo Preprocess field item data.

    // Is it an entity reference?
    if (is_array($field_item_data) && isset($field_item_data['entity'])) {
      $item_value = $this->importEntity($field_item_data);
    }
    else {
      $item_value = $field_item_data;
    }

    // @todo Postprocess field item object.

    return $item_value;
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
  public function buildEntity(string $entity_type, array $content_data, array &$context = array()) {
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

    // Create the entity.
    $entity = $entity_handler->create($properties);

    return $entity;
  }

  /**
   * Set or assign a field value based on field cardinality.
   *
   * @param FieldableEntityInterface $entity
   * @param string $field_name
   * @param $value
   */
  public function assignFieldValue(FieldableEntityInterface $entity, string $field_name, $value) {
    $field = $entity->$field_name;

    // Get the field cardinality to determine whether or not a value should be
    // 'set' or 'appended' to.
    $cardinality = $field->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getCardinality();

    // If the cardinality is 0, throw an exception.
    if (!$cardinality) {
      throw new \InvalidArgumentException("'{$field->getName()}' cannot hold any values.");
    }

    // If the cardinality is set to 1, set the field value directly.
    if ($cardinality == 1) {
      $field->setValue($value);
    }
    else {
      $field->appendItem($value);
    }

  }
}
