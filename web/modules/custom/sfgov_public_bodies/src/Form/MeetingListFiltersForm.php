<?php

namespace Drupal\sfgov_public_bodies\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

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

    $form['container']['toggle']['items']['archive'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->getArchiveLink(),
    ];


    if ($this->getSubcommittees()) {
      $query_subcommittees = \Drupal::request()->query->all('subcommittees');
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
        '#suffix' => '<div id="subcommittees-description" class="visually-hidden">' . t('Select one or more committees') . '</div>',
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
          } else {
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
    
    $values_query = $database->select('node__field_smart_date', 'd')
      ->fields('d', ['field_smart_date_value'])
      ->condition('d.bundle', 'meeting');
    $values_query->join('node__field_public_body', 'pb', 'pb.entity_id = d.entity_id AND pb.field_public_body_target_id = :pbid', array(':pbid' => $public_body_id));
    $values = $values_query->execute()
      ->fetchAll();

    foreach ($values as $value) {
      $year = date('Y', $value->field_smart_date_value);

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
  public static function getSubcommittees($route = null) {
    $route = \Drupal::routeMatch();
    $agency = \Drupal::entityTypeManager()->getStorage('node')->load($route->getParameter('arg_0'));
    $subcommittees = [$agency->id() => $agency->label()];
    $divisionsAndSubcommittees = [];

    // for the moment we still have departments/agencies and public bodies
    // now that agencies can have meetings and divisions/subcommittees we will need to consider them in these meeting queries
    // TODO: when migration from public body to agency is complete, remove unnecessary collection of field values for query conditions

    // public body subcommittees
    if ($agency->hasField('field_subcommittees')) {
      $divisionsAndSubcommittees = array_merge($divisionsAndSubcommittees, $agency->field_subcommittees->getValue());
    }

    // agency divisions
    if ($agency->hasField('field_agency_sections')) {
      $agencySections = $agency->field_agency_sections->getValue();
      foreach ($agencySections as $agencySection) {
        $agencySection = Paragraph::load($agencySection['target_id']);
        $agencyContents = $agencySection->field_agencies->getValue();

        foreach($agencyContents as $ac) {
          $agencyContent = Paragraph::load($ac['target_id']);
          $divisionsAndSubcommittees[] = $agencyContent->field_department->getValue()[0];
        }        
      }
    }


    foreach ($divisionsAndSubcommittees as $value) {
      $subcommittee = \Drupal::entityTypeManager()->getStorage('node')->load($value['target_id']);
      if (!empty($subcommittee)) {
          $subcommittees[$subcommittee->id()] = $subcommittee->label();
      }
    }

    return $subcommittees;
  }

  /**
   * Get Archive url.
   */
  public function getArchiveLink() {
    $route = \Drupal::routeMatch();
    $agency = \Drupal::entityTypeManager()->getStorage('node')->load($route->getParameter('arg_0'));

    if ($agency instanceof Node) {
      $date = NULL;
      if ($agency->hasField('field_meeting_archive_date')) {
        foreach ($agency->field_meeting_archive_date->getValue() as $value) {
          $date_formatter = \Drupal::service('date.formatter');
          $date = $date_formatter->format(strtotime($value['value']), 'custom', 'M Y');
          $date = new FormattableMarkup("<span class='nobreak'>@date</span>", ['@date' => $date]);
        }
      }

      $text = $date ? $this->t('See archived meetings before @date', ['@date' => $date]) : $this->t('See past meetings');

      if ($agency->hasField('field_meeting_archive_url')) {
        foreach ($agency->field_meeting_archive_url->getValue() as $value) {
          $url = Url::fromUri($value['uri'], ['attributes' => ['class' => 'link__plain']]);
          return Link::fromTextAndUrl($text, $url)->toString();
        }
      }
    }

    return false;
  }
}
