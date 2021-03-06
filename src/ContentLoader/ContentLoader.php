<?php

namespace Drupal\yaml_content\ContentLoader;

use Drupal\Core\Config\ConfigValueException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\FieldException;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\Yaml\Parser;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * ContentLoader class for parsing and importing YAML content.
 */
class ContentLoader implements ContentLoaderInterface {

  /**
   * The entity type manager interface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler interface for invoking any hooks.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * YAML parser.
   *
   * @var \Symfony\Component\Yaml\Parser
   */
  protected $parser;

  /**
   * The parsed content.
   *
   * @var mixed
   */
  protected $parsedContent;

  /**
   * Boolean value of whether other not to update existing content.
   *
   * @var bool
   */
  protected $existenceCheck;

  /**
   * ContentLoader constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Drupal module handler service.
   *
   * @todo Register via services.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    $this->parser = new Parser();
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Set a path prefix for all content files to be loaded from.
   *
   * @param string $path
   *   The path for where all content files will be loaded from.
   */
  public function setContentPath($path) {
    $this->path = $path;
  }

  /**
   * Returns whether or not the system should check for previous demo content.
   *
   * @return bool
   *   The true/false value of existence check.
   */
  public function existenceCheck() {
    return $this->existenceCheck;
  }

  /**
   * Set the whether or not the system should check for previous demo content.
   *
   * @param bool $existence_check
   *   The true/false value of existence check.
   */
  public function setExistenceCheck($existence_check) {
    $this->existenceCheck = $existence_check;
  }

  /**
   * {@inheritdoc}
   */
  public function parseContent($content_file) {
    $this->contentFile = $this->path . '/' . $content_file;
    $this->parsedContent = $this->parser->parse(file_get_contents($this->contentFile));

    // Never leave this as null, even on a failed parsing process.
    // @todo Output a warning for empty content files or failed parsing.
    $this->parsedContent = isset($this->parsedContent) ? $this->parsedContent : [];

    return $this->parsedContent;
  }

  /**
   * {@inheritdoc}
   */
  public function loadContent($content_file, $skip_existence_check = TRUE) {
    $this->setExistenceCheck($skip_existence_check);
    $content_data = $this->parseContent($content_file);

    $loaded_content = [];

    // Create each entity defined in the yml content.
    foreach ($content_data as $content_item) {
      $entity = $this->buildEntity($content_item['entity'], $content_item);
      $entity->save();
      $loaded_content[] = $entity;
    }

    // Trigger a hook for post-import processing.
    $this->moduleHandler->invokeAll('yaml_content_post_import',
      [$content_file, &$loaded_content, $content_data]);

    return $loaded_content;
  }

  /**
   * Build an entity from the provided content data.
   *
   * @param string $entity_type
   *   The entity type.
   * @param array $content_data
   *   The array of content data to be parsed.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created entity from the parsed content data.
   */
  public function buildEntity($entity_type, array $content_data) {
    // Load entity type handler.
    $entity_handler = $this->entityTypeManager->getStorage($entity_type);

    // Verify required content data.
    // Parse properties for creation and fields for processing.
    $properties = [];
    $fields = [];
    foreach (array_keys($content_data) as $key) {
      if (strpos($key, 'field') === 0) {
        $fields[$key] = $content_data[$key];
      }
      else {
        $properties[$key] = $content_data[$key];
      }
    };

    // If it is a 'user' entity, append a timestamp to make the username unique.
    if ($entity_type == 'user' && isset($properties['name'][0]['value'])) {
      $properties['name'][0]['value'] .= '_' . time();
    }
    // Create the entity only if we do not want to check for existing nodes.
    if (!$this->existenceCheck()) {
      $entity = $entity_handler->create($properties);
    }
    else {
      $entity = $this->entityExists($entity_type, $content_data);

      // Create the entity if no existing one was found.
      if ($entity === FALSE) {
        $entity = $entity_handler->create($properties);
      }
    }

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
   * @param object $field
   *   The entity field object.
   * @param array $field_data
   *   The field data.
   *
   * @todo Handle field data types more dynamically with typed data.
   */
  protected function populateField($field, array &$field_data) {
    // Get the field cardinality to determine whether or not a value should be
    // 'set' or 'appended' to.
    $cardinality = $field->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getCardinality();

    // Gets the count of the field data array.
    $field_data_count = count($field_data);

    // If the cardinality is 0, throw an exception.
    if (!$cardinality) {
      throw new \InvalidArgumentException("'{$field->getName()}' cannot hold any values.");
    }

    // If the number of field content is greater than allowed, throw exception.
    if ($cardinality > 0 && $field_data_count > $cardinality) {
      throw new \InvalidArgumentException("'{$field->getname()}' cannot hold more than $cardinality values. $field_data_count values were parsed from the YAML file.");
    }

    // If we're updating content in-place, empty the field before population.
    if ($this->existenceCheck() && !$field->isEmpty()) {
      // Trigger delete callbacks on each field item.
      $field->delete();

      // Special handling for non-reusable entity reference values.
      if ($field instanceof EntityReferenceFieldItemList) {
        // Test if this is a paragraph field.
        $target_type = $field->getFieldDefinition()->getSetting('target_type');
        if ($target_type == 'paragraph') {
          $entities = $field->referencedEntities();
          foreach ($entities as $entity) {
            $entity->delete();
          }
        }
      }

      // Empty out the field's list of items.
      $field->setValue([]);
    }

    // Iterate over each field data value and process it.
    foreach ($field_data as &$item_data) {
      // Preprocess the field data.
      $this->preprocessFieldData($field, $item_data);

      // Check if the field is a reference field. If so, build the entity ref.
      $is_reference = isset($item_data['entity']);
      if ($is_reference) {
        // Build the reference entity.
        $field_item = $this->buildEntity($item_data['entity'], $item_data);
      }
      else {
        $field_item = $item_data;
      }

      // If the cardinality is set to 1, set the field value directly.
      if ($cardinality == 1) {
        $field->setValue($field_item);

        // @todo Warn if additional item data is available for population.
        break;
      }
      else {
        // Otherwise, append the item to the multi-value field.
        $field->appendItem($field_item);
      }
    }
  }

  /**
   * Run any designated preprocessors on the provided field data.
   *
   * Preprocessors are expected to be provided in the following format:
   *
   * ```yaml
   *   '#process':
   *     callback: '<callback string>'
   *     args:
   *       - <callback argument 1>
   *       - <callback argument 2>
   *       - <...>
   * ```
   *
   * The callback function receives the following arguments:
   *
   *   - `$field`
   *   - `$field_data`
   *   - <callback argument 1>
   *   - <callback argument 2>
   *   - <...>
   *
   * The `$field_data` array is passed by reference and may be modified directly
   * by the callback implementation.
   *
   * @param object $field
   *   The entity field object.
   * @param array $field_data
   *   The field data.
   */
  protected function preprocessFieldData($field, array &$field_data) {
    // Check for a callback processor defined at the value level.
    if (isset($field_data['#process']) && isset($field_data['#process']['callback'])) {
      $callback_type = $field_data['#process']['callback'];
      $dependency = $field_data['#process']['dependency'];
      $process_method = $callback_type . 'EntityLoad';
      if ($dependency) {
        $process_dependency = new ContentLoader($this->entityTypeManager);
        $process_dependency->setContentPath($this->path);
        $process_dependency->loadContent($dependency, $this->existenceCheck());
      }
      // Check to see if this class has a method in the format of
      // '{fieldType}EntityLoad'.
      if (method_exists($this, $process_method)) {
        // Append callback arguments to field object and value data.
        $args = array_merge([$field, &$field_data], isset($field_data['#process']['args']) ? $field_data['#process']['args'] : []);
        // Pass in the arguments and call the method.
        call_user_func_array([$this, $process_method], $args);
      }
      // If the method does not exist, throw an exception.
      else {
        throw new ConfigValueException('Unknown type specified: ' . $callback_type);
      }
    }
  }

  /**
   * Processor function for querying and loading a referenced entity.
   *
   * @param object $field
   *   The entity field object.
   * @param array $field_data
   *   The field data.
   * @param string $entity_type
   *   The entity type.
   * @param array $filter_params
   *   The filters for the query conditions.
   *
   * @return array|int
   *   The entity id.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   Error for missing data.
   *
   * @see ContentLoader::preprocessFieldData()
   */
  protected function referenceEntityLoad($field, array &$field_data, $entity_type, array $filter_params) {
    // Use query factory to create a query object for the node of entity_type.
    $query = \Drupal::entityQuery($entity_type);

    // Apply filter parameters.
    foreach ($filter_params as $property => $value) {
      $query->condition($property, $value);
    }

    $entity_ids = $query->execute();

    if (empty($entity_ids)) {
      if ($entity_type == 'taxonomy_term') {
        $term = Term::create($filter_params);
        $term->save();
        $entity_ids = [$term->id()];
      }
      elseif ($entity_type == 'node') {
        $node = Node::create($filter_params);
        $node->save();
        $entity_ids = [$node->id()];
      }
    }

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
      $param_output = implode("\n", $error_params);

      throw new MissingDataException(__CLASS__ . ': Unable to find referenced content: ' . $param_output);
    }

    // Use the first match for our value.
    $field_data['target_id'] = array_shift($entity_ids);

    // Remove process data to avoid issues when setting the value.
    unset($field_data['#process']);

    return $entity_ids;
  }

  /**
   * Processor function for processing and loading a file attachment.
   *
   * @param object $field
   *   The entity field object.
   * @param array $field_data
   *   The field data.
   * @param string $entity_type
   *   The entity type.
   * @param array $filter_params
   *   The filters for the query conditions.
   *
   * @return array|int
   *   The entity id.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   Error for missing data.
   *
   * @see ContentLoader::preprocessFieldData()
   */
  protected function fileEntityLoad($field, array &$field_data, $entity_type, array $filter_params) {
    if ($filter_params['type'] == 'module') {
      $filename = $filter_params['filename'];
      $directory = '/data_files/';
      // If the entity type is an image, look in to the /images directory.
      if ($entity_type == 'image') {
        $directory = '/images/';
      }
      $output = file_get_contents($this->path . $directory . $filename);
      if ($output !== FALSE) {
        // Save the file data. Do not overwrite files if they already exist.
        $file = file_save_data($output, 'public://' . $filename, FILE_EXISTS_RENAME);
        // Use the newly created file id as the value.
        $field_data['target_id'] = $file->id();

        // Remove process data to avoid issues when setting the value.
        unset($field_data['#process']);

        return $file->id();
      }
      else {
        // Build parameter output description for error message.
        $error_params = [
          '[',
          '  "entity_type" => ' . $entity_type . ',',
        ];
        foreach ($filter_params as $key => $value) {
          $error_params[] = sprintf("  '%s' => '%s',", $key, $value);
        }
        $error_params[] = ']';
        $param_output = implode("\n", $error_params);

        throw new MissingDataException(__CLASS__ . ': Unable to process file content: ' . $param_output);
      }
    }
  }

  /**
   * Query if a target entity already exists and should be updated.
   *
   * @param string $entity_type
   *   The type of entity being imported.
   * @param array $content_data
   *   The import content structure representing the entity being searched for.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false
   *   Return a matching entity if one is found, or FALSE otherwise.
   */
  protected function entityExists($entity_type, array $content_data) {
    // Load entity type handler.
    $entity_handler = $this->entityTypeManager->getStorage($entity_type);

    // Some entities require special handling to determine if it exists.
    switch ($entity_type) {
      // Always create new paragraphs since they're not reusable.
      case 'paragraph':
        break;

      case 'media':
        // @todo Add special handling to check file name or path.
        break;

      default:
        $query = \Drupal::entityQuery($entity_type);
        foreach ($content_data as $key => $value) {
          if ($key != 'entity' && !is_array($value)) {
            $query->condition($key, $value);
          }
        }
        $entity_ids = $query->execute();

        if ($entity_ids) {
          $entity_id = array_shift($entity_ids);
          $entity = $entity_handler->load($entity_id);
        }
    }

    return isset($entity) ? $entity : FALSE;
  }

}

