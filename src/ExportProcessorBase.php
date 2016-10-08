<?php

namespace Drupal\yaml_content;


use Drupal\Core\Plugin\PluginBase;

abstract class ExportProcessorBase extends ContentProcessorBase implements ExportProcessorInterface {

  /**
   * Indicate that this plugin supports export operations.
   */
  public $export = TRUE;

}
