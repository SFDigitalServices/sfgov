<?php

namespace Drupal\sfgov_untranslated_aliases;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\path_alias\AliasRepositoryInterface;
use Drupal\path_alias\PathProcessor\AliasPathProcessor;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes the inbound and outbound paths using path alias lookups.
 */
class PathProcessorUntranslatedAlias extends AliasPathProcessor implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * The path alias storage service.
   *
   * @var \Drupal\path_alias\AliasRepositoryInterface
   */
  protected AliasRepositoryInterface $aliasStorage;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * Current language id.
   *
   * @var string
   */
  protected string $currentLangId;

  /**
   * Default language id.
   *
   * @var string
   */
  protected string $defaultLangId;

  /**
   * Constructs a PathProcessorFakeAlias object.
   *
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   An alias manager for looking up the system path.
   * @param \Drupal\path_alias\AliasRepositoryInterface $alias_storage
   *   The path alias storage.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(AliasManagerInterface $alias_manager, AliasRepositoryInterface $alias_storage, LanguageManagerInterface $language_manager) {
    parent::__construct($alias_manager);

    $this->aliasStorage = $alias_storage;
    $this->languageManager = $language_manager;
    $this->currentLangId = $this->languageManager->getCurrentLanguage()->getId();
    $this->defaultLangId = $this->languageManager->getDefaultLanguage()->getId();
  }

  /**
   * Convert path alias to system path.
   *
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $path = parent::processInbound($path, $request);

    // Current language has no alias, but original has one.
    if (!$this->aliasStorage->lookupByAlias($path, $this->currentLangId)
        && $this->aliasStorage->lookupByAlias($path, $this->defaultLangId)
    ) {
      // Get node source path from passed alias in original language.
      $path = $this->aliasManager->getPathByAlias($path, $this->defaultLangId);
    }

    return $path;
  }

  /**
   * Convert system path to path alias.
   *
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    $path = parent::processOutbound($path, $options, $request, $bubbleable_metadata);
    $requestedLangId = isset($options['language']) ? $options['language']->getId() : NULL;

    if (empty($options['alias'])) {

      // Process paths for the non-default languages.
      if (!empty($requestedLangId) && $requestedLangId != $this->defaultLangId) {
        // Alias doesn't exist for requested language.
        if (!$this->aliasStorage->lookupBySystemPath($path, $requestedLangId)
          && !$this->aliasStorage->lookupByAlias($path, $requestedLangId)
        ) {
          // Instead of original node source path, get node alias.
          $path = $this->aliasManager->getAliasByPath($path, $this->defaultLangId);
        }
      }

      // Edge scenario - prevents redirects from /[lang]/alias to /[lang]/node/[id].
      else {
        if (!$this->aliasStorage->lookupBySystemPath($path, $this->currentLangId)
          && !$this->aliasStorage->lookupByAlias($path, $this->currentLangId)
        ) {
          // Instead of original node source path, get node alias.
          $path = $this->aliasManager->getAliasByPath($path, $this->defaultLangId);
        }
      }
    }

    return $path;
  }

  /*
  public function processOutbound($path, &$options = array(), Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    $path = parent::processOutbound($path, $options, $request, $bubbleable_metadata);

    if (empty($options['alias'])) {
      // Alias doesn't exist for current language and source too.
      if (!$this->aliasStorage->lookupBySystemPath($path, $this->currentLangId)
        && !$this->aliasStorage->lookupByAlias($path, $this->currentLangId)
      ) {
        // Instead of original node source path, get node alias.
        $path = $this->aliasManager->getAliasByPath($path, $this->defaultLangId);
      }
    }

    return $path;
  }
  */

}
