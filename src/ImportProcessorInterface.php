<?php

namespace Drupal\yaml_content;

interface ImportProcessorInterface extends ContentProcessorInterface {

  /**
   * Pre-process import data and manipulate it prior to content creation.
   *
   * @param array $import_data
   *   The data array being processed for content import.
   */
  public function preprocess(array &$import_data);

  /**
   * Post-process imported content after it has been instantiated.
   *
   * @param array $import_data
   *   The data array that was processed to create the imported content item.
   * @param $imported_content
   *   The instantiated content element from the original content data array.
   */
  public function postprocess(array &$import_data, &$imported_content);
}