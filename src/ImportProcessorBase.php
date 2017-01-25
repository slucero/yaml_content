<?php

namespace Drupal\yaml_content;

/**
 * Class ImportProcessorBase
 *
 * A base implementation of a content import processor. This class should be
 * extended by all import processors.
 *
 * @package Drupal\yaml_content
 */
abstract class ImportProcessorBase extends ContentProcessorBase implements ImportProcessorInterface {

  /**
   * Indicate that this plugin supports import operations.
   */
  public $import = TRUE;

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$import_data) { }

  /**
   * {@inheritdoc}
   */
  public function postprocess(array &$import_data, &$imported_content) { }
}
