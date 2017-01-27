<?php

namespace Drupal\sample_data;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class DataGeneratorPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $subdir = 'Plugin/SampleData';

    $plugin_interface = 'Drupal\sample_data\SampleDataGeneratorInterface';

    $plugin_definition_annotation_name = 'Drupal\sample_data\Annotation\SampleDataGenerator';

    parent::__construct($subdir, $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);

    $this->alterInfo('sample_data_generator_info');

    $this->setCacheBackend($cache_backend, 'sample_data_generator_info');
  }
}