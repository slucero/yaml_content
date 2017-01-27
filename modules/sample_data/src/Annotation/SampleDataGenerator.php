<?php

namespace Drupal\sample_data\Annotation;


use Drupal\Component\Annotation\Plugin;

/**
 * Defines a ContentProcessor annotation object.
 *
 * @Annotation
 */
class SampleDataGenerator extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The title of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;
}
