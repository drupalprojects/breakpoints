<?php

/**
 * @file
 * Definition of Drupal\breakpoint\BreakpointSet.
 */

namespace Drupal\breakpoints;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the BreakpointSet entity.
 */
class BreakpointSet extends ConfigEntityBase {

  /**
   * The BreakpointSet ID (machine name).
   *
   * @var string
   */
  public $id;

  /**
   * The BreakpointSet UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The BreakpointSet label.
   *
   * @var string
   */
  public $label;

  /**
   * The BreakpointSet breakpoints.
   *
   * @var array
   */
  public $breakpoints = array();

  /**
   * The BreakpointSet source type.
   *
   * @var string
   */
  public $source_type = Breakpoint::BREAKPOINTS_SOURCE_TYPE_CUSTOM;

  /**
   * The BreakpointSet overridden status.
   *
   * @var string
   */
  public $overridden = FALSE;

  /**
   * Overrides Drupal\config\ConfigEntityBase::__construct()
   */
  public function __construct(array $values = array(), $entity_type = 'breakpoints_breakpointset') {
    parent::__construct($values, $entity_type);
    $this->_load_all_breakpoints();
  }

  /**
   * Overrides Drupal\Core\Entity::save().
   */
  public function save() {
    // Only save the keys, but return the full objects.
    $this->breakpoints = drupal_map_assoc(array_keys($this->breakpoints));
    parent::save();
    $this->_load_all_breakpoints();
  }

  /**
   * Override and save a breakpoint set.
   */
  public function override() {
    return entity_get_controller($this->entityType)->override($this);
  }

  /**
   * Revert a breakpoint set after it has been overridden.
   */
  public function revert() {
    return entity_get_controller($this->entityType)->revert($this);
  }

  /**
   * Implements EntityInterface::createDuplicate().
   */
  public function createDuplicate() {
    $duplicate = new BreakpointSet();
    $duplicate->id = '';
    $duplicate->label = t('Clone of') . ' ' . $this->label();
    $duplicate->breakpoints = $this->breakpoints;
    return $duplicate;
  }

  /**
   * Load all breakpoints, remove non-existing ones.
   */
  private function _load_all_breakpoints() {
    foreach(array_values($this->breakpoints) as $breakpoint_id) {
      $breakpoint = breakpoints_breakpoint_load($breakpoint_id);
      if ($breakpoint) {
        $this->breakpoints[$breakpoint_id] = $breakpoint;
      }
      else {
        unset($this->breakpoints[$breakpoint_id]);
      }
    }
  }
}
