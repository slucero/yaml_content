<?php

namespace Drupal\sample_data;

/**
 * Provides methods for retrieving sample data to be used in demo content.
 */
class SampleDataSet {

  protected $data;

  /**
   * SampleData constructor.
   *
   * @param string $file_path
   *   The path to a yaml sample data file.
   * @param string $src_theme
   *   The theme to source samples from.
   */
  public function __construct(array $data) {
    $this->data = $data;
  }

  /**
   * Retrieves a sample data for the given type and advances the data counter.
   *
   * @param string $type
   *   The type of sample data to retrieve.
   *
   * @return string|array|false
   *   The sample data or FALSE if it does not exist.
   */
  public function get($type) {
    if (isset($this->data[$type])) {
      $sample = current($this->data[$type]);
      if (next($this->data[$type]) === FALSE) {
        reset($this->data[$type]);
      }
      return $sample;
    }
    return FALSE;
  }

  /**
   * Sets sample data for the given type.
   *
   * @param string $type
   *   The type of sample data to set.
   * @param string|array $value
   *   The sample data.
   */
  public function set($type, $value) {
    $this->data[$type] = (array) $value;
  }
}