<?php

namespace Drupal\sample_data\Plugin\SampleData;

use Drupal\Core\Annotation\Translation;
use Drupal\sample_data\Annotation\SampleDataGenerator;

/**
 * Sample data generator to provide basic placeholder images.
 *
 * @SampleDataGenerator(
 *   id = "file_generator",
 *   title = @Translation("Sample file generator"),
 *   description = @Translation("A basic generator for placeholder images.")
 * )
 */
class FileGenerator {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $sample = $this->getFile($this->configuration['path'], $this->configuration['src']);

    return $sample;
  }

  /**
   * Helper function to retrieve files.
   *
   * @param string $file_path
   *   File destination path.
   * @param string $file_src
   *   File source path.
   *
   * @return \Drupal\file\FileInterface|false
   *   A file entity, or FALSE on error.
   */
  public function getFile($file_path, $file_src) {
    $file = file_get_contents($file_src);
    $destination = \Drupal::service('file_system')->dirname("public://$file_path");
    file_prepare_directory($destination, FILE_CREATE_DIRECTORY);
    return file_save_data(
      $file,
      "public://$file_path", FILE_EXISTS_REPLACE
    );
  }
}