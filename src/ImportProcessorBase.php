<?php

namespace Drupal\yaml_content\Plugin\YamlContent;


use Drupal\Core\Plugin\PluginBase;

abstract class ImportProcessorBase extends PluginBase implements ImportProcessorInterface {

  /**
   * Indicate that this plugin supports import operations.
   */
  public $import = TRUE;
}