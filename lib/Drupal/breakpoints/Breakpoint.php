<?php

/**
 * @file
 * Definition of Drupal\breakpoint\Breakpoint.
 */

namespace Drupal\breakpoints;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Exception;

/**
 * Defines the Breakpoint entity.
 */
class Breakpoint extends ConfigEntityBase {

  /**
   * The breakpoint ID (config name).
   *
   * @var string
   */
  public $id;

  /**
   * The breakpoint UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The breakpoint name (machine name).
   *
   * @var string
   */
  public $name;
  
 /**
   * The breakpoint label.
   *
   * @var string
   */
  public $label;

  /**
   * The breakpoint media query.
   *
   * @var string
   */
  public $media_query = '';

  /**
   * The breakpoint source.
   *
   * @var string
   */
  public $source = 'user';

  /**
   * The breakpoint source type.
   *
   * @var string
   */
  public $source_type = BREAKPOINTS_SOURCE_TYPE_CUSTOM;

  /**
   * The breakpoint status.
   *
   * @var string
   */
  public $status = TRUE;

  /**
   * The breakpoint weight.
   *
   * @var weight
   */
  public $weight = 0;

  /**
   * The breakpoint multipliers.
   *
   * @var multipliers
   */
  public $multipliers = array();

  /**
   * The possible values for source type.
   *
   */
  const BREAKPOINTS_SOURCE_TYPE_THEME = 'theme';
  const BREAKPOINTS_SOURCE_TYPE_MODULE = 'module';
  const BREAKPOINTS_SOURCE_TYPE_CUSTOM = 'custom';
  /**
   * Overrides Drupal\config\ConfigEntityBase::__construct()
   */
  public function __construct(array $values = array(), $entity_type = 'breakpoints_breakpoint') {
    parent::__construct($values, $entity_type);
  }
  public function save() {
    if (empty($this->id)) {
      $this->id = $this->build_config_name();
    }
    if (empty($this->label)) {
      $this->label = ucfirst($this->name);
    }
    return parent::save();
  }

  /**
   * Get config name.
   */
  public function get_config_name() {
    return $this->source_type
      . '.' . $this->source
      . '.' . $this->name;
  }

  /**
   * Construct config name.
   */
  private function build_config_name() {
    // Check for illegal values in breakpoint source type.
    if (!in_array($this->source_type, array(BREAKPOINTS_SOURCE_TYPE_CUSTOM, BREAKPOINTS_SOURCE_TYPE_MODULE, BREAKPOINTS_SOURCE_TYPE_THEME))) {
      throw new Exception(
          t(
            'Expected one of \'@custom\', \'@module\' or \'@theme\' for breakpoint source_type property but got \'@sourcetype\'.',
            array(
              '@custom' => \BREAKPOINTS_SOURCE_TYPE_CUSTOM,
              '@module' => \BREAKPOINTS_SOURCE_TYPE_MODULE,
              '@theme' => \BREAKPOINTS_SOURCE_TYPE_THEME,
              '@sourcetype' => $this->source_type,
            )
          )
      );
    }
    // Check for illegal characters in breakpoint source.
    if (preg_match('/[^a-z_]+/', $this->source)) {
      throw new Exception(t('Invalid value \'@source\' for breakpoint source property. Breakpoint source property can only contain lowercase letters and underscores.', array('@source' => $this->source)));
    }
    // Check for illegal characters in breakpoint names.
    if (preg_match('/[^0-9a-z_\-]/', $this->name)) {
      throw new Exception(t('Invalid value \'@name\' for breakpoint name property. Breakpoint name property can only contain lowercase alphanumeric characters, underscores (_), and hyphens (-).', array('@name' => $this->name)));
    }
    return $this->source_type
      . '.' . $this->source
      . '.' . $this->name;
  }

  /**
   * Shortcut function to enable a breakpoint and save it.
   * @see breakpoints_breakpoint_action_confirm_submit().
   */
  public function enable() {
    if (!$this->status) {
      $this->status = 1;
      $this->save();
    }
  }

  /**
   * Shortcut function to disable a breakpoint and save it.
   * @see breakpoints_breakpoint_action_confirm_submit().
   */
  public function disable() {
    if ($this->status) {
      $this->status = 0;
      $this->save();
    }
  }
}
