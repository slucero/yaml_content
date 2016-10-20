<?php

namespace Drupal\yaml_content\ContentLoader;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigValueException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldException;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Symfony\Component\Config\Definition\Exception\Exception;
use \Symfony\Component\Yaml\Parser;

/**
 * Class ContentLoader
 * @package Drupal\yaml_content\ContentLoader
 *
 * @todo Extend this class as an EntityLoader to support later support options.
 */
class ContentLoader implements ContentLoaderInterface {

  /**
   * @var \Symfony\Component\Yaml\Parser
   */
  protected $parser;

  /**
   * @var \Drupal\yaml_content\ContentProcessorPluginManager
   */
  protected $processorPluginManager;

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
  public function __construct(PluginManagerInterface $processorPluginManager, EntityTypeManagerInterface $entityTypeManager) {
    $this->parser = new Parser();
    $this->processorPluginManager = $processorPluginManager;
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
  protected function loadProcessor($processor_id, array &$context) {
    $processor_definition = $this->processorPluginManager->getDefinition($processor_id);

    // @todo Implement exception class for invalid processors.
    if (!$processor_definition->import) {
      throw new \Exception(sprintf('The %s processor does not support import operations.', $processor_id));
    }

    // Instantiate the processor plugin with default config values.
    $processor = $this->processorPluginManager->createInstance($processor_id, $processor_definition['config']);

    // @todo Set and validate context values.

    return $processor;
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
    // Check for and run any pre-processing steps.
    if (isset($content_data['#preprocess'])) {
      $this->preprocessData($content_data, $context);
    }

    // Is there an entity available?
    if (isset($context['entity'])) {
      // Pass back this content data since it's passed through processing.
      $import_content = $content_data;
    }
    // Are we building an entity?
    elseif (isset($content_data['entity'])) {
      $import_content = $this->buildEntity($content_data['entity'], $content_data, $context);
    }
    // I don't know what we're trying to do here.
    else {
      throw new Exception("Unknown data state.\n" . print_r($content_data, TRUE));
    }

    // Check for and run any post-processing steps.
    if (isset($content_data['#postprocess'])) {
      $this->postprocessData($import_content, $content_data, $context);
    }

    return $import_content;
  }

  /**
   * Build an entity from the provided content data.
   *
   * @param $entity_type
   * @param array $content_data
   *   Parameters:
   *     - `entity`: (required) The entity type machine name.
   *     - `bundle`: (required) The bundle machine name.
   *     - Additional field and property data keyed by field or property name.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function buildEntity($entity_type, array $content_data, array &$context) {
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
    }

    // Create entity.
    $context['entity'] = $entity = $entity_handler->create($properties);

    // Populate additional data not included in properties.
    foreach (array_diff_key($content_data, $entity_keys, $properties) as $field_name => $field_data) {
      try {
        if ($entity->$field_name) {
          $field_data = $this->importData($content_data[$field_name], $context);

          // @todo Validate data assignment.
          $entity->$field_name->appendItem($field_data);
        }
        else {
          throw new FieldException('Undefined field: ' . $field_name);
        }
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
   * Evaluate the current import data array and run any preprocessing needed.
   *
   * Any data keys starting with '#' indicate preprocessing instructions that
   * should be executed on the data prior to import. The data array is altered
   * directly and fully prepared for import.
   *
   * @param array $import_data
   *   The current content data being evaluated for import. This array is
   *   altered directly and returned without the processing key.
   * @param string $operations_key
   *   The key for the processing operations to be executed.
   *
   * @throws Exception
   */
  public function preprocessData(array &$import_data, array $context) {
    if (!is_array($import_data['#preprocess'])) {
      throw new Exception('Processing instructions must be provided as an array.');
    }

    // Execute all processing actions.
    foreach ($import_data['#preprocess'] as $key => $data) {
      // @todo Execute preprocess actions.
    }

    // Remove executed processing data.
    unset($import_data['#preprocess']);
  }

  /**
   * Evaluate the current import data array and run any preprocessing needed.
   *
   * Any data keys starting with '#' indicate preprocessing instructions that
   * should be executed on the data prior to import. The data array is altered
   * directly and fully prepared for import.
   *
   * @param array $import_data
   *   The current content data being evaluated for import. This array is
   *   altered directly and returned without the processing key.
   * @param string $operations_key
   *   The key for the processing operations to be executed.
   *
   * @throws Exception
   */
  public function postprocessData(&$import_content, array $content_data, array $context) {
    if (!is_array($content_data['#postprocess'])) {
      throw new Exception('Processing instructions must be provided as an array.');
    }

    // Execute all processing actions.
    foreach ($content_data['#postprocess'] as $key => $data) {
      // @todo Execute postprocess actions.
    }
  }

  /**
   * Filter array keys to only those starting with '#'.
   *
   * Array keys starting with '#' are used to indicate special data processing
   * is needed. This function is a helper to identify only those keys indicating
   * special processing instructions.
   *
   * The original array passed into this remains unaltered.
   *
   * @param array $data
   *   The content data array currently being processed.
   *
   * @return array
   *   An array of only the keys starting with '#'.
   */
  protected function getProcessKeys(array $data) {
    // Filter only array keys starting with '#'.
    $process_keys = array_filter($data, function($key) {
      return (substr($key, 0, 1) == '#');
    }, ARRAY_FILTER_USE_KEY);

    return $process_keys;
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
}
