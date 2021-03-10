<?php

namespace Drupal\sfgov_vaccine\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Guzzle\Client instance.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Class constructor.
   */
  public function __construct(ClientInterface $httpClient) {
    $this->httpClient = $httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
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
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sfgov_vaccine.settings');

    $form['api_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Microservice URL'),
      '#description' => $this->t('e.g. https://vaccination-site-microservice.vercel.app/api/v1/appointments, https://vaccination-site-microservice-git-automate-site-data-sfds.vercel.app/api/v1/appointments'),
      '#default_value' => $config->get('api_url')
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
      $request = $this->httpClient->get($url, ['http_errors' => false]);
      $code = $request->getStatusCode();

      if($code != 200) {
        $message = $this->t(
          "Failed response with code @code. Try entering the microservice url again.", [
          '@code' => $request->getStatusCode()
        ]);
        $form_state->setErrorByName('api_uri',$message);
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
  }
}
