<?php

namespace Drupal\yaml_content\Annotation;


use Drupal\Component\Annotation\Plugin;

/**
 * Defines a ContentProcessor annotation object.
 *
 * @Annotation
 */
class ContentProcessor extends Plugin {

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

  /**
   * Whether the processor supports import operations.
   *
   * @var bool
   */
  public $import;

  /**
   * Whether the processor supports export operations.
   *
   * @var bool
   */
  public $export;
}
