<?php

namespace Drupal\sfgov_services\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ServicesSearchFiltersForm.
 */
class ServicesSearchFiltersForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'services_search_filters_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['container'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Filters'),
    ];

    $form['container']['topics'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Topic'),
      '#options' => $this->getOptions('topic'),
      '#default_value' => \Drupal::request()->query->get('topics') ?: [],
    ];

    $form['container']['departments'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Department'),
      '#options' => $this->getOptions('department'),
      '#default_value' => \Drupal::request()->query->get('departments') ?: [],
    ];

    $form['container']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply'),
      // BUG: Drupal core: Cached forms can have duplicate HTML IDs, which
      // disrupts accessible form labels: https://www.drupal.org/node/1852090
      '#id' => 'services-search-filters-form--submit',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $filters = [];

    $inputs = $form_state->getUserInput();
    foreach ($inputs as $key => $value) {
      if (!in_array($key, $form_state->getCleanValueKeys())) {
        if (!empty($value)) {
          if (is_array($value)) {
            $options = [];
            foreach ($value as $k => $v) {
              if ($v) {
                $options[$k] = $v;
              }
            }
            // Check that there are actual values in the array. Submitting the
            // filter form without any selections should still work, e.g.
            // subcommittees[1126]&subcommittees[1119] = returns no results
            // whereas an empty array results all.
            $filters[$key] = array_filter($options) ? $options : [];
          }
          else {
            $filters[$key] = $value;
          }
        }
      }
    }

    $uri = \Drupal::request()->getPathInfo();
    $redirect = Url::fromUserInput($uri, ['query' => $filters]);
    $form_state->setRedirectUrl($redirect);
  }

  public function getOptions($type) {
    $options = [];

    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['type' => $type, 'status' => TRUE]);

    foreach ($nodes as $node) {
      $options[$node->id()] = $node->label();
    }

    return $options;
  }

  /**
   * Get subcommittees.
   */
  public function getSubcommittees() {
    $route = \Drupal::routeMatch();
    $public_body = \Drupal::entityTypeManager()->getStorage('node')->load($route->getParameter('arg_0'));
    $subcommittees = [$public_body->id() => $public_body->label()];

    foreach ($public_body->field_subcommittees->getValue() as $value) {
      $subcommittee = \Drupal::entityTypeManager()->getStorage('node')->load($value['target_id']);
      if (!empty($subcommittee)) {
          $subcommittees[$subcommittee->id()] = $subcommittee->label();
      }
    }

    return $subcommittees;
  }

}
