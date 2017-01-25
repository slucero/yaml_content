<?php

namespace Drupal\yaml_content\Plugin\YamlContent;

use Drupal\yaml_content\ImportProcessorBase;
use Drupal\yaml_content\SampleDataLoader;

/**
 * Import processor to support entity queries and references.
 *
 * @ImportProcessor(
 *   id = "sample_data",
 *   label = @Translation("Sample Data Processor"),
 * )
 */
class SampleDataProcessor extends ImportProcessorBase {

  /**
   * @var SampleDataLoader
   */
  protected $data_loader;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->data_loader = \Drupal::service('yaml_content.sample_data_loader');
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$import_data) {
    $context = $this->getContextValues();

    if (isset($context['dataset'])) {
      $data = $this->loadSampleData($context['dataset']);

      $value = $data->get($context['lookup']);
    }
    elseif (isset($context['data_type'])) {
      $params = isset($context['params']) ? $context['params'] : [];
      $value = $this->data_loader->loadSample($context['data_type'], $params);
    }

    $import_data[] = $value;
  }

  protected function loadSampleData(array $config) {
    $path = drupal_get_path('module', $config['module']);
    $path .= '/' . $config['path'];
    $path .= '/' . $config['file'] . '.data.yml';

    $data = $this->data_loader->loadDataSet($path);

    return $data;
  }
}
