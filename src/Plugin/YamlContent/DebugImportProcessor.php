<?php

namespace Drupal\yaml_content\Plugin\YamlContent;

use Drupal\Core\Annotation\ContextDefinition;
use Drupal\yaml_content\ImportProcessorBase;

/**
 * Import processor to support entity queries and references.
 *
 * @ImportProcessor(
 *   id = "debug_import_processor",
 *   label = @Translation("Debug Import Processor"),
 * )
 */
class DebugImportProcessor extends ImportProcessorBase {
  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$import_data) {
    dpm($import_data, 'Debug Import Processor: Pre-process');
  }

  /**
   * {@inheritdoc}
   */
  public function postprocess(array &$import_data, &$imported_content) {
    dpm(array(
      'Import data' => $import_data,
      'Imported content' => $imported_content,
    ), 'Debug Import Processor: Post-process');
  }
}
