<?php

namespace Drupal\yaml_content;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Class ImportProcessorPluginManager
 * @package Drupal\yaml_content\Plugin
 *
 * @todo Documentation.
 */
class ImportProcessorPluginManager extends DefaultPluginManager {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $subdir = 'Plugin/YamlContent';

    $plugin_interface = 'Drupal\yaml_content\ImportProcessorInterface';

    $plugin_definition_annotation_name = 'Drupal\yaml_content\Annotation\ImportProcessor';

    parent::__construct($subdir, $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);

    $this->alterInfo('yaml_content_import_processor_info');

    $this->setCacheBackend($cache_backend, 'yaml_content_import_processor_info');
  }
}