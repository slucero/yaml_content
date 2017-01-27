<?php

namespace Drupal\sample_data\Plugin\SampleData;

use Drupal\Core\Annotation\Translation;
use Drupal\sample_data\Annotation\SampleDataGenerator;
use Drupal\sample_data\SampleDataGeneratorBase;

/**
 * Sample data generator to provide basic placeholder images.
 *
 * @SampleDataGenerator(
 *   id = "image_generator",
 *   title = @Translation("Sample image generator"),
 *   description = @Translation("A basic generator for placeholder images.")
 * )
 */
class ImageGenerator extends SampleDataGeneratorBase {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $sample = $this->getImage($this->configuration['width'], $this->configuration['height']);

    return $sample;
  }

  /**
   * Helper function to fetch images.
   *
   * @param string $width
   *   The width of the image to fetch.
   * @param string $height
   *   The height of the image to fetch.
   * @param string $ext
   *   (Optional) The extension to use for fetching the image.
   *
   * @return \Drupal\file\FileInterface|false
   *   The file entity that was created.
   */
  public function getImage($width, $height, $ext = "png") {
    $image = file_get_contents("http://placehold.it/$width" . 'x' . $height);
    return file_save_data(
      $image,
      "public://$width" . 'x' . "$height.$ext", FILE_EXISTS_REPLACE);
  }
}