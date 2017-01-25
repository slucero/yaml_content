<?php

namespace Drupal\yaml_content\ContentLoader;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\yaml_content\ImportProcessorInterface;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class ProcessedContentLoader
 * @package Drupal\yaml_content\ContentLoader
 *
 * An extension of the ContentLoaderBase class to include pre and post
 * processing of content through plugins.
 *
 * @todo Extend this class as an EntityLoader to support later support options.
 */
class ProcessedContentLoader extends ContentLoaderBase {
  /**
   * @var \Drupal\yaml_content\ContentProcessorPluginManager
   */
  protected $processorPluginManager;

  protected $parsed_content;

  /**
   * ProcessedContentLoader constructor.
   *
   * Overrides the constructor to pull in the content processor plugin manager.
   *
   * @param EntityTypeManagerInterface $entityTypeManager
   * @param PluginManagerInterface $processorPluginManager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, PluginManagerInterface $processorPluginManager) {
    parent::__construct($entityTypeManager);

    $this->processorPluginManager = $processorPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public function importEntity(array $content_data) {
    // Preprocess the content data.
    $entity_context = [];
    $this->preprocessData($content_data, $entity_context);

    $entity = parent::importEntity($content_data, $entity_context);

    // Postprocess loaded entity object.
    $this->postprocessData($content_data, $entity, $entity_context);

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function importEntityField(array $field_data, EntityInterface $entity, FieldDefinitionInterface $field_definition) {
    // Preprocess field data.
    $field_context['entity'] = $entity;
    $field_context['field'] = $field_definition;
    $this->preprocessData($field_data, $field_context);

    parent::importEntityField($field_data, $entity, $field_definition);

    // Postprocess loaded field data.
    $this->postprocessData($field_data, $entity, $field_context);
  }

  /**
   * {@inheritdoc}
   */
  public function importFieldItem($field_item_data, EntityInterface $entity, FieldDefinitionInterface $field_definition) {
    // Preprocess field data.
    $field_context['entity'] = $entity;
    $field_context['field'] = $field_definition;
    if (is_array($field_item_data)) {
      $this->preprocessData($field_item_data, $field_context);
    }

    $item_value = parent::importFieldItem($field_item_data, $entity, $field_definition);

    // Postprocess loaded field data.
    if (is_array($field_item_data)) {
      $this->postprocessData($field_item_data, $item_value, $field_context);
    }

    return $item_value;
  }

  /**
   * Load the processor plugin for use on the import content.
   *
   * Load the processor plugin and configure any relevant context available in
   * the provided `$context` parameter.
   *
   * @param $processor_id
   * @param array $context
   *
   * @return mixed
   *
   * @throws \Exception
   *
   * @todo Handle PluginNotFoundException.
   */
  public function loadProcessor($processor_id, array &$context) {
    $processor_definition = $this->processorPluginManager->getDefinition($processor_id);

    // @todo Implement exception class for invalid processors.
    if (!$processor_definition['import']) {
      throw new \Exception(sprintf('The %s processor does not support import operations.', $processor_id));
    }

    // Instantiate the processor plugin with default config values.
    $processor = $this->processorPluginManager->createInstance($processor_id);

    // Set and validate context values.
    foreach ($processor_definition['context'] as $name => $definition) {
      if (isset($context[$name])) {
        // @todo Validate context types and values.
        $processor->setContextValue($name, $context[$name]);
      }
      else {
        // Handle missing required contexts.
        if ($definition->isRequired()) {
          // @todo Handle this exception.
        }
      }
    }

    return $processor;
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
   * @param array $context
   *   Contextual data passed by reference to preprocessing plugins.
   *
   * @throws Exception
   */
  public function preprocessData(array &$import_data, array &$context) {
    // Abort if there are no preprocessing instructions.
    if (!isset($import_data['#preprocess'])) {
      return;
    }

    if (!is_array($import_data['#preprocess'])) {
      throw new Exception('Preprocessing instructions must be provided as an array.');
    }

    // Execute all processing actions.
    foreach ($import_data['#preprocess'] as $key => $data) {
      // @todo Execute preprocess actions.
      if (isset($data['#plugin'])) {
        // Expose preprocess configuration into context for the plugin.
        $processor_context = array_merge($context, $data);

        // Load the plugin.
        $processor = $this->loadProcessor($data['#plugin'], $processor_context);

        assert($processor instanceof ImportProcessorInterface,
          'Preprocess plugin [' . $data['#plugin'] . '] failed to load a valid ImportProcessor plugin.');

        // @todo Execute plugin on $import_data.
        $processor->preprocess($import_data);
      }
      else {
        throw new Exception('Preprocessing instructions require a defined "#plugin" identifier.');
      }
    }

    // Remove executed preprocess data.
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
   * @param $loaded_content
   *   The loaded content object generated from the given import data.
   * @param array $context
   *   Contextual data passed by reference to preprocessing plugins.
   *
   * @throws Exception
   */
  public function postprocessData(array &$import_data, &$loaded_content, array &$context) {
    // Abort if there are no postprocessing instructions.
    if (!isset($import_data['#postprocess'])) {
      return;
    }

    if (!is_array($import_data['#postprocess'])) {
      throw new Exception('Postprocessing instructions must be provided as an array.');
    }

    // Execute all processing actions.
    foreach ($import_data['#postprocess'] as $key => $data) {
      // @todo Execute postprocess actions.
      if (isset($data['#plugin'])) {
        // Load the plugin.
        $processor = $this->loadProcessor($data['#plugin'], $context);

        assert($processor instanceof ImportProcessorInterface,
          'Postprocess plugin [' . $data['#plugin'] . '] failed to load a valid ImportProcessor plugin.');

        // @todo Provide required context as defined by plugin definition.

        // @todo Execute plugin on $loaded_content.
      }
      else {
        throw new Exception('Postprocessing instructions require a defined "#plugin" identifier.');
      }
    }
  }
}
