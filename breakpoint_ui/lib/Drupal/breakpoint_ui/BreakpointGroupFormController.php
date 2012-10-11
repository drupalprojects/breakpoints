<?php

/**
 * @file
 * Definition of Drupal\breakpoint_ui\BreakpointFormController.
 */

namespace Drupal\breakpoint_ui;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFormController;
use Drupal\breakpoint\Breakpoint;
use Drupal\breakpoint\InvalidBreakpointMediaQueryException;

/**
 * Form controller for the breakpoint group edit/add forms.
 */
class BreakpointGroupFormController extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state, EntityInterface $breakpoint_group) {
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#maxlength' => 255,
      '#default_value' => $breakpoint_group->label(),
      '#description' => t("Example: 'Content' or 'Sidebar'."),
      '#required' => TRUE,
    );
    $form['name'] = array(
      '#type' => 'machine_name',
      '#default_value' => $breakpoint_group->name,
      '#machine_name' => array(
        'exists' => 'breakpoint_group_load',
        'source' => array('label'),
      ),
      '#disabled' => (bool)$breakpoint_group->id(),
    );

    $form['#tree'] = TRUE;

    // Load all available multipliers.
    $multipliers = drupal_map_assoc(config('breakpoint')->get('multipliers'));
    if (array_key_exists('1x', $multipliers)) {
      unset($multipliers['1x']);
    }

    // Weight for the order of the breakpoints.
    $weight = 0;

    $form['breakpoint_fieldset'] = array(
      '#type' => 'fieldset',
      '#title' => t('Breakpoints'),
      '#collapsible' => TRUE,
      '#attributes' => array(
        'id' => 'breakpoint_group-fieldset',
      ),
    );

    // Build table of breakpoints.
    $form['breakpoint_fieldset']['breakpoints'] = array(
      '#theme' => 'table',
      '#attributes' => array(
        'id' => 'breakpoint_group-breakpoints-table',
      ),
      '#empty' => t('No breakpoints added.'),
      '#pre_render' => array(
        'breakpoint_ui_add_breakpoint_table_prerender'
      ),
    );

    foreach ($breakpoint_group->breakpoints as $key => $breakpoint) {
      $form['breakpoint_fieldset']['breakpoints']['#rows'][$key] = array(
        'class' => array('draggable'),
        'data' => array(
          'label' => '',
          'mediaQuery' => '',
          'source' => $breakpoint->source . ' - ' . $breakpoint->sourceType,
          'multipliers' => '',
          'weight' => '',
        ),
      );
      $form['breakpoint_fieldset']['breakpoints'][$key]['label'] = array(
        '#type' => 'textfield',
        '#default_value' => $breakpoint->label(),
        '#parents' => array('breakpoints', $key, 'label'),
        '#maxlength' => 255,
        '#size' => 20,
        '#required' => TRUE,
      );
      $form['breakpoint_fieldset']['breakpoints'][$key]['mediaQuery'] = array(
        '#type' => 'textfield',
        '#default_value' => $breakpoint->mediaQuery,
        '#maxlength' => 255,
        '#parents' => array('breakpoints', $key, 'mediaQuery'),
        '#required' => TRUE,
        '#size' => 60,
        '#disabled' => !$breakpoint->isEditable(),
      );
      $form['breakpoint_fieldset']['breakpoints'][$key]['multipliers'] = array(
        '#type' => 'checkboxes',
        '#default_value' => (isset($breakpoint->multipliers) && is_array($breakpoint->multipliers)) ? $breakpoint->multipliers : array(),
        '#options' => $multipliers,
        '#parents' => array('breakpoints', $key, 'multipliers'),
      );
      if ($breakpoint_group->isEditable()) {
        $form['breakpoint_fieldset']['breakpoints'][$key]['remove'] = array(
          '#type' => 'submit',
          '#value' => t('Remove'),
          '#name' => 'breakpoint_remove_' . $weight,
          '#access' => $breakpoint->sourceType === Breakpoint::SOURCE_TYPE_CUSTOM,
          '#submit' => array(
            array($this, 'removeBreakpointSubmit'),
          ),
          '#breakpoint' => $key,
          '#ajax' => array(
            'callback' => 'ajax_add_breakpoint_submit',
            'wrapper' => 'breakpoint_group-fieldset',
          ),
        );
      }
      $form['breakpoint_fieldset']['breakpoints'][$key]['weight'] = array(
        '#type' => 'select',
        '#title' => t('Weight'),
        '#description' => t('Select the weight of this breakpoint in this set.'),
        '#options' => range(0, count($breakpoint_group->breakpoints)),
        '#attributes' => array('class' => array('weight')),
        '#parents' => array('breakpoints', $key, 'weight'),
        '#default_value' => $weight++,
      );
    }
    $form['breakpoint_fieldset']['breakpoints']['#header'] = array(
      'label' => t('Label'),
      'mediaQuery' => t('Media query'),
      'source' => t('Source'),
      'multipliers' => t('Multipliers'),
      'weight' => t('Weight'),
      'remove' => t('Remove'),
    );

    // Hide remove column for read only groups.
    if (!$breakpoint_group->isEditable()) {
      unset($form['breakpoint_fieldset']['breakpoints']['#header']['remove']);
    }

    // Show add another breakpoint if the group isn't read only.
    if ($breakpoint_group->isEditable()) {
      $options = array_diff_key(breakpoint_labels(), $breakpoint_group->breakpoints);

      if (!empty($options)) {
        $form['breakpoint_fieldset']['add_breakpoint_action'] = array(
          '#type' => 'actions',
          '#suffix' => '</div>',
        );
        $form['breakpoint_fieldset']['add_breakpoint_action']['breakpoint'] = array(
          '#type' => 'select',
          '#title' => t('Add existing breakpoint'),
          '#description' => t('Add an existing breakpoint to this set'),
          '#options' => $options,
          '#parents' => array('breakpoint'),
        );
        $form['breakpoint_fieldset']['add_breakpoint_action']['add_breakpoint'] = array(
          '#type' => 'submit',
          '#value' => t('Add breakpoint'),
          '#submit' => array(
            array($this, 'addBreakpointSubmit'),
          ),
          '#ajax' => array(
            'callback' => 'ajax_add_breakpoint_submit',
            'wrapper' => 'breakpoint_group-fieldset',
          ),
        );
      }
    }

    return parent::form($form, $form_state, $breakpoint_group);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   */
  protected function actions(array $form, array &$form_state) {
    // Only includes a Save action for the entity, no direct Delete button.
    return array(
      'submit' => array(
        '#value' => t('Save'),
        '#validate' => array(
          array($this, 'validate'),
        ),
        '#submit' => array(
          array($this, 'submit'),
          array($this, 'save'),
        ),
      ),
    );
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::validate().
   */
  public function validate(array $form, array &$form_state) {
    $breakpoint_group = $this->getEntity($form_state);
    // Check if the media queries are valid.
    if (isset($form_state['values']['breakpoints'])) {
      $breakpoints = $form_state['values']['breakpoints'];
      foreach ($breakpoints as $breakpoint_id => $breakpoint) {
        // Check if the user can edit the media query.
        if ($breakpoint_group->breakpoints[$breakpoint_id]->sourceType == Breakpoint::SOURCE_TYPE_CUSTOM) {
          try {
            Breakpoint::isValidMediaQuery($breakpoints[$breakpoint_id]['mediaQuery']);
          }
          catch(InvalidBreakpointMediaQueryException $e) {
            form_set_error('breakpoints][' . $breakpoint_id . '][mediaQuery', t($e->getMessage()));
          }
        }
      }
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $breakpoint_group = $this->getEntity($form_state);
    $breakpoints = $form_state['values']['breakpoints'];
    $this->_sort_breakpoints($breakpoint_group, $breakpoints);
    foreach ($breakpoint_group->breakpoints as $breakpoint_id => $breakpoint) {
      // Config will recognize this is as an existing breakpoint by its id.
      $breakpointobject = entity_load('breakpoint', $breakpoint_id);
      foreach ($breakpoint as $property => $value) {
        $breakpointobject->{$property} = $value;
      }
      $breakpointobject->save();
    }
    $breakpoint_group->save();

    watchdog('breakpoint', 'Breakpoint group @label saved.', array('@label' => $breakpoint_group->label()), WATCHDOG_NOTICE);
    drupal_set_message(t('Breakpoint group %label saved.', array('%label' => $breakpoint_group->label())));

    $form_state['redirect'] = 'admin/config/media/breakpoint/breakpoint_group';
  }

  /**
   * Submit callback to add a new breakpoint to a breakpoint group.
   * @see BreakpointGroupFormController::form()
   */
  public function addBreakpointSubmit(array $form, array $form_state) {
    $entity = $this->getEntity($form_state);
    // Get the order from the current form_state.
    if (isset($form_state['values']['breakpoints'])) {
      $breakpoints = $form_state['values']['breakpoints'];
      $this->_sort_breakpoints($entity, $breakpoints);
    }
    // Add the new breakpoint at the end.
    $breakpoint = $form_state['values']['breakpoint'];
    $entity->breakpoints += array($breakpoint => entity_load('breakpoint', $breakpoint));
    $form_state['rebuild'] = TRUE;
  }

  /**
   * Submit callback to add a new breakpoint to a breakpoint group.
   * @see BreakpointGroupFormController::form()
   */
  public function removeBreakpointSubmit(array $form, array $form_state) {
    $entity = $this->getEntity($form_state);
    // Get the order from the current form_state.
    $breakpoints = $form_state['values']['breakpoints'];
    $this->_sort_breakpoints($entity, $breakpoints);
    unset($entity->breakpoints[$form_state['triggering_element']['#breakpoint']]);
    $form_state['rebuild'] = TRUE;
  }

  private function _sort_breakpoints(&$entity, $breakpoints) {
    // Sort the breakpoints in the right order.
    uasort($breakpoints, 'drupal_sort_weight');
    $breakpoint_order = array_keys($breakpoints);
    $entity_breakpoints = $entity->breakpoints;
    $entity->breakpoints = array();
    foreach ($breakpoint_order as $breakpoint_id) {
      $entity->breakpoints[$breakpoint_id] = $entity_breakpoints[$breakpoint_id];
    }
    // make sure we don't lose any data
    $entity->breakpoints += $entity_breakpoints;
  }

}

