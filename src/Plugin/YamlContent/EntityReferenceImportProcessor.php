<?php

namespace Drupal\yaml_content\Plugin\YamlContent;

use Drupal\yaml_content\ImportProcessorBase;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;

/**
 * Import processor to support entity queries and references.
 **
 * @ImportProcessor(
 *   id = "entity_reference_import_processor",
 *   label = @Translation("Entity Reference Import Processor"),
 *   config = {
 *     "entity_type" = NULL,
 *     "filters" = NULL,
 *     "limit" = 0,
 *   },
 * )
 */
class EntityReferenceImportProcessor extends ImportProcessorBase {

  /**
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $query;

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if (!$this->query) {
      $this->buildQuery();
    }

    $results = $this->query->execute();

    if (empty($results)) {
      return $this->noResults();
    }

    return $this->processResults($results);
  }

  /**
   * Build and prepare the the entity query with configured filters.
   *
   * All filters are applied using the addQueryFilter() method. After executing
   * this method, the entity query object itself is available from the $query
   * property.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   */
  protected function buildQuery() {
    $entity_type = $this->configuration['entity_type'];
    $this->query = \Drupal::entityQuery($entity_type);

    // Apply filters.
    foreach ($this->configuration['filters'] as $property => $value) {
      $this->addQueryFilter($entity_type, $property, $value);
    }

    // Handle any limits on the number of results allowed.
    if (isset($this->configuration['limit']) && $this->configuration['limit'] > 0) {
      $this->query->range(0, $this->configuration['limit']);
    }

    return $this->query;
  }

  /**
   * Apply property filter to the entity query.
   *
   * This method may be overridden to provide further processing or mapping of
   * property values as needed for filters.
   *
   * @param $entity_type
   * @param $property
   * @param $value
   */
  protected function addQueryFilter($entity_type, $property, $value) {
    $this->query->condition($property, $value);
  }

  /**
   * Handle behavior when no matches were found.
   *
   * @throws \Drupal\yaml_content\Plugin\YamlContent\MissingDataException
   */
  protected function noResults() {
    $entity_type = $this->configuration['entity_type'];
    $filters = $this->configuration['filters'];

    $error = 'Unable to find referenced content of type %type matching: @filters';

    throw new MissingDataException(__CLASS__ . ': ' . $this->t($error, array(
        '%type' => $entity_type,
        '@filters' => print_r($filters, TRUE),
      )));
  }

  /**
   * Process entity ID results into expected content structure.
   *
   * @param array $result_set
   * @return array
   *   The content array for use as part of the import process.
   */
  protected function processResults(array $result_set) {
    $field_data = array();

    foreach ($result_set as $entity_id) {
      $field_data[] = array(
        'target_id' => $entity_id,
      );
    }

    return $field_data;
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
