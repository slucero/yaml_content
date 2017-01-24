<?php

namespace Drupal\yaml_content\Plugin\YamlContent;

use Drupal\Core\Annotation\ContextDefinition;
use Drupal\yaml_content\ImportProcessorBase;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;

/**
 * Import processor to support entity queries and references.
 **
 * @ImportProcessor(
 *   id = "entity_reference_import_processor",
 *   label = @Translation("Entity Reference Import Processor"),
 *   context = {
 *     "import_data" = @ContextDefinition("any", label = @Translation("Import data")),
 *     "entity_type" = @ContextDefinition(
 *       "any",
 *       label = @Translation("Entity type"),
 *       required = "TRUE"
 *     ),
 *     "bundle" = @ContextDefinition(
 *       "any",
 *       label = @Translation("Bundle"),
 *       required = "FALSE"
 *     ),
 *     "type" = @ContextDefinition(
 *       "any",
 *       label = @Translation("Content type"),
 *       required = "FALSE"
 *     ),
 *     "limit" = @ContextDefinition(
 *       "any",
 *       label = @Translation("Result limit"),
 *       required = "FALSE"
 *     ),
 *     "conditions" = @ContextDefinition(
 *       "any",
 *       label = @Translation("Query conditions"),
 *       required = "FALSE"
 *     ),
 *   }
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
  public function preprocess(array &$import_data) {
    if (!$this->query) {
      $this->buildQuery();
    }

    $results = $this->query->execute();

    if (empty($results)) {
      // @todo Handle empty result sets.
      //return $this->noResults();
    }

    $this->processResults($import_data, $results);
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
    $entity_type = $this->getContextValue('entity_type');
    $this->query = \Drupal::entityQuery($entity_type);

    $this->applyFilters();

    return $this->query;
  }

  /**
   *
   * @todo Add support for `range()` operator instead of just `limit`.
   * @todo Add support for `condition()` operators.
   */
  protected function applyFilters() {
    $context = $this->getContextValues();
    foreach ($context as $name => $value) {

      // Only apply filters for populated values.
      if (!is_null($value)) {
        // Handle special cases.
        switch ($name) {
          // Entity type should be already applied at query creation and require
          // nothing further.
          case 'entity_type':
            break;

          // Specially handle a `bundle` or `type` key for convenience.
          case 'bundle':
          case 'type':
            $this->query->condition('type', $value);
            break;

          case 'limit':
            // Assume starting at 0 to apply limit only.
            $this->query->range(0, $value);
            break;

          case 'conditions':
            foreach ($value as $field => $filter) {
              $op = is_array($filter) ? 'IN' : '=';
              $this->query->condition($field, $filter, $op);
            }
        }
      }
    }
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
   * @param array $import_data
   * @param array $result_set
   */
  protected function processResults(array &$import_data, array $result_set) {
    // Format and append entity reference value.
    foreach ($result_set as $entity_id) {
      $import_data[] = array(
        'target_id' => $entity_id,
      );
    }
  }
}
