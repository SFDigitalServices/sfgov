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
    $form['container'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Filters'),
      '#attributes' => [
        'data-filter-toggle-container' => TRUE,
      ],
      '#attached' => [
        'library' => [
          'sfgovpl/meetings',
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
        '09' => $this->t('October'),
        '10' => $this->t('September'),
        '11' => $this->t('November'),
        '12' => $this->t('December'),
      ],
      '#default_value' => \Drupal::request()->query->get('month'),
    ];

    $form['container']['toggle']['items']['year'] = [
      '#type' => 'select',
      '#options' => $this->getYearOptions(),
      '#default_value' => \Drupal::request()->query->get('year'),
    ];

    if ($this->getSubcommittees()) {
      $query_subcommittees = \Drupal::request()->query->get('subcommittees');
      $form['container']['toggle']['items']['subcommittees'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Committees'),
        '#options' => $this->getSubcommittees(),
        '#description' => $this->t('Select one or more committees'),
        '#default_value' => $query_subcommittees ? $query_subcommittees : [0],
      ];
    }

    $form['container']['toggle']['items']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply'),
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

    $values = $database->select('node__field_start_date', 'd')
      ->fields('d', ['field_start_date_value'])
      ->condition('d.bundle', 'meeting')
      ->execute()
      ->fetchAll();

    foreach ($values as $value) {
      $year = substr($value->field_start_date_value, 0, 4);

      if (!in_array($year, $years)) {
        $years[$year] = $year;
      }
    }

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
      $subcommittees[$subcommittee->id()] = $subcommittee->label();
    }

    return $subcommittees;
  }

}
