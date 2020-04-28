<?php

namespace Drupal\mandrill\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\RendererInterface;
use Drupal\mandrill\MandrillServiceInterface;
use Drupal\mandrill\MandrillAPIInterface;

/**
 * Implements an Mandrill Admin Settings form.
 */
class MandrillAdminSettingsForm extends ConfigFormBase {

  /**
   * The mail system manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The Mandrill service.
   *
   * @var \Drupal\mandrill\MandrillServiceInterface
   */
  protected $mandrill;

  /**
   * The Mandrill API service.
   *
   * @var \Drupal\mandrill\MandrillAPIInterface
   */
  protected $mandrillAPI;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Drupal\mandrill\MandrillServiceInterface $mandrill
   * @param \Drupal\mandrill\MandrillAPIInterface $mandrill_api
   */
  public function __construct(MailManagerInterface $mail_manager, PathValidatorInterface $path_validator, RendererInterface $renderer, MandrillServiceInterface $mandrill, MandrillAPIInterface $mandrill_api) {
    $this->mailManager = $mail_manager;
    $this->pathValidator = $path_validator;
    $this->renderer = $renderer;
    $this->mandrill = $mandrill;
    $this->mandrillAPI = $mandrill_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.mail'),
      $container->get('path.validator'),
      $container->get('renderer'),
      $container->get('mandrill.service'),
      $container->get('mandrill.api')
    );
  }

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'mandrill_admin_settings';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mandrill.settings');
    $key = $config->get('mandrill_api_key');
    $form['mandrill_api_key'] = array(
      '#title' => $this->t('Mandrill API Key'),
      '#type' => 'textfield',
      '#description' => $this->t('Create or grab your API key from the %link.', array('%link' => $this->l($this->t('Mandrill settings'), Url::fromUri('https://mandrillapp.com/settings/index')))),
      '#default_value' => $key,
    );
    if (!$this->mandrillAPI->isLibraryInstalled()) {
      drupal_set_message($this->t('The Mandrill PHP library is not installed. Please see installation directions in README.txt'), 'warning');
    }
    else if ($key) {
      $mailSystemPath = Url::fromRoute('mailsystem.settings');
      $usage = [];
      foreach ($this->mandrill->getMailSystems() as $system) {
        if ($this->mailConfigurationUsesMandrillMail($system)) {
          $system['sender'] = $this->getPluginLabel($system['sender']);
          $system['formatter'] = $this->getPluginLabel($system['formatter']);
          $usage[] = $system;
        }
      }
      if (!empty($usage)) {
        $usage_array = array(
          '#theme' => 'table',
          '#header' => array(
            $this->t('Key'),
            $this->t('Sender'),
            $this->t('Formatter'),
          ),
          '#rows' => $usage,
        );
        $form['mandrill_status'] = array(
          '#type' => 'markup',
          '#markup' => $this->t('Mandrill is currently configured to be used by the following Module Keys. To change these settings or '
            . 'configure additional systems to use Mandrill, use %link.<br /><br />%table',
            array(
              '%link' => $this->l($this->t('Mail System'), $mailSystemPath),
              '%table' => $this->renderer->render($usage_array),
            )),
        );
      }
      elseif (!$form_state->get('rebuild')) {
        drupal_set_message($this->t(
          'PLEASE NOTE: Mandrill is not currently configured for use by Drupal. In order to route your email through Mandrill, '
          . 'you must configure at least one MailSystemInterface (other than mandrill) to use "MandrillMailSystem" in %link, or '
          . 'you will only be able to send Test Emails through Mandrill.',
          array('%link' => $this->l($this->t('Mail System'), $mailSystemPath))), 'warning');
      }
      $form['email_options'] = array(
        '#type' => 'fieldset',
        '#collapsible' => TRUE,
        '#title' => $this->t('Email options'),
      );
      $form['email_options']['mandrill_from'] = array(
        '#title' => $this->t('From address'),
        '#type' => 'textfield',
        '#description' => $this->t('The sender email address. If this address has not been verified, messages will be queued and not sent until it is. '
          . 'This address will appear in the "from" field, and any emails sent through Mandrill with a "from" address will have that '
          . 'address moved to the Reply-To field.'),
        '#default_value' => $config->get('mandrill_from_email'),
      );
      $form['email_options']['mandrill_from_name'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('From name'),
        '#default_value' => $config->get('mandrill_from_name'),
        '#description' => $this->t('Optionally enter a from name to be used.'),
      );
      $sub_accounts = $this->mandrillAPI->getSubAccounts();
      $sub_accounts_options = array();
      if (!empty($sub_accounts)) {
        $sub_accounts_options = array('_none' => '-- Select --');
        foreach ($sub_accounts as $account) {
          if ($account['status'] == 'active') {
            $sub_accounts_options[$account['id']] = $account['name'] . ' (' . $account['reputation'] . ')';
          }
        }
      }
      elseif ($config->get('mandrill_subaccount')) {
        $config->set('mandrill_subaccount', FALSE)->save();
      }
      if (!empty($sub_accounts_options)) {
        $form['email_options']['mandrill_subaccount'] = array(
          '#type' => 'select',
          '#title' => $this->t('Subaccount'),
          '#options' => $sub_accounts_options,
          '#default_value' => $config->get('mandrill_subaccount'),
          '#description' => $this->t('Choose a subaccount to send through.'),
        );
      }
      $formats = filter_formats();
      $options = array('' => $this->t('-- Select --'));
      foreach ($formats as $v => $format) {
        $options[$v] = $format->get('name');
      }
      $form['email_options']['mandrill_filter_format'] = array(
        '#type' => 'select',
        '#title' => $this->t('Input format'),
        '#description' => $this->t('If selected, the input format to apply to the message body before sending to the Mandrill API.'),
        '#options' => $options,
        '#default_value' => array($config->get('mandrill_filter_format')),
      );
      $form['send_options'] = array(
        '#title' => $this->t('Send options'),
        '#type' => 'fieldset',
        '#collapsible' => TRUE,
      );
      $form['send_options']['mandrill_track_opens'] = array(
        '#title' => $this->t('Track opens'),
        '#type' => 'checkbox',
        '#description' => $this->t('Whether or not to turn on open tracking for messages.'),
        '#default_value' => $config->get('mandrill_track_opens'),
      );
      $form['send_options']['mandrill_track_clicks'] = array(
        '#title' => $this->t('Track clicks'),
        '#type' => 'checkbox',
        '#description' => $this->t('Whether or not to turn on click tracking for messages.'),
        '#default_value' => $config->get('mandrill_track_clicks'),
      );
      $form['send_options']['mandrill_url_strip_qs'] = array(
        '#title' => $this->t('Strip query string'),
        '#type' => 'checkbox',
        '#description' => $this->t('Whether or not to strip the query string from URLs when aggregating tracked URL data.'),
        '#default_value' => $config->get('mandrill_url_strip_qs'),
      );
      $form['send_options']['mandrill_mail_key_blacklist'] = array(
        '#title' => $this->t('Content logging blacklist'),
        '#type' => 'textarea',
        '#description' => $this->t('Comma delimited list of Drupal mail keys to exclude content logging for. CAUTION: Removing the default password reset key may expose a security risk.'),
        '#default_value' => $config->get('mandrill_mail_key_blacklist'),
      );
      $form['send_options']['mandrill_log_defaulted_sends'] = array(
        '#title' => $this->t('Log sends from module/key pairs that are not registered independently in mailsystem.'),
        '#type' => 'checkbox',
        '#description' => $this->t('If you select Mandrill as the site-wide default email sender in %mailsystem and check this box, any messages that are sent through Mandrill using module/key pairs that are not specifically registered in mailsystem will cause a message to be written to the %systemlog (type: Mandrill, severity: info). Enable this to identify keys and modules for automated emails for which you would like to have more granular control. It is not recommended to leave this box checked for extended periods, as it slows Mandrill and can clog your logs.',
          array(
            '%mailsystem' => $this->l($this->t('Mail System'), $mailSystemPath),
            '%systemlog' => $this->l($this->t('system log'), Url::fromRoute('dblog.overview')),
          )),
        '#default_value' => $config->get('mandrill_log_defaulted_sends'),
      );
      $form['analytics'] = array(
        '#type' => 'fieldset',
        '#collapsible' => TRUE,
        '#title' => $this->t('Google analytics'),
      );
      $form['analytics']['mandrill_analytics_domains'] = array(
        '#title' => $this->t('Google analytics domains'),
        '#type' => 'textfield',
        '#description' => $this->t('One or more domains for which any matching URLs will automatically have Google Analytics parameters appended to their query string. Separate each domain with a comma.'),
        '#default_value' => $config->get('mandrill_analytics_domains'),
      );
      $form['analytics']['mandrill_analytics_campaign'] = array(
        '#title' => $this->t('Google analytics campaign'),
        '#type' => 'textfield',
        '#description' => $this->t("The value to set for the utm_campaign tracking parameter. If this isn't provided the messages from address will be used instead."),
        '#default_value' => $config->get('mandrill_analytics_campaign'),
      );
      $form['asynchronous_options'] = array(
        '#title' => $this->t('Asynchronous options'),
        '#type' => 'fieldset',
        '#collapsible' => TRUE,
        '#attributes' => array(
          'id' => array('mandrill-async-options'),
        ),
      );
      $form['asynchronous_options']['mandrill_process_async'] = array(
        '#title' => $this->t('Queue outgoing messages'),
        '#type' => 'checkbox',
        '#description' => $this->t('When set, emails will not be immediately sent. Instead, they will be placed in a queue and sent when cron is triggered.'),
        '#default_value' => $config->get('mandrill_process_async'),
      );
      $form['asynchronous_options']['mandrill_batch_log_queued'] = array(
        '#title' => $this->t('Log queued emails'),
        '#type' => 'checkbox',
        '#description' => $this->t('Do you want to create a log entry when an email is queued to be sent?'),
        '#default_value' => $config->get('mandrill_batch_log_queued'),
        '#states' => array(
          'invisible' => array(
            ':input[name="mandrill_process_async"]' => array('checked' => FALSE),
          ),
        ),
      );
      $form['asynchronous_options']['mandrill_queue_worker_timeout'] = array(
        '#title' => $this->t('Queue worker timeout'),
        '#type' => 'textfield',
        '#size' => '12',
        '#description' => $this->t('Number of seconds to spend processing messages during cron. Zero or negative values are not allowed.'),
        //'#required' => TRUE,
        //'#element_validate' => array('element_validate_integer_positive'),
        '#default_value' => $config->get('mandrill_queue_worker_timeout'),
        '#states' => array(
          'invisible' => array(
            ':input[name="mandrill_process_async"]' => array('checked' => FALSE),
          ),
        ),
      );
    }
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * Check if a mail configuration has sender or formatter set to Mandrill.
   *
   * @param array $configuration
   *   Must have keys sender and formatter set.
   *
   * @return bool
   *   TRUE if configuration uses, FALSE otherwise.
   */
  private function mailConfigurationUsesMandrillMail(array $configuration) {
    // The sender and formatter is required keys.
    if (!isset($configuration['sender']) || !isset($configuration['formatter'])) {
      return FALSE;
    }
    if ($configuration['sender'] === 'mandrill_mail' || $configuration['formatter'] === 'mandrill_mail') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get the label for a mail plugin.
   *
   * @param string $plugin_id
   *
   * @return string
   */
  private function getPluginLabel($plugin_id) {
    $definition = $this->mailManager->getDefinition($plugin_id);
    if (isset($definition['label'])) {
      $plugin_label = $definition['label'];
    }
    else {
      $plugin_label = $this->t('Unknown Plugin (%id)', ['%id' => $plugin_id]);
    }
    return $plugin_label;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('mandrill.settings')
      ->set('mandrill_api_key', $form_state->getValue('mandrill_api_key'))
      ->set('mandrill_from_email', $form_state->getValue('mandrill_from'))
      ->set('mandrill_from_name', $form_state->getValue('mandrill_from_name'))
      ->set('mandrill_subaccount', $form_state->getValue('mandrill_subaccount'))
      ->set('mandrill_filter_format', $form_state->getValue('mandrill_filter_format'))
      ->set('mandrill_track_opens', $form_state->getValue('mandrill_track_opens'))
      ->set('mandrill_track_clicks', $form_state->getValue('mandrill_track_clicks'))
      ->set('mandrill_url_strip_qs', $form_state->getValue('mandrill_url_strip_qs'))
      ->set('mandrill_mail_key_blacklist', $form_state->getValue('mandrill_mail_key_blacklist'))
      ->set('mandrill_log_defaulted_sends', $form_state->getValue('mandrill_log_defaulted_sends'))
      ->set('mandrill_analytics_domains', $form_state->getValue('mandrill_analytics_domains'))
      ->set('mandrill_analytics_campaign', $form_state->getValue('mandrill_analytics_campaign'))
      ->set('mandrill_process_async', $form_state->getValue('mandrill_process_async'))
      ->set('mandrill_batch_log_queued', $form_state->getValue('mandrill_batch_log_queued'))
      ->set('mandrill_queue_worker_timeout', $form_state->getValue('mandrill_queue_worker_timeout'))
      ->save();
  }

  /**
   * {@inheritdoc}.
   */
  protected function getEditableConfigNames() {
    return ['mandrill.settings'];
  }
}
