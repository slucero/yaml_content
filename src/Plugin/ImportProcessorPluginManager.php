<?php

namespace Drupal\yaml_content\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Class ImportProcessorPluginManager
 * @package Drupal\yaml_content\Plugin
 *
 * @todo Documentation.
 */
class ImportProcessorPluginManager extends DefaultPluginManager {

  public function __construct($subdir, \Traversable $namespaces, \Drupal\Core\Extension\ModuleHandlerInterface $module_handler, $plugin_interface, $plugin_definition_annotation_name, $additional_annotation_namespaces) {
    parent::__construct($subdir, $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name, $additional_annotation_namespaces);
  }
}