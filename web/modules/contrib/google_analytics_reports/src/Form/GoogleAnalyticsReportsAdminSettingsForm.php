<?php

namespace Drupal\google_analytics_reports\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\google_analytics_reports\GoogleAnalyticsReports;
use Drupal\google_analytics_reports_api\Form\GoogleAnalyticsReportsApiAdminSettingsForm;
use Drupal\google_analytics_reports_api\GoogleAnalyticsReportsApiFeed;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements Google Analytics Reports API Admin Settings form override.
 */
class GoogleAnalyticsReportsAdminSettingsForm extends GoogleAnalyticsReportsApiAdminSettingsForm {

  /**
   * Date Formatter Interface.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public function __construct(DateFormatterInterface $date_formatter) {
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $account = google_analytics_reports_api_gafeed();
    if ($account instanceof GoogleAnalyticsReportsApiFeed && $account->isAuthenticated()) {
      $google_analytics_reports_settings = $this->config('google_analytics_reports.settings')->get();
      $last_time = '';
      if (!empty($google_analytics_reports_settings['metadata_last_time'])) {
        $last_time = $google_analytics_reports_settings['metadata_last_time'];
      }
      $collapsed = (!$last_time) ? TRUE : FALSE;
      $form['fields'] = [
        '#type' => 'details',
        '#title' => t('Import and update fields'),
        '#open' => $collapsed,
      ];
      if ($last_time) {
        $form['fields']['last_time'] = [
          '#type' => 'item',
          '#title' => t('Google Analytics fields for Views integration'),
          '#description' => t('Last import was @time.',
            [
              '@time' => $this->dateFormatter->format($last_time, 'custom', 'd F Y H:i'),
            ]),
        ];
        $form['fields']['update'] = [
          '#type' => 'submit',
          '#value' => t('Check updates'),
          '#submit' => [[GoogleAnalyticsReports::class, 'checkUpdates']],
        ];
      }
      $form['fields']['settings'] = [
        '#type' => 'submit',
        '#value' => t('Import fields'),
        '#submit' => [[GoogleAnalyticsReports::class, 'importFields']],
      ];
    }
    return $form;
  }

}
