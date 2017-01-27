<?php

namespace Drupal\sample_data;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface SampleDataGeneratorInterface
 *
 * A generic interface to be extended by all sample data generators.
 *
 * @package Drupal\sample_data
 */
interface SampleDataGeneratorInterface extends PluginInspectionInterface {

  /**
   * A uniform execution function for all plugins to implement.
   *
   * @return mixed
   */
  public function execute();
}
