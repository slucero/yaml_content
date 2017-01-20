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
 *     "import_data" = @ContextDefinition("any", label = @Translation("Import data"))
 *   }
 * )
 */
class DebugImportProcessor extends ImportProcessorBase {

  /**
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $query;

  /**
   * {@inheritdoc}
   */
  public function execute() {

  }

  public function preprocess() {
    $import_data = $this->getContextValue('import_data');
    dpm($import_data, 'Debug Import Processor');
  }

  public function postprocess() {

  }
}
