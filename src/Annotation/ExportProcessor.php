<?php

namespace Drupal\yaml_content\Annotation;


use Drupal\Component\Annotation\Plugin;

/**
 * Defines a ContentProcessor annotation object for export operations.
 *
 * @Annotation
 */
class ExportProcessor extends ContentProcessor {

  /**
   * {@inheritdoc}
   */
  public $id;

  /**
   * {@inheritdoc}
   */
  public $title;

  /**
   * {@inheritdoc}
   */
  public $description;

  /**
   * Whether the processor supports export operations.
   *
   * @var bool
   */
  public $export = TRUE;
}
