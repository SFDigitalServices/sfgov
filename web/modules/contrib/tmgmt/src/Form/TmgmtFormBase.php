<?php

namespace Drupal\tmgmt\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the job item edit forms.
 *
 * @ingroup tmgmt_job
 */
class TmgmtFormBase extends ContentEntityForm {

  /**
   * Translator plugin manager.
   *
   * @var \Drupal\tmgmt\TranslatorManager
   */
  protected $translatorManager;

  /**
   * Source plugin manager.
   *
   * @var \Drupal\tmgmt\SourceManager
   */
  protected $sourceManager;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = parent::create($container);
    $form->translatorManager = $container->get('plugin.manager.tmgmt.translator');
    $form->sourceManager = $container->get('plugin.manager.tmgmt.source');
    $form->renderer = $container->get('renderer');
    $form->dateFormatter = $container->get('date.formatter');

    return $form;
  }

}
