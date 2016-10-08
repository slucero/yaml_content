<?php

namespace Drupal\yaml_content\ContentLoader;

use Drupal\Core\Config\ConfigValueException;
use Drupal\Core\Field\FieldException;
use Drupal\Core\TypedData\Exception\MissingDataException;
use \Symfony\Component\Yaml\Parser;

class ContentLoader implements ContentLoaderInterface {

  /**
   * @var \Symfony\Component\Yaml\Parser
   */
  protected $parser;

  /**
   * @var \Drupal\yaml_content\ContentProcessorPluginManager
   */
  protected $pluginManager;

  protected $parsed_content;

  /**
   * ContentLoader constructor.
   *
   * @todo Register via services.
   */
  public function __construct() {
    $this->parser = new Parser();
    $this->pluginManager = \Drupal::service('plugin.manager.yaml_content');
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
    $this->content_file = $this->path . '/' . $content_file;
    $this->parsed_content = $this->parser->parse(file_get_contents($this->content_file));

    return $this->parsed_content;
  }

  /**
   * {@inheritdoc}
   */
  public function loadContent($content_file) {
    $content = $this->parseContent($content_file);

    $loaded_content = array();

    // Create each entity defined in the yml content.
    foreach ($content as $content_item) {
      $entity = $this->buildEntity($content_item['entity'], $content_item);
      $entity->save();
      $loaded_content[] = $entity->id();
    }

    return $loaded_content;
  }

  /**
   * Load the processor plugin for use on the import content.
   *
   * Load the processor plugin and configure any relevant context available in
   * the provided `$context` parameter.
   *
   * @param $processor_id
   * @param array $context
   * @throws \Exception
   *
   * @todo Handle PluginNotFoundException.
   */
  protected function loadProcessor($processor_id, array $context) {
    $processor_definition = $this->pluginManager->getDefinition($processor_id);

    // @todo Implement exception class for invalid processors.
    if (!$processor_definition->import) {
      throw new \Exception(sprintf('The %s processor does not support import operations.', $processor_id));
    }

    $processor = $this->pluginManager->createInstance($processor_id, $context);

    // @todo Set and validate context values.

    return $processor;
  }

  /**
   * Build an entity from the provided content data.
   *
   * @param $entity_type
   * @param array $content_data
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function buildEntity($entity_type, array $content_data) {
    // Load entity type handler.
    $entity_handler = \Drupal::entityTypeManager()->getStorage($entity_type);

    // Verify required content data.

    // Parse properties for creation and fields for processing.
    $properties = array();
    $fields = array();
    foreach (array_keys($content_data) as $key) {
      if (strpos($key, 'field') === 0) {
        $fields[$key] = $content_data[$key];
      }
      else {
        $properties[$key] = $content_data[$key];
      }
    };

    // Create entity.
    $entity = $entity_handler->create($properties);

    // Populate fields.
    foreach ($fields as $field_name => $field_data) {
      try {
        if ($entity->$field_name) {
          $this->populateField($entity->$field_name, $field_data);
        }
        else {
          throw new FieldException('Undefined field: ' . $field_name);
        }
      }
      catch (MissingDataException $exception) {
        watchdog_exception('cfr_demo_content', $exception);
      }
      catch (ConfigValueException $exception) {
        watchdog_exception('cfr_demo_content', $exception);
      }
    }

    return $entity;
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

      // Preprocess field data.
      $this->preprocessFieldData($field, $field_item);

      $is_reference = isset($field_item['entity']);

      if ($is_reference) {
        // @todo Dynamically determine the type of reference.

        // Create entity.
        $field_item = $this->buildEntity($field_item['entity'], $field_item);
      }

      $field->appendItem($field_item);
    }
  }


  /**
   * Run any designated preprocessors on the provided field data.
   *
   * Preprocessors are expected to be provided in the following format:
   *
   * ```yaml
   *   '#process':
   *     callable: '<callable string>'
   *     args:
   *       - <callable argument 1>
   *       - <callable argument 2>
   *       - <...>
   * ```
   *
   * The callable function receives the following arguments:
   *
   *   - `$field`
   *   - `$field_data`
   *   - <callable argument 1>
   *   - <callable argument 2>
   *   - <...>
   *
   * The `$field_data` array is passed by reference and may be modified directly
   * by the callable implementation.
   *
   * @param $field
   * @param array $field_data
   */
  protected function preprocessFieldData($field, array &$field_data) {
    // Check for a callable processor defined at the value level.
    if (isset($field_data['#process'])) {
      $callable = $field_data['#process']['callable'];

      if (is_callable($callable)) {
        // Append callback arguments to field object and value data.
        $args = array_merge([$field, &$field_data], $field_data['#process']['args'] ?? []);
        call_user_func_array($callable, $args);
      }
      else {
        throw new ConfigValueException('Uncallable processor provided: ' . $callable);
      }
    }
  }

  /**
   * Processor function for querying and loading a referenced entity.
   *
   * @param $field
   * @param array $field_data
   * @param $entity_type
   * @param array $filter_params
   * @return array|int
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *
   * @see ChapterLoader::preprocessFieldData().
   */
  static function loadReference($field, array &$field_data, $entity_type, array $filter_params) {

    $query = \Drupal::entityQuery($entity_type);

    // Apply filter parameters.
    foreach ($filter_params as $property => $value) {
      $query->condition($property, $value);
    }

    $entity_ids = $query->execute();

    if (empty($entity_ids)) {
      // Build parameter output description for error message.
      $error_params = [
        '[',
        '  "entity_type" => ' . $entity_type . ',',
      ];
      foreach ($filter_params as $key => $value) {
        $error_params[] = sprintf("  '%s' => '%s',", $key, $value);
      }
      $error_params[] = ']';
      $param_output = join("\n", $error_params);

      throw new MissingDataException(__CLASS__ . ': Unable to find referenced content: ' . $param_output);
    }

    // Use the first match for our value.
    $field_data['target_id'] = array_shift($entity_ids);

    // Remove process data to avoid issues when setting the value.
    unset($field_data['#process']);

    return $entity_ids;
  }
}