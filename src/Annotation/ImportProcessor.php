<?php

namespace Drupal\yaml_content\Annotation;


use Drupal\Component\Annotation\Plugin;

/**
 * Defines a ContentProcessor annotation object for import operations.
 *
 * @Annotation
 */
class ImportProcessor extends ContentProcessor {

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
   * Whether the processor supports import operations.
   *
   * @var bool
   */
  public $import = TRUE;
}
