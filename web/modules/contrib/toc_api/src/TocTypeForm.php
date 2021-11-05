<?php

/**
 * @file
 * Contains Drupal\toc_api\TocTypeForm.
 */

namespace Drupal\toc_api;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Theme\Registry;
use Drupal\Core\Theme\ThemeInitializationInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for TOC type add and edit forms.
 */
class TocTypeForm extends EntityForm {

  /**
   * The entity type manager to create query factory.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The theme registry.
   *
   * @var \Drupal\Core\Theme\Registry
   */
  protected $themeRegistry;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The theme initialization logic.
   *
   * @var \Drupal\Core\Theme\ThemeInitializationInterface
   */
  protected $themeInitialization;

  /**
   * Constructs a new TocTypeForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity query factory.
   *
   * @param \Drupal\Core\Theme\Registry $theme_registry
   *   The theme registry.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Registry $theme_registry, ThemeManagerInterface $theme_manager, ThemeInitializationInterface $theme_initialization) {
    $this->entityTypeManager = $entity_type_manager;
    $this->themeRegistry = $theme_registry;
    $this->themeManager = $theme_manager;
    $this->themeInitialization = $theme_initialization;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('theme.registry'),
      $container->get('theme.manager'),
      $container->get('theme.initialization')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $toc_type = $this->entity;
    $options = $toc_type->getOptions();

    // An associative array of HTML header tags keyed by level.
    $header_options = [
      1 => 'h1',
      2 => 'h2',
      3 => 'h3',
      4 => 'h4',
      5 => 'h5',
      6 => 'h6',
    ];

    // An associative array of HTML list style types used for numbering.
    $numbering_options = [
      'decimal' => 'decimal (1, 2, 3...)',
      'lower-alpha' => 'lower-alpha (a, b, c...)',
      'upper-alpha' => 'upper-alpha (A, B, C...)',
      'lower-roman' => 'lower-roman (i, ii, iii...)',
      'upper-roman' => 'upper-roman (I, II, III...)',
      'circle' => 'circle',
      'disc' => 'disc',
      'square' => 'square',
      'none' => 'none',
    ];

    $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'toc_api/toc_type';
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $toc_type->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $toc_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\toc_api\Entity\TocType::load',
      ],
      '#disabled' => !$toc_type->isNew(),
    ];

    $form['options'] = [
      '#type' => 'container',
    ];
    $form['options']['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['options']['general']['template'] = [
      '#title' => $this->t('Table of contents type'),
      '#type' => 'select',
      '#options' => $this->getTemplates(),
      '#default_value' => $options['template'],
    ];
    $form['options']['general']['title'] = [
      '#title' => $this->t('Table of contents title'),
      '#type' => 'textfield',
      '#default_value' => $options['title'],
    ];
    // Hide block option since it is up to TOC submodule to decide how to
    // support it.
    $form['options']['general']['block'] = [
      '#title' => $this->t('Display table of contents in a block.'),
      '#type' => 'checkbox',
      '#default_value' => $options['block'],
      '#access' => FALSE,
    ];
    $form['options']['header'] = [
      '#type' => 'details',
      '#title' => $this->t('Header settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['options']['header']['header_count'] = [
      '#title' => t('Number of headers required to generate a table of contents'),
      '#type' => 'number',
      '#size' => 10,
      '#maxlength' => 10,
      '#default_value' => $options['header_count'],
    ];
    $form['options']['header']['header_min'] = [
      '#title' => $this->t('Header minimum level'),
      '#type' => 'select',
      '#options' => $header_options,
      '#default_value' => $options['header_min'],
      '#attributes' => ['class' => ['js-toc-type-options-header-min']],
    ];
    $form['options']['header']['header_max'] = [
      '#title' => $this->t('Header maximum level'),
      '#type' => 'select',
      '#options' => $header_options,
      '#default_value' => $options['header_max'],
      '#attributes' => ['class' => ['js-toc-type-options-header-max']],
    ];
    $form['options']['header']['header_allowed_tags'] = [
      '#title' => $this->t('Header allowed tags'),
      '#type' => 'textfield',
      '#default_value' => $options['header_allowed_tags'],
    ];

    $form['options']['header']['header_id'] = [
      '#title' => $this->t('Header id type'),
      '#type' => 'select',
      '#options' => [
        'title' => 'title',
        'key' => 'key',
        'number_path' => 'number_path',
      ],
      '#default_value' => $options['header_id'],
    ];
    $form['options']['header']['header_id_prefix'] = [
      '#title' => $this->t('Header id prefix'),
      '#type' => 'textfield',
      '#default_value' => $options['header_id_prefix'],
    ];

    $form['options']['top'] = [
      '#type' => 'details',
      '#title' => $this->t('Back to top settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['options']['top']['top_min'] = [
      '#title' => $this->t('Back to top minimum level'),
      '#type' => 'select',
      '#options' => $header_options,
      '#default_value' => $options['top_min'],
    ];
    $form['options']['top']['top_max'] = [
      '#title' => $this->t('Back to top  maximum level'),
      '#type' => 'select',
      '#options' => $header_options,
      '#default_value' => $options['top_max'],
    ];
    $form['options']['top']['top_label'] = [
      '#title' => $this->t('Back to top label'),
      '#type' => 'textfield',
      '#default_value' => $options['top_label'],
    ];

    $form['options']['numbering'] = [
      '#type' => 'details',
      '#title' => $this->t('Numbering settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['options']['numbering']['default'] = [];
    $form['options']['numbering']['default']['number_type'] = [
      '#title' => $this->t('Numbering type'),
      '#type' => 'select',
      '#options' => $numbering_options,
      '#default_value' => $options['default']['number_type'],
    ];
    $form['options']['numbering']['default']['number_prefix'] = [
      '#title' => $this->t('Numbering  prefix'),
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 10,
      '#default_value' => $options['default']['number_prefix'],
    ];
    $form['options']['numbering']['default']['number_suffix'] = [
      '#title' => $this->t('Numbering suffix'),
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 10,
      '#default_value' => $options['default']['number_suffix'],
    ];
    $form['options']['numbering']['number_path_separator'] = [
      '#title' => $this->t('Numbering separator'),
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 10,
      '#default_value' => $options['number_path_separator'],
    ];
    $form['options']['numbering']['number_path'] = [
      '#title' => $this->t('Display entire numbering path in each header.'),
      '#type' => 'checkbox',
      '#default_value' => $options['number_path'],
    ];
    $form['options']['numbering']['number_path_truncate'] = [
      '#title' => $this->t('Truncate the numbering path to only display parents.'),
      '#type' => 'checkbox',
      '#default_value' => $options['number_path'],
    ];

    foreach ($header_options as $header_tag) {
      $header_options = isset($options['headers'][$header_tag]) ? $options['headers'][$header_tag] : [];
      $header_options += [
        'custom' => ($header_options) ? TRUE : FALSE,
        'number_type' => '',
        'number_prefix' => '',
        'number_suffix' => '',
      ];
      $states = [
        'invisible' => [
          ".js-toc-type-options-headers-$header_tag-custom" => [
            'checked' => FALSE,
          ],
        ],
      ];
      $form['options']['numbering']['headers'][$header_tag] = [
        '#type' => 'details',
        '#title' => $header_tag,
        '#open' => $header_options['custom'],
        '#attributes' => ['class' => ["js-toc-type-options-headers-$header_tag"]],
      ];
      $form['options']['numbering']['headers'][$header_tag]['custom'] = [
        '#title' => $this->t('Customize @tag numbering', [
            '@tag' => $header_tag,
          ]
        ),
        '#type' => 'checkbox',
        '#default_value' => $header_options['custom'],
        '#attributes' => ['class' => ["js-toc-type-options-headers-$header_tag-custom"]],

      ];
      $form['options']['numbering']['headers'][$header_tag]['number_type'] = [
        '#title' => $this->t('Numbering type'),
        '#type' => 'select',
        '#options' => $numbering_options,
        '#default_value' => $header_options['number_type'],
        '#states' => $states,
        '#attributes' => ['class' => ["js-toc-type-options-headers-$header_tag-number-type"]],
      ];
      $form['options']['numbering']['headers'][$header_tag]['number_prefix'] = [
        '#title' => $this->t('Numbering  prefix'),
        '#type' => 'textfield',
        '#size' => 10,
        '#maxlength' => 10,
        '#default_value' => $header_options['number_prefix'],
        '#states' => $states,
        '#attributes' => ['class' => ["js-toc-type-options-headers-$header_tag-number-prefix"]],
      ];
      $form['options']['numbering']['headers'][$header_tag]['number_suffix'] = [
        '#title' => $this->t('Numbering suffix'),
        '#type' => 'textfield',
        '#size' => 10,
        '#maxlength' => 10,
        '#default_value' => $header_options['number_suffix'],
        '#states' => $states,
        '#attributes' => ['class' => ["js-toc-type-options-headers-$header_tag-number-suffix"]],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $options = $values['general'] + $values['header'] + $values['top'] + $values['numbering'];

    // Convert min and max to integers.
    foreach ($options as $key => $value) {
      if (preg_match('/_(min|max)$/', $key)) {
        $options[$key] = (int) $value;
      }
    }

    // Unset headers not included in header range or have not been customized.
    for ($i = 1; $i <= 6; $i++) {
      if ($i < $options['header_min'] || $i > $options['header_max'] || empty($options['headers']["h$i"]['custom'])) {
        unset($options['headers']["h$i"]);
      }
      else {
        unset($options['headers']["h$i"]['custom']);
      }
    }
    $form_state->setValue('options', $options);

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\toc_api\Entity\TocType $toc_type */
    $toc_type = $this->getEntity();
    $toc_type->save();

    $this->logger('toc_api')->notice('Table of contents type @label saved.', ['@label' => $toc_type->label()]);
    $this->messenger()->addMessage($this->t('Table of contents type %label saved.', ['%label' => $toc_type->label()]));

    $form_state->setRedirect('entity.toc_type.collection');
  }

  /**
   * Determines if the TOC type already exists.
   *
   * @param string $id
   *   The ID.
   *
   * @return bool
   *   TRUE if the TOC type exists, FALSE otherwise.
   */
  public function exists($id) {
    return (bool) $this->entityTypeManager
      ->get('toc_type')
      ->condition('id', $id)
      ->execute();
  }

  /**
   * Get TOC templates from the theme registry for the default theme as an associative array of options.
   *
   * @return array
   *   TOC templates as an associative array of options.
   */
  protected function getTemplates() {
    $default_theme = $this->themeInitialization->getActiveThemeByName($this->config('system.theme')->get('default'));
    $active_theme = $this->themeManager->getActiveTheme();

    // Switch to the default theme.
    $this->themeManager->setActiveTheme($default_theme);

    $templates = [];
    $registry = $this->themeRegistry->get();
    foreach ($registry as $template_name => $template_settings) {
      // Find toc_* templates that only accept 'toc' and 'attributes' as the
      // variables.  This might be too generic of an approach to finding
      // TOC templates and a custom attribute may need to added to hook_theme()
      // info template settings.
      if (strpos($template_name, 'toc_') === 0 && array_keys($template_settings['variables']) == ['toc', 'attributes']) {
        $toc_name = preg_replace('/^toc_/', '', $template_name);
        $templates[$toc_name] = $toc_name;
      }
    }

    // Switch back to the active theme.
    $this->themeManager->setActiveTheme($active_theme);

    return $templates;
  }

}
