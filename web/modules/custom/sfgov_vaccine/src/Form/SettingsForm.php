<?php

namespace Drupal\sfgov_vaccine\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\sfgov_vaccine\Services\VaxValues;
use Drupal\sfgov_vaccine\VaxValues\Alert;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * Settings for the vaccine sites page.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Guzzle\Client instance.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;


  /**
   * The configuration factory.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The configuration factory.
   *
   * @var Drupal\sfgov_vaccine\VaxValues
   */
  protected $vaxValues;

  /**
   * Class constructor.
   */
  public function __construct(ClientInterface $httpClient, ConfigFactory $configFactory, VaxValues $vaxValues) {
    $this->httpClient = $httpClient;
      $this->configFactory = $configFactory;
      $this->vaxValues = $vaxValues;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('config.factory'),
      $container->get('sfgov_vaccine.values')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sfgov_vaccine.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vaccine_settings_form';
  }

  /**
   * Get config.
   */
  private function settings($value) {
    return $this->configFactory->get('sfgov_vaccine.settings')->get($value);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['api_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Microservice URL'),
      '#description' => $this->t('e.g. https://vaccination-site-microservice.vercel.app/api/v1/appointments, https://vaccination-site-microservice-git-automate-site-data-sfds.vercel.app/api/v1/appointments'),
      '#default_value' => $this->vaxValues->settings('api_url'),
    ];

    $form['alert'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Vaccine Page Alert Message'),
      '#description' => $this->t('Enter a message for the yellow alert area at /vaccine-sites.'),
      '#default_value' => $this->vaxValues->getAlert(),
      '#format' => 'sf_restricted_html',
      '#allowed_formats' => ['sf_restricted_html'],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    $url = $values['api_url'];
    $format = UrlHelper::isValid($values['api_url']);

    if ($format === TRUE) {
      $request = $this->httpClient->get($url, ['http_errors' => FALSE]);
      $code = $request->getStatusCode();

      if ($code != 200) {
        $message = $this->t(
          "Failed response with code @code. Try entering the microservice url again.", [
            '@code' => $request->getStatusCode(),
          ]);
        $form_state->setErrorByName('api_uri', $message);
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('sfgov_vaccine.settings')
      ->set('api_url', trim($form_state->getValue('api_url')))
      ->save();

    $this->vaxValues->setAlert($form_state->getValue('alert'));
  }

}
