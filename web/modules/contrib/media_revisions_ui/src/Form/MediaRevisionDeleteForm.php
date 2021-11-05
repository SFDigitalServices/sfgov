<?php

namespace Drupal\media_revisions_ui\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting media revision.
 *
 * @internal
 */
class MediaRevisionDeleteForm extends ConfirmFormBase {

  /**
   * Media revision.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $revision;

  /**
   * Media storage.
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
   * Constructs a new MediaRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\RevisionableStorageInterface $media_storage
   *   The revisionable storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(RevisionableStorageInterface $media_storage, DateFormatterInterface $date_formatter) {
    $this->mediaStorage = $media_storage;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('media'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => $this->dateFormatter->format(
        $this->revision->getRevisionCreationTime()
      ),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.media.version_history', [
      'media' => $this->revision->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
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
    $this->mediaStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('@type: deleted %title revision %revision.', [
      '@type' => $this->revision->bundle(),
      '%title' => $this->revision->label(),
      '%revision' => $this->revision->getRevisionId(),
    ]);
    $this->messenger()->addStatus(
      $this->t('Revision from %revision-date of %title has been deleted.',
      [
        '%revision-date' => $this->dateFormatter->format(
          $this->revision->getRevisionCreationTime()
        ),
        '%title' => $this->revision->label(),
      ]
    ));
    $form_state->setRedirect('entity.media.canonical', [
      'media' => $this->revision->id(),
    ]);

    $revisionCount = $this->mediaStorage->getQuery()
      ->allRevisions()
      ->condition('mid', $this->revision->id())
      ->count()
      ->execute();

    if ($revisionCount > 1) {
      $form_state->setRedirect('entity.media.version_history', [
        'media' => $this->revision->id(),
      ]);
    }
  }

}
