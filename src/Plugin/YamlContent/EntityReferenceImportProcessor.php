<?php

namespace Drupal\yaml_content\Plugin\YamlContent;

use Drupal\yaml_content\ImportProcessorBase;

/**
 * Import processor to support entity queries and references.
 *
 * @ImportProcessor(
 *   id = "entity_reference_import_processor",
 *   label = @Translation("Entity Reference Import Processor"),
 *   context = {
 *     "entity_type" = NULL,
 *     "filters" = NULL,
 *   },
 * )
 */
class EntityReferenceImportProcessor extends ImportProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function execute() {

  }

  protected function buildQuery() {

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
