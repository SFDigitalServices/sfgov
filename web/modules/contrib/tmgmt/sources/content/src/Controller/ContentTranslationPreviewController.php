<?php

namespace Drupal\tmgmt_content\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\tmgmt\JobItemInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Content preview translation controller.
 */
class ContentTranslationPreviewController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates an ContentTranslationPreviewController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Preview job item entity data.
   *
   * @param \Drupal\tmgmt\JobItemInterface $tmgmt_job_item
   *   Job item to be previewed.
   * @param string $view_mode
   *   The view mode that should be used to display the entity.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function preview(JobItemInterface $tmgmt_job_item, $view_mode) {
    // Load entity.
    $entity = $this->entityTypeManager
      ->getStorage($tmgmt_job_item->getItemType())
      ->load($tmgmt_job_item->getItemId());

    // We cannot show the preview for non-existing entities.
    if (!$entity) {
      throw new NotFoundHttpException();
    }
    $data = $tmgmt_job_item->getData();
    $target_langcode = $tmgmt_job_item->getJob()->getTargetLangcode();
    // Populate preview with target translation data.
    $preview = $this->makePreview($entity, $data, $target_langcode);
    // Build view for entity.
    $page = $this->entityTypeManager
      ->getViewBuilder($entity->getEntityTypeId())
      ->view($preview, $view_mode, $preview->language()->getId());

    // The preview is not cacheable.
    $page['#cache']['max-age'] = 0;
    \Drupal::service('page_cache_kill_switch')->trigger();

    return $page;
  }

  /**
   * The _title_callback for the page that renders a single node in preview.
   *
   * @param \Drupal\tmgmt\JobItemInterface $tmgmt_job_item
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function title(JobItemInterface $tmgmt_job_item) {
    $target_language = $tmgmt_job_item->getJob()->getTargetLanguage()->getName();
    $title = $this->entityTypeManager
      ->getStorage($tmgmt_job_item->getItemType())
      ->load($tmgmt_job_item->getItemId())
      ->label();
    return t("Preview of @title for @target_language", [
      '@title' => $title,
      '@target_language' => $target_language,
    ]);
  }

  /**
   * Builds the entity translation for the provided translation data.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity for which the translation should be returned.
   * @param array $data
   *   The translation data for the fields.
   * @param string $target_langcode
   *   The target language.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   Translation data.
   */
  protected function makePreview(ContentEntityInterface $entity, array $data, $target_langcode) {
    // If the translation for this language does not exist yet, initialize it.
    if (!$entity->hasTranslation($target_langcode)) {
      $entity->addTranslation($target_langcode, $entity->toArray());
    }

    $embeded_fields = $this->config('tmgmt_content.settings')->get('embedded_fields');

    $translation = $entity->getTranslation($target_langcode);

    foreach (Element::children($data) as $name) {
      $field_data = $data[$name];
      foreach (Element::children($field_data) as $delta) {
        $field_item = $field_data[$delta];
        foreach (Element::children($field_item) as $property) {
          $property_data = $field_item[$property];
          // If there is translation data for the field property, save it.
          if (isset($property_data['#translation']['#text']) && $property_data['#translate']) {
            $translation->get($name)
              ->offsetGet($delta)
              ->set($property, $property_data['#translation']['#text']);
          }
          // If the field is an embeddable reference, we assume that the
          // property is a field reference. The translation will be available
          // to formatters due to the static entity caching.
          // @todo Evaluate if the item could be tricked into thinking that this
          //   is a new reference. See \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem::hasNewEntity
          //   and \Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase::prepareView.
          elseif (isset($embeded_fields[$entity->getEntityTypeId()][$name])) {
            $this->makePreview($translation->get($name)->offsetGet($delta)->$property, $property_data, $target_langcode);
          }
        }
      }
    }
    return $translation;
  }

}
