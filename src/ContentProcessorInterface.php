<?php

namespace Drupal\yaml_content;

use Drupal\Component\Plugin\ContextAwarePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Executable\ExecutableInterface;

interface ContentProcessorInterface extends PluginInspectionInterface, ContextAwarePluginInterface, ExecutableInterface {

}
