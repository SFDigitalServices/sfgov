<?php

namespace Drupal\google_analytics_reports\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * Tests creating Google Analytics views with the wizard.
 *
 * @ViewsWizard(
 *   id = "google_analytics_wizard",
 *   base_table = "google_analytics",
 *   title = @Translation("Google Analytics")
 * )
 */
class GoogleAnalyticsWizard extends WizardPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getAvailableSorts() {
    return [
      'sessions:DESC' => $this->t('Sessions'),
      'users:DESC' => $this->t('Users'),
      'pageviews:DESC' => $this->t('Pageviews'),
      'date:DESC' => $this->t('Date'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultDisplayOptions() {
    $display_options = parent::defaultDisplayOptions();

    // Add permission-based access control.
    $display_options['access']['type'] = 'perm';
    $display_options['access']['options']['perm'] = 'access google analytics reports';

    // Remove the default fields, since we are customizing them here.
    unset($display_options['fields']);

    // Add the title field.
    /* Field: Page tracking: Page Title */
    $display_options['fields']['pageTitle'] = [
      'id' => 'pageTitle',
      'table' => 'google_analytics',
      'field' => 'pageTitle',
      'label' => '',
      'element_label_colon' => FALSE,
    ];

    // Remove the default filters, since we are customizing them here.
    unset($display_options['filters']);

    /* Filter criterion: Google Analytics: Start date of report */
    $display_options['filters']['start_date'] = [
      'id' => 'start_date',
      'table' => 'google_analytics',
      'field' => 'start_date',
      'value' => [
        'type' => 'offset',
        'value' => '-31 day',
      ],
      'group' => 1,
      'expose' => ['operator' => FALSE]
    ];
    /* Filter criterion: Google Analytics: End date of report */
    $display_options['filters']['end_date'] = [
      'id' => 'end_date',
      'table' => 'google_analytics',
      'field' => 'end_date',
      'value' => [
        'type' => 'offset',
        'value' => '-1 day',
      ],
      'group' => 1,
      'expose' => ['operator' => FALSE]
    ];

    return $display_options;
  }

}
