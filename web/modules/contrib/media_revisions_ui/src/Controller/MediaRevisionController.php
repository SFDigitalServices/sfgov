<?php

namespace Drupal\media_revisions_ui\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\media\MediaInterface;
use Drupal\media\MediaStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list of media revisions for a given media.
 */
class MediaRevisionController extends ControllerBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a MediaRevisionController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer, EntityRepositoryInterface $entity_repository = NULL) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('entity.repository')
    );
  }

  /**
   * Generates an overview table of older revisions of media.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A media object.
   *
   * @return array
   *   An array expected by \Drupal\Core\Render\RendererInterface::render().
   */
  public function revisionOverview(MediaInterface $media) {
    $account = $this->currentUser();
    $langcode = $media->language()->getId();
    $langname = $media->language()->getName();
    $languages = $media->getTranslationLanguages();
    $hasTranslations = (count($languages) > 1);

    /** @var \Drupal\media\MediaStorage $mediaStorage */
    $mediaStorage = $this->entityTypeManager()->getStorage('media');

    $title = $this->t('Revisions for %title', [
      '%title' => $media->label(),
    ]);
    if ($hasTranslations) {
      $title = $this->t('@langname revisions for %title', [
        '@langname' => $langname,
        '%title' => $media->label(),
      ]);
    }

    $build['#title'] = $title;
    $header = [
      $this->t('Revision'),
      $this->t('Operations'),
    ];

    $type = $media->bundle();
    $canRevert = $this->accountHasRevertPermission($type, $account) && $media->access('update');
    $canDelete = $this->accountHasDeletePermission($type, $account) && $media->access('delete');

    $rows = [];
    $defaultRevision = $media->getRevisionId();
    $currentRevisionDisplayed = FALSE;

    foreach ($this->getRevisionIds($media, $mediaStorage) as $vid) {
      /** @var \Drupal\media\MediaInterface $revision */
      $revision = $mediaStorage->loadRevision($vid);
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        $date = $this->dateFormatter->format(
          $revision->getRevisionCreationTime(),
          'short'
        );

        $isCurrentRevision = $vid == $defaultRevision || (!$currentRevisionDisplayed && $revision->wasDefaultRevision());
        if (!$isCurrentRevision) {
          $link = Link::fromTextAndUrl($date, new Url(
            'entity.media.revision',
            [
              'media' => $media->id(),
              'media_revision' => $vid,
            ]
          ))->toString();
        }
        else {
          $link = $media->toLink($date)->toString();
          $currentRevisionDisplayed = TRUE;
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];

        $this->renderer->addCacheableDependency($column['data'], $username);
        $row[] = $column;

        if ($isCurrentRevision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];

          $rows[] = [
            'data' => $row,
            'class' => ['revision-current'],
          ];
        }
        else {
          $links = [];
          if ($canRevert) {
            $revertLink = Url::fromRoute(
              'entity.media.revision_revert_confirm',
              [
                'media' => $media->id(),
                'media_revision' => $vid,
              ]
            );
            if ($hasTranslations) {
              $revertLink = Url::fromRoute(
                'entity.media.revision_revert_translation_confirm',
                [
                  'media' => $media->id(),
                  'media_revision' => $vid,
                  'langcode' => $langcode,
                ]
              );
            }
            $title = $this->t('Set as current revision');
            if ($vid < $media->getRevisionId()) {
              $title = $this->t('Revert');
            }
            $links['revert'] = [
              'title' => $title,
              'url' => $revertLink,
            ];
          }

          if ($canDelete) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute(
                'entity.media.revision_delete_confirm',
                [
                  'media' => $media->id(),
                  'media_revision' => $vid,
                ]
              ),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];

          $rows[] = $row;
        }
      }
    }

    $build['media_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attached' => [
        'library' => [
          'media_revisions_ui/media_revisions_ui.admin',
        ],
      ],
      '#attributes' => [
        'class' => 'media-revision-table',
      ],
    ];
    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }

  /**
   * Gets a list of media revision IDs for a given media.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media entity to search for revisions.
   * @param \Drupal\media\MediaStorage $mediaStorage
   *   Media storage to load revisions from.
   *
   * @return int[]
   *   Media revision IDs in descending order.
   */
  protected function getRevisionIds(MediaInterface $media, MediaStorage $mediaStorage) {

    $result = $mediaStorage->getQuery()
      ->allRevisions()
      ->condition('mid', $media->id())
      ->sort('vid', 'DESC')
      ->pager(50)
      ->execute();

    return array_keys($result);
  }

  /**
   * Checks if account can revert a given media type.
   *
   * @param string $mediaType
   *   Media type to check permission.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account to check for permissions.
   *
   * @return bool
   *   TRUE if account can revert a given media type, otherwise FALSE.
   */
  protected function accountHasRevertPermission($mediaType, AccountInterface $account) {
    $hasRevertPermission = FALSE;
    $revertPermissions = [
      "revert $mediaType media revisions",
      'revert all media revisions',
      'administer media',
    ];
    foreach ($revertPermissions as $permission) {
      if ($account->hasPermission($permission)) {
        $hasRevertPermission = TRUE;
        break;
      }
    }

    return $hasRevertPermission;
  }

  /**
   * Checks if account can delete a given media type.
   *
   * @param string $mediaType
   *   Media type to check permission.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account to check for permissions.
   *
   * @return bool
   *   TRUE if account can delete a given media type, otherwise FALSE.
   */
  protected function accountHasDeletePermission($mediaType, AccountInterface $account) {
    $hasRevertPermission = FALSE;
    $revertPermissions = [
      "delete $mediaType media revisions",
      'delete all media revisions',
      'administer media',
    ];
    foreach ($revertPermissions as $permission) {
      if ($account->hasPermission($permission)) {
        $hasRevertPermission = TRUE;
        break;
      }
    }

    return $hasRevertPermission;
  }

}
