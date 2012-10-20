<?php

/**
 * @file
 * Definition of Drupal\breakpoint_ui\BreakpointGroupListController.
 */

namespace Drupal\breakpoint_ui;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\breakpoint\Breakpoint;

/**
 * Provides a listing of breakpoint groups.
 */
class BreakpointGroupListController extends ConfigEntityListController {

  public function __construct($entity_type, $entity_info = FALSE) {
    parent::__construct($entity_type, $entity_info);
  }

  /**
   * Overrides Drupal\config\EntityListControllerBase::hookMenu();
   */
  public function hookMenu() {
    $path = $this->entityInfo['list path'];
    $items = parent::hookMenu();

    // Override the access callback.
    $items[$path]['title'] = 'Breakpoint groups';
    $items[$path]['description'] = 'Manage list of breakpoint groups.';
    $items[$path]['access callback'] = 'user_access';
    $items[$path]['access arguments'] = array('administer breakpoints');

    return $items;
  }

  /**
   * Overrides Drupal\config\ConfigEntityListController::getOperations();
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    // Custom breakpoint groups can be deleted.
    if ($entity->sourceType !== Breakpoint::SOURCE_TYPE_USER_DEFINED) {
      unset($operations['delete']);
    }
    return $operations;
  }

  /**
   * Implements Drupal\Core\Entity\EntityListControllerInterface::render().
   *
   * Builds the entity list as renderable array for theme_table().
   */
  public function render() {
    $build = parent::render();
    if (!isset($build['#attached'])) {
      $build['#attached'] = array();
    }
    return $build;
  }
}
