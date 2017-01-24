<?php

namespace Drupal\yaml_content\Plugin\YamlContent;

use Drupal\Core\Annotation\ContextDefinition;
use Drupal\yaml_content\ImportProcessorBase;

/**
 * Import processor to support entity queries and references.
 **
 * @ImportProcessor(
 *   id = "debug_import_processor",
 *   label = @Translation("Debug Import Processor"),
 *   context = {
 *     "import_data" = @ContextDefinition("string",
 *       label = @Translation("Import data"),
 *       description = @Translation("The content array being imported.")
 *     )
 *   }
 * )
 */
class DebugImportProcessor extends ImportProcessorBase {
  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$import_data) {
    $import_data = $this->getContextValue('import_data');
    dpm($import_data, 'Debug Import Processor');
  }

  /**
   * {@inheritdoc}
   */
  public function postprocess() {

  }
}
