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

  $items['yaml-content-import-module'] = [
    'description' => dt('Import yaml content from a module.'),
    'aliases' => ['ycim'],
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
  $loader = \Drupal::service('yaml_content.content_loader');
  $loader->setContentPath($directory);

  // @todo Implement file globbing if `$file` is NULL.
  $import_files = is_array($file) ? $file : [$file];

  // @todo Verify files before loading for import.
  foreach ($import_files as $file) {
    drush_print_r('Importing content: ' . $file);
    $loader->parseContent($file);
    $loaded = $loader->loadContent();
    drush_print_r('Imported ' . count($loaded) . ' items.');
  }
}

/**
 * Import specified yaml content file(s) from a designated module.
 *
 * @param string $module
 *   The module to look for content files within.
 *
 *   This command assumes files will be contained within a `content/` directory
 *   at the top of the module's main directory. Any files within matching the
 *   pattern `*.content.yml` will then be imported.
 * @param string|string[] $file
 *   (Optional) The name of a file to be imported or an array of files to
 *   import. If this argument is not provided then all files in the directory
 *   matching `*.content.yml` are queued for import.
 */
function drush_yaml_content_import_module($module, $file = NULL) {
  $loader = \Drupal::service('yaml_content.content_loader');

  $path = drupal_get_path('module', $module);

  if (!$path) {
    // @todo Handle when the module cannot be found.
  }
  else {
    $path .= '/content';
  }

  $loader->setContentPath($path);

  // Identify files for import.
  if (is_null($file)) {
    $mask = '/.*\.content\.yml/';
  }
  else {
    // Scan only for the specific file name if it exists.
    $mask = '/' . $file . '\.content\.yml/';
  }
  $files = file_scan_directory($path, $mask, ['recurse' => FALSE]);


  // @todo Verify files before loading for import.
  foreach ($files as $filename => $file) {
    drush_print_r('Importing content: ' . $filename);
    $loader->parseContent($file->filename);
    $loaded = $loader->loadContent();
    drush_print_r('Imported ' . count($loaded) . ' items.');
  }
}

/**
 * Import debugging content.
 */
function drush_yaml_content_import_dev() {
  $content_path = drupal_get_path('module', 'yaml_content') . '/content';
  drush_yaml_content_import($content_path, 'dev.content.yml');
}
