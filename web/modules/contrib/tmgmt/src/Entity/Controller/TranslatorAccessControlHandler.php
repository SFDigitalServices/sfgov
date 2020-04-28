<?php

namespace Drupal\tmgmt\Entity\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\tmgmt\TranslatorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access control handler for the translator entity.
 *
 * @see \Drupal\tmgmt\Plugin\Core\Entity\Translator.
 *
 * @ingroup tmgmt_translator
 */
class TranslatorAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {
  /**
   * The translator manager which knows about installed translator plugins.
   *
   * @var \Drupal\tmgmt\TranslatorManager $translatorManager
   */
  protected $translatorManager;

  /**
   * Constructs a TranslatorAccessControlHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\tmgmt\TranslatorManager $translator_manager
   *   The translator manager.
   */
  public function __construct(EntityTypeInterface $entity_type, TranslatorManager $translator_manager) {
    parent::__construct($entity_type);

    $this->translatorManager = $translator_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('plugin.manager.tmgmt.translator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Don't allow delete access for busy translators.
    if ((!$entity->hasPlugin() && $operation != 'delete') || ($operation == 'delete' && tmgmt_translator_busy($entity->id()))) {
      return AccessResult::forbidden();
    }
    return AccessResult::allowedIfHasPermission($account, 'administer tmgmt');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $installed_translators = $this->translatorManager->getLabels();
    if (empty($installed_translators)) {
      return AccessResult::forbidden();
    }
    return AccessResult::allowedIfHasPermission($account, 'administer tmgmt');
  }


}
