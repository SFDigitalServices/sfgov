<?php

namespace Drupal\sfgov_public_bodies\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MeetingListFiltersForm.
 */
class MeetingListFiltersForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'meeting_list_filters_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'sfgov-filters-form';
    $form['container'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Filters'),
      '#attributes' => [
        'data-filter-toggle-container' => TRUE,
      ],
      '#attached' => [
        'library' => [
          'sfgovpl/filters',
        ],
      ],
    ];

    $form['container']['toggle'] = [
      '#type' => 'container',
      '#attributes' => [
        'data-filter-toggle-content' => TRUE,
      ],
    ];

    $form['container']['toggle']['items'] = [
      '#type' => 'container',
    ];

    $form['container']['toggle']['items']['month'] = [
      '#type' => 'select',
      '#title' => $this->t('Month'),
      '#title_display' => 'invisible',
      '#options' => [
        '' => $this->t('Select a month'),
        '01' => $this->t('January'),
        '02' => $this->t('February'),
        '03' => $this->t('March'),
        '04' => $this->t('April'),
        '05' => $this->t('May'),
        '06' => $this->t('June'),
        '07' => $this->t('July'),
        '08' => $this->t('August'),
        '09' => $this->t('September'),
        '10' => $this->t('October'),
        '11' => $this->t('November'),
        '12' => $this->t('December'),
      ],
      '#default_value' => \Drupal::request()->query->get('month'),
    ];

    $form['container']['toggle']['items']['year'] = [
      '#type' => 'select',
      '#title' => $this->t('Year'),
      '#title_display' => 'invisible',
      '#options' => $this->getYearOptions(),
      '#default_value' => \Drupal::request()->query->get('year'),
    ];

    if ($this->getSubcommittees()) {
      $query_subcommittees = \Drupal::request()->query->get('subcommittees');
      $form['container']['toggle']['items']['subcommittees'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Committees'),
        '#options' => $this->getSubcommittees(),
        '#default_value' => $query_subcommittees ? $query_subcommittees : [0],
        // BUG: Drupal core: Broken aria-describedby IDREF in radios and
        // checkboxes elements: https://www.drupal.org/node/2839344
        // Note: When this is fixed, we can delete '#suffix' and the
        // corresponding code in sfgov_public_bodies_preprocess_fieldset() and
        // just use '#description'.
        '#suffix' => '<div id="subcommittees-description" class="visually-hidden">'. t('Select one or more committees') . '</div>',
      ];
    }

    $form['container']['toggle']['items']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply'),
      // BUG: Drupal core: Cached forms can have duplicate HTML IDs, which
      // disrupts accessible form labels: https://www.drupal.org/node/1852090
      '#id' => 'meeting-list-filters-form--submit',
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
            // Check that there are actual values in the array. Submitting the
            // filter form without any selections should still work, e.g.
            // subcommittees[1126]&subcommittees[1119] = returns no results
            // whereas an empty array results all.
            $filters[$key] = array_filter($value) ? $value : [];
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

  /**
   * Get year options.
   */
  public function getYearOptions() {
    $years = ['' => $this->t('Select a year')];
    $database = \Drupal::database();
    $route = \Drupal::routeMatch();
    $public_body_id = $route->getParameter('arg_0');
    
    $values_query = $database->select('node__field_start_date', 'd')
      ->fields('d', ['field_start_date_value'])
      ->condition('d.bundle', 'meeting');
    $values_query->join('node__field_public_body', 'pb', 'pb.entity_id = d.entity_id AND pb.field_public_body_target_id = :pbid', array(':pbid' => $public_body_id));
    $values = $values_query->execute()
           ->fetchAll();
    
    foreach ($values as $value) {
      $year = substr($value->field_start_date_value, 0, 4);

      if (!in_array($year, $years)) {         
        $years[$year] = $year;
      }
    }
    arsort($years);

    return $years;
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
