<?php

namespace Drupal\media_revisions_ui\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting media revision for a single translation.
 */
class MediaRevisionRevertTranslationForm extends MediaRevisionRevertForm {

  /**
   * The language to be reverted.
   *
   * @var string
   */
  protected $langcode;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new MediaRevisionRevertTranslationForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $media_storage
   *   The media storage service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityStorageInterface $media_storage, DateFormatterInterface $date_formatter, LanguageManagerInterface $language_manager, TimeInterface $time) {
    parent::__construct($media_storage, $date_formatter, $time);
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('media'),
      $container->get('date.formatter'),
      $container->get('language_manager'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_revision_revert_translation_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to revert @language translation to the revision from %revision-date?', [
      '@language' => $this->languageManager->getLanguageName($this->langcode),
      '%revision-date' => $this->dateFormatter->format(
        $this->revision->getRevisionCreationTime()
      ),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $media_revision = NULL, $langcode = NULL) {
    $this->langcode = $langcode;
    $form = parent::buildForm($form, $form_state, $media_revision);

    $isDefaultTranslationAffectedOnly = $this->revision->isDefaultTranslationAffectedOnly();
    $form['revert_untranslated_fields'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Revert content shared among translations'),
      '#default_value' => $isDefaultTranslationAffectedOnly && $this->revision->getTranslation($this->langcode)->isDefaultTranslation(),
      '#access' => !$isDefaultTranslationAffectedOnly,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareRevertedRevision(MediaInterface $revision, FormStateInterface $form_state) {
    $revertUntranslatedFields = (bool) $form_state->getValue('revert_untranslated_fields');
    $translation = $revision->getTranslation($this->langcode);

    return $this->mediaStorage->createRevision(
      $translation,
      TRUE,
      $revertUntranslatedFields
    );
  }

}
