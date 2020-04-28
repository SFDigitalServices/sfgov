<?php

namespace Drupal\telephone_validation;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use libphonenumber\PhoneNumberFormat;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for default validation settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Validator service.
   *
   * @var \Drupal\telephone_validation\Validator
   */
  protected $validator;

  /**
   * Element Info Manager service.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfoManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param Validator $validator
   *   Telephone validation service.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info_manager
   *   Collects available render array element types.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Validator $validator, ElementInfoManagerInterface $element_info_manager) {
    $this->validator = $validator;
    $this->elementInfoManager = $element_info_manager;
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('telephone_validation.validator'),
      $container->get('plugin.manager.element_info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'telephone_validation_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'telephone_validation.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Retrieve configuration object.
    $config = $this->config('telephone_validation.settings');

    // Define valid telephone format.
    $form['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Format'),
      '#default_value' => $config->get('format') ?: PhoneNumberFormat::E164,
      '#options' => [
        PhoneNumberFormat::E164 => $this->t('E164'),
        PhoneNumberFormat::NATIONAL => $this->t('National'),
      ],
      '#ajax' => [
        'callback' => '::getCountry',
        'wrapper' => 'telephone-validation-country',
        'method' => 'replace',
      ],
    ];

    // Define available countries (or country if format = NATIONAL).
    $val = $form_state->getValue('format') ?: $form['format']['#default_value'];
    $form['country'] = [
      '#type' => 'select',
      '#title' => $this->t('Valid countries'),
      '#description' => $this->t('If no country selected all countries are valid.'),
      '#default_value' => $config->get('country') ?: [],
      '#multiple' => $val != PhoneNumberFormat::NATIONAL,
      '#options' => $this->validator->getCountryList(),
      '#prefix' => '<div id="telephone-validation-country">',
      '#suffix' => '</div>',
    ];

    $form['allow_emergency'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow emergency numbers?'),
      '#description' => $this->t('Check to allow emergency numbers such as 911.'),
      '#default_value' => $config->get('allow_emergency'),
    ];

    $form['allow_short'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow short codes?'),
      '#description' => $this->t('Check to allow short codes such as 311.'),
      '#default_value' => $config->get('allow_short'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Ajax callback.
   */
  public function getCountry(array &$form, FormStateInterface $form_state) {
    return $form['country'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $country = $form_state->getValue('country');
    // Save new config.
    $this->config('telephone_validation.settings')
      ->set('format', $form_state->getValue('format'))
      ->set('country', is_array($country) ? $country : [$country])
      ->set('allow_emergency', $form_state->getValue('allow_emergency'))
      ->set('allow_short', $form_state->getValue('allow_short'))
      ->save();
    // Clear element info cache.
    $this->elementInfoManager->clearCachedDefinitions();

    parent::submitForm($form, $form_state);
  }

}
