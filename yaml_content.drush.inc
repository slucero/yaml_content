<?php
/**
 * @file
 * Drush commands for the yaml_content module.
 *
 * @todo Re-implement as universal command compatible with Drupal console.
 */

use \Drupal\yaml_content\ContentLoader\ContentLoader;

/**
 * Implements hook_drush_command().
 */
function yaml_content_drush_command() {
  $items = [];

  $items['yaml-content-import'] = [
    'description' => dt('Import yaml content.'),
    'aliases' => ['yci'],
    'arguments' => [],
    'options' => [],
  ];

  $items['yaml-content-import-dev'] = [
    'description' => dt('Dev debugging import.'),
    'aliases' => ['ycid'],
    'arguments' => [],
    'options' => [],
  ];

  return $items;
}

/**
 * Import specified yaml content file(s).
 * 
 * @param string $directory
 *   The directory path containing the yaml content file(s) to be imported.
 * @param string|string[] $file
 *   (Optional) The name of a file to be imported or an array of files to
 *   import. If this argument is not provided then all files in the directory
 *   matching `*.content.yml` are queued for import.
 * 
 * @todo Implement file globbing for optional `$file` parameter.
 */
function drush_yaml_content_import($directory, $file = NULL) {
  // @todo Use service loading after refactoring.
  // $loader = \Drupal::service('content_loader.yaml_content');
  $loader = new ContentLoader();
  $loader->setContentPath($directory);

  // @todo Implement file globbing if `$file` is NULL.
  $import_files = is_array($file) ? $file : [$file];

  // @todo Verify files before loading for import.
  foreach ($import_files as $file) {
    drush_print_r('Importing content: ' . $file);
    $loaded = $loader->loadContent($file);
    drush_print_r('Imported ' . count($loaded) . ' items.');
  }
}

/**
 * Import debugging content.
 */
function drush_yaml_content_import_dev() {
  $content_path = drupal_get_path('module', 'yaml_content') . '/yaml_content/content';
  drush_yaml_content_import($content_path, 'dev.content.yml');
}
