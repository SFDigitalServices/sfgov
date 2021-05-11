<?php

namespace Drupal\sfgov_qless\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\sfgov_qless\Services\QLess;

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
   * QLess Service.
   *
   * @var Drupal\sfgov_qless\QLess
   */
  protected $qLess;

  /**
   * Class constructor.
   */
  public function __construct(ClientInterface $httpClient, ConfigFactory $configFactory, QLess $qLess) {
    $this->httpClient = $httpClient;
    $this->configFactory = $configFactory;
    $this->qLess = $qLess;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('config.factory'),
      $container->get('sfgov_qless')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sfgov_qless.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'qless_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['api_url'] = [
      '#type' => 'url',
      '#title' => $this->t('QLess Microservice URL'),
      '#description' => $this->t('e.g. https://qless-microservice.herokuapp.com/api/v1/queues'),
      '#default_value' => $this->qLess->settings('api_url'),
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
    $this->config('sfgov_qless.settings')
      ->set('api_url', trim($form_state->getValue('api_url')))
      ->save();
  }

}
