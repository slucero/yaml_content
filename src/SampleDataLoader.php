<?php

namespace Drupal\yaml_content;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides methods for retrieving sample data to be used in demo content.
 */
class SampleDataLoader {

  /**
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $decoder;

  protected $data;

  /**
   * SampleData constructor.
   *
   * @param SerializationInterface $decoder
   */
  public function __construct(SerializationInterface $decoder) {
    $this->decoder = $decoder;
  }

  /**
   * Load a data set from a given file.
   *
   * @param $file
   *   The fully qualified filename and path to be loaded.
   * @return \Drupal\yaml_content\SampleDataSet
   */
  public function loadDataSet($file) {
    if (!isset($this->data[$file])) {
      $data = $this->decoder->decode(file_get_contents($file));
      $this->data[$file] = new SampleDataSet($data);
    }

    return $this->data[$file];
  }

  /**
   * Load sample data based on type and additional parameters.
   *
   * @param string $data_type
   * @param array $params
   *
   * @return mixed|FALSE
   *   The loaded sample data item or FALSE if unable to load.
   */
  public function loadSample(string $data_type, array $params = []) {
    $sample = FALSE;

    switch ($data_type) {
      case 'term':
        $sample = $this->getTerm($params['name'], $params['vocabulary']);
        break;

      case 'short_text':
        $sample = $this->getLipsum(20);
        break;

      case 'rich_text':
        // @todo Support addition of markup.
        $sample = $this->getLipsum(200);
        break;

      case 'image':
        // @todo Handle missing width and height parameters.
        $sample = $this->getImage($params['width'], $params['height']);
        break;

      case 'file':
        $sample = $this->getFile($params['path'], $params['src']);
        break;

      default:
        // @todo Handle unsupported data type.
    }

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
  public static function getFile($file_path, $file_src) {
    $file = file_get_contents($file_src);
    $destination = \Drupal::service('file_system')->dirname("public://$file_path");
    file_prepare_directory($destination, FILE_CREATE_DIRECTORY);
    return file_save_data(
      $file,
      "public://$file_path", FILE_EXISTS_REPLACE
    );
  }

  /**
   * Helper function to fetch images.
   *
   * @param string $width
   *   The width of the image to fetch.
   * @param string $height
   *   The height of the image to fetch.
   * @param string $ext
   *   The extension to use for fetching the image.
   *
   * @return \Drupal\file\FileInterface|false
   *   The file entity that was created.
   */
  public static function getImage($width, $height, $ext = 'png') {
    $image = file_get_contents("http://placehold.it/$width" . 'x' . $height);
    return file_save_data(
      $image,
      "public://$width" . 'x' . "$height.$ext", FILE_EXISTS_REPLACE);
  }

  /**
   * Helper function for theme images.
   *
   * @param string $file_path
   *   The destination filename.
   * @param string $file_src
   *   The uri to the theme file in the format 'directory/image.jpg'.
   *
   * @return \Drupal\file\FileInterface|false
   *   A file entity, or FALSE on error.
   */
  public function getThemeFile($file_path, $file_src) {
    return static::getFile($file_path,
      drupal_get_path('theme', $this->srcTheme) . '/' . $file_src);
  }

  /**
   * Helper function to generate random Lorem Ipsum content.
   *
   * @param int $length
   *   The integer length of the content to generate.
   * @param bool $capitalize
   *   Whether to capitalize the return Lorem ipsum.
   *
   * @return string
   *   The string of content, $length characters long.
   */
  public static function getLipsum($length = 200, $capitalize = TRUE) {
    $lorem_ipsum = file_get_contents(__DIR__ . '/lipsum.txt');
    $lipsum_count = strlen($lorem_ipsum);
    $rand_start = max(0, random_int(0, $lipsum_count - $length));
    $start = $rand_start ? strpos($lorem_ipsum, ' ', $rand_start) + 1 : 0;
    $lipsum = preg_replace('/^[\W_]+|[\W_]+$/', '',
      substr($lorem_ipsum, $start, $length));
    $lipsum = $capitalize ? ucfirst($lipsum) : $lipsum;
    $missing_char = $length - strlen($lipsum);
    return $missing_char ? $lipsum . SampleDataLoader::getLipsum($missing_char, FALSE) : $lipsum;
  }

  /**
   * Helper function to get or create a term.
   *
   * @param string $term_name
   *   The name of the term.
   * @param string $vocabulary
   *   The term vocabulary.
   *
   * @return int
   *   The id of the term.
   */
  public static function getTerm($term_name, $vocabulary) {
    if ($terms = taxonomy_term_load_multiple_by_name($term_name, $vocabulary)) {
      $term = reset($terms);
    }
    else {
      $term = Term::create([
        'name' => $term_name,
        'vid' => $vocabulary,
      ]);
      $term->save();
    }

    return $term->id();
  }

  /**
   * Helper function to convert non-alphanumeric characters into dash.
   *
   * @param string $str
   *   The string to convert.
   * @param string $replace
   *   The string to replace non-alphanumeric characters.
   *
   * @return string
   *   The converted string.
   */
  public static function slugify($str, $replace = '-') {
    return preg_replace("/[^a-z0-9]/", $replace, strtolower($str));
  }

}