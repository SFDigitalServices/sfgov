<?php

namespace Drupal\media_revisions_ui\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting media revision.
 */
class MediaRevisionRevertForm extends ConfirmFormBase {

  /**
   * Media revision.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $revision;

  /**
   * The media storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mediaStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new MediaRevisionRevertForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $media_storage
   *   The media storage service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityStorageInterface $media_storage, DateFormatterInterface $date_formatter, TimeInterface $time) {
    $this->mediaStorage = $media_storage;
    $this->dateFormatter = $date_formatter;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('media'),
      $container->get('date.formatter'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_revision_revert_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to revert to the revision from %revision-date?', ['%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime())]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.media.version_history', ['media' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Revert');
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
  public function buildForm(array $form, FormStateInterface $form_state, $media_revision = NULL) {
    $this->revision = $this->mediaStorage->loadRevision($media_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $originalRevisionTime = $this->revision->getRevisionCreationTime();

    $this->revision = $this->prepareRevertedRevision($this->revision, $form_state);
    $this->revision->setRevisionLogMessage($this->t(
      'Copy of the revision from %date.',
      ['%date' => $this->dateFormatter->format($originalRevisionTime)]
    ));
    $this->revision->setRevisionUserId($this->currentUser()->id());
    $this->revision->setRevisionCreationTime($this->time->getRequestTime());
    $this->revision->setChangedTime($this->time->getRequestTime());
    $this->revision->save();

    $this->logger('content')->notice(
      '@type: reverted %title revision %revision.',
      [
        '@type' => $this->revision->getEntityType()->getLabel(),
        '%title' => $this->revision->label(),
        '%revision' => $this->revision->getRevisionId(),
      ]
    );
    $this->messenger()
      ->addStatus($this->t('@type %title has been reverted to the revision from %revision-date.', [
        '@type' => $this->revision->getEntityType()->getLabel(),
        '%title' => $this->revision->label(),
        '%revision-date' => $this->dateFormatter->format($originalRevisionTime),
      ]));
    $form_state->setRedirect(
      'entity.media.version_history',
      ['media' => $this->revision->id()]
    );
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\media\MediaInterface $revision
   *   The revision to be reverted.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\media\MediaInterface
   *   The prepared revision ready to be stored.
   */
  protected function prepareRevertedRevision(MediaInterface $revision, FormStateInterface $form_state) {
    $revision->setNewRevision();
    $revision->isDefaultRevision(TRUE);

    return $revision;
  }

}
