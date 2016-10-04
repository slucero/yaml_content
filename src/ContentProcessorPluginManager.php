<?php

namespace Drupal\yaml_content;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class ContentProcessorPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $subdir = 'Plugin/YamlContent';

    $plugin_interface = 'Drupal\yaml_content\ContentProcessorInterface';

    $plugin_definition_annotation_name = 'Drupal\yaml_content\Annotation\ContentProcessor';

    parent::__construct($subdir, $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);

    $this->alterInfo('yaml_content_processor_info');

    $this->setCacheBackend($cache_backend, 'yaml_content_processor_info');
  }

  /**
   * Retrieve a list of content processor plugins supporting import operations.
   *
   * @return array
   *   An array of plugin definitions keyed by plugin id where the annotation
   *   indicates `import` as TRUE.
   *
   * @todo Incorporate caching into this lookup.
   */
  public function getImportPlugins() {
    $all_plugins = $this->getDefinitions();

    $import_plugins = array();
    foreach ($all_plugins as $plugin) {
      if ($plugin['import']) {
        $import_plugins[] = $plugin;
      }
    }

    return $import_plugins;
  }

  /**
   * Retrieve a list of content processor plugins supporting export operations.
   *
   * @return array
   *   An array of plugin definitions keyed by plugin id where the annotation
   *   indicates `export` as TRUE.
   *
   * @todo Incorporate caching into this lookup.
   */
  public function getExportPlugins() {
    $all_plugins = $this->getDefinitions();

    $export_plugins = array();
    foreach ($all_plugins as $plugin) {
      if ($plugin['export']) {
        $export_plugins[] = $plugin;
      }
    }

    return $export_plugins;
  }
}