<?php

namespace Drupal\yaml_content\Plugin\YamlContent;

use Drupal\yaml_content\ImportProcessorBase;

/**
 * Import processor to support entity queries and references.
 *
 * @ContentProcessor(
 *   id = "entity_reference_import_processor",
 *   label = @Translation("Entity Reference Import Processor"),
 * )
 */
class EntityReferenceImportProcessor extends ImportProcessorBase {

}
