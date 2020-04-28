<?php

/**
 * @file
 * Contains \Drupal\mandrill_template\Form\MandrillTemplateMapForm.
 */

namespace Drupal\mandrill_template\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the MandrillTemplateMap entity edit form.
 *
 * @ingroup mandrill_template
 */
class MandrillTemplateMapForm extends EntityForm {

  /**
   * The entity query.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   */
  public function __construct(QueryFactory $entity_query) {
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var $map \Drupal\mandrill_template\Entity\MandrillTemplateMap */
    $map = $this->entity;

    /* @var $mandrill_api \Drupal\mandrill\MandrillAPI */
    $mandrill_api = \Drupal::service('mandrill.api');
    $templates = $mandrill_api->getTemplates();

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $map->label,
      '#description' => t('The human-readable name of this Mandrill Template Map entity.'),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $map->id,
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => array(
        'source' => array('label'),
        'exists' => array($this, 'exists'),
      ),
      '#description' => t('A unique machine-readable name for this Mandrill Template Map entity. It must only contain lowercase letters, numbers, and underscores.'),
      '#disabled' => !$map->isNew(),
    );

    $form['map_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Template Map Settings'),
      '#collapsible' => FALSE,
      '#prefix' => '<div id="template-wrapper">',
      '#suffix' => '</div>',
    );

    $template_names = array();
    foreach ($templates as $template) {
      $template_names[$template['slug']] = $template;
    }
    // Check if the currently configured template still exists.
    if (!empty($map->template_id) && !array_key_exists($map->template_id, $template_names)) {
      drupal_set_message(t('The configured Mandrill template is no longer available, please select a valid one.'), 'warning');
    }
    if (!empty($templates)) {
      $options = array('' => t('-- Select --'));
      foreach ($templates as $template) {
        $options[$template['slug']] = $template['name'];
      }
      $form['map_settings']['template_id'] = array(
        '#type' => 'select',
        '#title' => t('Email Template'),
        '#description' => t('Select a Mandrill template.'),
        '#options' => $options,
        '#default_value' => isset($map->template_id) ? $map->template_id : '',
        '#required' => TRUE,
        '#ajax' => array(
          'callback' => '::template_callback',
          'wrapper' => 'template-wrapper',
          'method' => 'replace',
          'effect' => 'fade',
          'progress' => array(
            'type' => 'throbber',
            'message' => t('Retrieving template information.'),
          ),
        ),
      );

      $form_template_id = $form_state->getValue('template_id');

      if (!$form_template_id && isset($map->mandrill_template_map_entity_id)) {
        $form_template_id = $map->template_id;
      }

      if ($form_template_id) {
        $regions = array('' => t('-- Select --')) + $this->parseTemplateRegions($template_names[$form_template_id]['publish_code']);
        $form['map_settings']['main_section'] = array(
          '#type' => 'select',
          '#title' => t('Template region'),
          '#description' => t('Select the template region to use for email content. <i>Note that you can populate more regions by attaching an array to your message with the index "mandrill_template_content", using region names as indexes to the content for that region.'),
          '#options' => $regions,
          '#default_value' => isset($map->main_section) ? $map->main_section : '',
          '#required' => TRUE,
        );
      }
      $usable_keys = mandrill_template_map_usage();
      $module_names = mandrill_get_module_key_names();
      $mandrill_in_use = FALSE;
      $available_modules = FALSE;
      $mailsystem_options = array('' => t('-- None --'));
      foreach ($usable_keys as $key => $sys) {
        $mandrill_in_use = TRUE;
        if ($sys === NULL || (isset($map) && $sys == $map->mandrill_template_map_entity_id)) {
          $mailsystem_options[$key] = $module_names[$key];
          $available_modules = TRUE;
        }
      }

      if ($mandrill_in_use) {
        $form['mailsystem_key'] = array(
          '#type' => 'select',
          '#title' => t('Email key'),
          '#description' => t(
            'Select a module and mail key to use this template for outgoing email. Note that if an email has been selected in another Template Mapping, it will not appear in this list. These keys are defined through the %MailSystem interface.',
            array('%MailSystem' => Link::fromTextAndUrl(t('MailSystem'), Url::fromRoute('mailsystem.settings'))->toString())
          ),
          '#options' => $mailsystem_options,
          '#default_value' => isset($map->mailsystem_key) ? $map->mailsystem_key : '',
        );
        if (!$available_modules) {
          drupal_set_message(t("All email-using modules that have been assigned to Mandrill are already assigned to other template maps"), 'warning');
        }
      }

      if (!$mandrill_in_use) {
        drupal_set_message(t("You have not assigned any Modules to use Mandrill: to use this template, make sure Mandrill is assigned in Mailsystem."), 'warning');
      }
    }
    else {
      $form['email_options']['#description'] = t('The template selection is only available if the Mandrill API is correctly configured and available.');
    }

    return $form;
  }

  /**
   * AJAX callback handler for MandrillTemplateMapForm.
   */
  public function template_callback(&$form, FormStateInterface $form_state) {
    return $form['map_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /* @var $template_map \Drupal\mandrill_template\Entity\MandrillTemplateMap */
    $template_map = $this->getEntity();
    $template_map->save();

    \Drupal::service('router.builder')->setRebuildNeeded();

    $form_state->setRedirect('mandrill_template.admin');
  }

  public function exists($id) {
    $entity = $this->entityQuery->get('mandrill_template_map')
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

  /**
   * Parses a Mandrill template to extract its regions.
   */
  private function parseTemplateRegions($html, $tag = 'mc:edit') {
    $instances = array();
    $offset = 0;
    $inst = NULL;
    while ($offset = strpos($html, $tag, $offset)) {
      $start = 1 + strpos($html, '"', $offset);
      $length = strpos($html, '"', $start) - $start;
      $inst = substr($html, $start, $length);
      $instances[$inst] = $inst;
      $offset = $start + $length;
    }
    return $instances;
  }

}
