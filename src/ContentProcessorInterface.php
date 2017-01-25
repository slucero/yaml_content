<?php

namespace Drupal\yaml_content;

use Drupal\Component\Plugin\ContextAwarePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Executable\ExecutableInterface;

/**
 * Interface ContentProcessorInterface
 *
 * A generic interface to be extended by all import and export processors.
 *
 * @package Drupal\yaml_content
 */
interface ContentProcessorInterface extends PluginInspectionInterface, ContextAwarePluginInterface {

}
