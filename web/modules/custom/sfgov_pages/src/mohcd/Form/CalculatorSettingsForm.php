<?php

namespace Drupal\sfgov_pages\mohcd\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CalculatorSettingsForm extends ConfigFormBase {

  const SETTINGS = 'sfgov_pages.mohcd_calculator_settings';

  /**
   * The state store.
   */
  protected $state;

  /**
   * Creates a MOHCD form instance.
   */
  public function __construct(
    StateInterface $state
  ) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sfgov_mohcd_calculator_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sfgov_pages.mohcd_calculator_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return [
      'currentYearAMI' => $this->state->get('sfgov_pages_mohcd_currentYearAMI'),
      'yearAMI' => $this->state->get('sfgov_pages_mohcd_yearAMI'),
      'embed_page' => $this->state->get('sfgov_pages_mohcd_embed_page'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setState($key, $value) {
    $this->state->set($key, $value);
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $state = $this->getState();
    $entity_manager = \Drupal::entityTypeManager();
    $node_manager = $entity_manager->getStorage('node');

    $form['info']['#markup'] = $this->t('This form allows you to associate AMI with year.  These values will be used for MOHCD\'s estimated valuation for inclusionary homes calculator.');

    $form['test_link'] = [
      '#prefix' => '<p>',
      '#markup' => Link::createFromRoute('View calculator test page', 'sfgov_pages.mohcd_bmr_valuation_calculator')->toString(),
      '#suffix' => '</p>',
    ];

    $form['currentYearAMI'] = [
      '#id' => 'currentYearAMI',
      '#type' => 'textfield',
      '#title' => t('Current year AMI'),
      '#required' => TRUE,
      '#default_value' => !empty($state['currentYearAMI']) ? $state['currentYearAMI'] : '',
    ];

    $form['yearAMI'] = [
      '#id' => 'yearAMI',
      '#type' => 'textarea',
      '#title' => t('Year AMI values'),
      '#rows' => 29,
      '#description' => t('Enter values as YEAR|AMI.  One value per line.'),
      '#description_display' => 'before',
      '#required' => TRUE,
      '#default_value' => !empty($state['yearAMI']) ? $state['yearAMI'] : '',
    ];

    $transaction_node = $node_manager->load($state['embed_page']);
    $form['embed_page'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#selection_handler' => 'default', // Optional. The default selection handler is pre-populated to 'default'.
      '#selection_settings' => array(
        'target_bundles' => array('transaction'),
      ),
      '#id' => 'embed_page',
      '#title' => t('Transaction embed'),
      '#description' => t('Enter the transaction page you would like to embed the calculator on. This will render the calculator after the "What to do" section.'),
      '#description_display' => 'before',
      '#default_value' => !empty($state['embed_page']) ? $transaction_node : null,
    ];

    if (!empty($state['embed_page'])) {
      $form['embed_link'] = [
        '#prefix' => '<p>',
        '#markup' => Link::createFromRoute('View calculator embed page', 'entity.node.canonical', ['node' => $state['embed_page']])
          ->toString(),
        '#suffix' => '</p>',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Set form validation.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $yearAMIValue = $form_state->getValue('yearAMI') ?? '';

    if (!empty($yearAMIValue)) {
      $yearAMIItems = preg_split('/\r\n|\r|\n/', $yearAMIValue);

      $errors = [];
      foreach($yearAMIItems as $yearAMIItem) {
        $value = explode("|", $yearAMIItem);
        if (empty($value)
          || count($value) !== 2
          || empty(trim($value[0]))
          || empty(trim($value[1]))
          || !is_numeric(trim($value[0]))
          || !is_numeric(trim($value[1]))) {
          $errors[] = "<li>$yearAMIItem</li>";
        }
      }

      if (!empty($errors)) {
        $errorMsg = $this->t('Some values are not properly formatted:<br><ul>' . implode($errors) . '</ul>');
        $form_state->setErrorByName('yearAMI', $errorMsg);
      }
    }
  }

  /**
   * Set form submission.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $state = $this->getState();

    foreach ($values as $key => $value) {
      if ($key == 'yearAMI') {
        $this->setState('sfgov_pages_mohcd_yearAMI', $value);
      }
      if ($key == 'currentYearAMI') {
        $this->setState('sfgov_pages_mohcd_currentYearAMI', $value);
      }
      if ($key == 'embed_page') {
        $this->setState('sfgov_pages_mohcd_embed_page', $value);
      }
    }

    // Let our guests know that all is updated and well.
    \Drupal::messenger()->addMessage(
      $this->t('Settings updated.')
    );

    parent::submitForm($form, $form_state);
  }
}

