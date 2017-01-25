<?php

namespace Drupal\yaml_content\Plugin\YamlContent;

use Drupal\Core\Annotation\ContextDefinition;
use Drupal\Core\Annotation\Translation;
use Drupal\yaml_content\ImportProcessorBase;

/**
 * Class TemplateProcessor
 *
 * A content preprocessor to support more controlled generation of sample
 * content through the inclusion of reusable templates.
 *
 * @ImportProcessor(
 *   id = "template_processor",
 *   label = @Translation("Template processor"),
 *   context = {
 *     "template" = @ContextDefinition("string", label = @Translation("Content template")),
 *     "count" = @ContextDefinition("integer",
 *       label = @Translation("Count"),
 *       description = @Translation("The number of times to include the template.")
 *     ),
 *     "module" = @ContextDefinition("string",
 *       label = @Translation("Template module"),
 *       description = @Translation("The module containing the template file.")
 *     )
 *   }
 * )
 */
class TemplateProcessor extends ImportProcessorBase {

  /**
   * @var \Drupal\Component\Serialization\Yaml
   */
  protected $decoder;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->decoder = \Drupal::service('serialization.yaml');
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$import_data) {
    $template_data = $this->loadTemplate();

    $count = $this->getContextValue('count');

    // Populate the template the designated number of times.
    for ($i = 0; $i < $count; $i++) {
      // Add another iteration of the template.
      $import_data[] = $template_data;
    }
  }

  /**
   * Load the template designated through context values.
   *
   * @return mixed
   *   The loaded template structure.
   */
  protected function loadTemplate() {
    $context = $this->getContextValues();

    $template_path = drupal_get_path('module', $context['module']) . '/content';
    $template_name = $context['template'] . '.template.yml';

    // @todo Handle failure to load file.
    $template_data = $this->decoder
      ->decode(file_get_contents($template_path . '/' . $template_name));

    return $template_data;
  }
}
