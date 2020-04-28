<?php

namespace Drupal\extlink\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays the extlink settings form.
 */
class ExtlinkAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'extlink_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('extlink.settings');
    $renderer = \Drupal::service('renderer');

    $form['extlink_class'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Place an icon next to external links.'),
      '#return_value' => 'ext',
      '#default_value' => $config->get('extlink_class'),
      '#description' => $this->t('Places an <span class="ext"> </span>&nbsp; icon next to external links.'),
    ];

    $form['extlink_mailto_class'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Place an icon next to mailto links.'),
      '#return_value' => 'mailto',
      '#default_value' => $config->get('extlink_mailto_class'),
      '#description' => $this->t('Places an <span class="mailto"> </span>&nbsp; icon next to mailto links.'),
    ];

    $form['extlink_img_class'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Place an icon next to image links.'),
      '#default_value' => $config->get('extlink_img_class', FALSE),
      '#description' => $this->t('If checked, images wrapped in an anchor tag will be treated as external links.'),
    ];

    $form['extlink_subdomains'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude links with the same primary domain.'),
      '#default_value' => $config->get('extlink_subdomains'),
      '#description' => $this->t("For example, a link from 'www.example.com' to the subdomain of 'my.example.com' would be excluded."),
    ];

    $form['extlink_target'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open external links in a new window.'),
      '#default_value' => $config->get('extlink_target'),
    ];

    $form['extlink_target_no_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not alter links with a default target value'),
      '#default_value' => $config->get('extlink_target_no_override'),
      '#description' => $this->t("A link that specifies target='_self' will not be changed to tareget='_blank'."),
      '#states' => [
        'visible' => [
          ':input[name="extlink_target"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['extlink_nofollow'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Tag external links as "no follow".'),
      '#default_value' => $config->get('extlink_nofollow'),
    ];

    $form['extlink_follow_no_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not override rel="follow", if set'),
      '#default_value' => $config->get('extlink_follow_no_override'),
      '#states' => [
        'visible' => [
          ':input[name="extlink_nofollow"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['extlink_alert'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display a pop-up warning when any external link is clicked.'),
      '#default_value' => $config->get('extlink_alert'),
    ];

    $form['extlink_alert_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text to display in the pop-up warning box.'),
      '#rows' => 3,
      '#default_value' => $config->get('extlink_alert_text'),
      '#description' => $this->t('Text to display in the pop-up external link warning box.'),
      '#wysiwyg' => FALSE,
      '#states' => [
        // Only show this field when user opts to display a pop-up warning.
        'visible' => [
          ':input[name="extlink_alert"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $patterns = [
      '#theme' => 'item_list',
      '#items' => [
        ['#markup' => '<code>(example\.com)</code> ' . $this->t('Matches example.com.')],
        ['#markup' => '<code>(example\.com)|(example\.net)</code> ' . $this->t('Multiple patterns can be strung together by using a pipe. Matches example.com OR example.net.')],
        ['#markup' => '<code>(links/goto/[0-9]+/[0-9]+)</code> ' . $this->t('Matches links that go through the <a href="http://drupal.org/project/links">Links module</a> redirect.')],
      ],
    ];

    $wildcards = [
      '#theme' => 'item_list',
      '#items' => [
        ['#markup' => '<code>.</code> ' . $this->t('Matches any character.')],
        ['#markup' => '<code>?</code> ' . $this->t('The previous character or set is optional.')],
        ['#markup' => '<code>\d</code> ' . $this->t('Matches any digit (0-9).')],
        ['#markup' => '<code>[a-z]</code> ' . $this->t('Brackets may be used to match a custom set of characters. This matches any alphabetic letter.')],
      ],
    ];

    $form['patterns'] = [
      '#type' => 'details',
      '#title' => $this->t('Pattern matching.'),
      '#description' =>
      '<p>' . $this->t('External links uses patterns (regular expressions) to match the "href" property of links.') . '</p>' .
      $this->t('Here are some common patterns.') .
      $renderer->render($patterns) .
      $this->t('Common special characters:') .
      $renderer->render($wildcards) .
      '<p>' . $this->t('All special characters (<code>@characters</code>) must also be escaped with backslashes. Patterns are not case-sensitive. Any <a href="http://www.javascriptkit.com/javatutors/redev2.shtml">pattern supported by JavaScript</a> may be used.', ['@characters' => '^ $ . ? ( ) | * +']) . '</p>',
      '#open' => FALSE,
    ];

    $form['patterns']['extlink_exclude'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exclude links matching the pattern.'),
      '#maxlength' => NULL,
      '#default_value' => $config->get('extlink_exclude'),
      '#description' => $this->t('Enter a regular expression for links that you wish to exclude from being considered external.'),
    ];

    $form['patterns']['extlink_include'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Include links matching the pattern.'),
      '#maxlength' => NULL,
      '#default_value' => $config->get('extlink_include'),
      '#description' => $this->t('Enter a regular expression for internal links that you wish to be considered external.'),
    ];

    $form['css_matching'] = [
      '#tree' => FALSE,
      '#type' => 'fieldset',
      '#title' => $this->t('CSS Matching.'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' =>
      '<p>' . $this->t('Use CSS selectors to exclude entirely or only look inside explicitly specified classes and IDs for external links.  These will be passed straight to jQuery for matching.') . '</p>',
    ];

    $form['css_matching']['extlink_css_exclude'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Exclude links inside these CSS selectors.'),
      '#maxlength' => NULL,
      '#default_value' => $config->get('extlink_css_exclude', ''),
      '#description' => $this->t('Enter a comma-separated list of CSS selectors (ie "#block-block-2 .content, ul.menu").'),
    ];

    $form['css_matching']['extlink_css_explicit'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Only look for links inside these CSS selectors.'),
      '#maxlength' => NULL,
      '#default_value' => $config->get('extlink_css_explicit', ''),
      '#description' => $this->t('Enter a comma-separated list of CSS selectors (ie "#block-block-2 .content, ul.menu").'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    \Drupal::configFactory()->getEditable('extlink.settings')
      ->set('extlink_include', $values['extlink_include'])
      ->set('extlink_exclude', $values['extlink_exclude'])
      ->set('extlink_alert_text', $values['extlink_alert_text'])
      ->set('extlink_alert', $values['extlink_alert'])
      ->set('extlink_target', $values['extlink_target'])
      ->set('extlink_target_no_override', $values['extlink_target_no_override'])
      ->set('extlink_nofollow', $values['extlink_nofollow'])
      ->set('extlink_follow_no_override', $values['extlink_follow_no_override'])
      ->set('extlink_subdomains', $values['extlink_subdomains'])
      ->set('extlink_mailto_class', $values['extlink_mailto_class'])
      ->set('extlink_img_class', $values['extlink_img_class'])
      ->set('extlink_class', $values['extlink_class'])
      ->set('extlink_css_exclude', $values['extlink_css_exclude'])
      ->set('extlink_css_explicit', $values['extlink_css_explicit'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['extlink.settings'];
  }

}
