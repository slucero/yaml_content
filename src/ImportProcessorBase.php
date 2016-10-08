<?php

namespace Drupal\yaml_content;

abstract class ImportProcessorBase extends ContentProcessorBase implements ImportProcessorInterface {

  /**
   * Indicate that this plugin supports import operations.
   */
  public $import = TRUE;

}
