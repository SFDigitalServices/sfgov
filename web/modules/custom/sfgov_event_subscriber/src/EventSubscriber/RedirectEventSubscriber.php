<?php

namespace Drupal\sfgov_event_subscriber\EventSubscriber;

use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\path_alias\AliasRepository;
use Drupal\redirect\RedirectRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\node\NodeInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Url;
use Drupal\redirect\Entity\Redirect;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Event Subscriber RedirectEventSubscriber.
 */
class RedirectEventSubscriber implements EventSubscriberInterface {

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The Language Manager service.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * The alias repository.
   *
   * @var \Drupal\path_alias\AliasRepository
   */
  protected $aliasRepository;

  /**
   * The redirect repository.
   *
   * @var \Drupal\redirect\RedirectRepository
   */
  protected $redirectRepository;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   *   The Language manager.
   * @param \Drupal\path_alias\AliasRepository $aliasRepository
   *   Object to search aliases.
   * @param \Drupal\redirect\RedirectRepository $redirectRepository
   *   Object to search redirect.
   */
  public function __construct(AccountInterface $account, LanguageManager $languageManager, AliasRepository $aliasRepository, RedirectRepository $redirectRepository) {
    $this->account = $account;
    $this->languageManager = $languageManager;
    $this->aliasRepository = $aliasRepository;
    $this->redirectRepository = $redirectRepository;
  }

  /**
   * Create function.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('language_manager'),
      $container->get('path_alias.repository'),
      $container->get('redirect.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['sfgovRedirect', 0];
    return $events;
  }

  /**
   * Method to manage custom redirects.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event object.
   */
  public function sfgovRedirect(GetResponseEvent $event) {
    // Don't redirect authenticated users.
    $account = $this->account;
    if (!in_array('anonymous', $account->getRoles())) {
      return;
    }

    $redirectBasedOnField = $this->redirectBasedOnField($event);
    $redirectBasedOnAlias = $this->redirectBasedOnAlias($event);

    if ($redirectBasedOnField) {
      $redirect_url = $redirectBasedOnField;
    }
    elseif ($redirectBasedOnAlias) {
      $redirect_url = $redirectBasedOnAlias;
    }
    else {
      return;
    }

    // Reconstruct response.
    if (!empty($redirect_url)) {
      $response_headers = [
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
      ];
      $response = new TrustedRedirectResponse($redirect_url, '302', $response_headers);
      $cached_response = $this->setRedirectCacheableDependency($response);

      // Disable page cache for anonymous requests.
      \Drupal::service('page_cache_kill_switch')->trigger();
      $event->setResponse($cached_response);
    }
  }

  /**
   * Add cache context to make sure the request isn't cached for auth users.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event object.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The node object or null.
   */
  public function addCacheContextsToNode(GetResponseEvent $event) {
    $node = $event->getRequest()->attributes->get('node');
    if ($node && $node instanceof NodeInterface) {
      $route_name = \Drupal::routeMatch()->getRouteName();

      if (!$node->isPublished() || $route_name === 'public_preview.preview_link') {
        return NULL;
      }
      else {
        $node->addCacheContexts(['user.roles:anonymous']);
      }
    }
    return $node ? $node : NULL;
  }

  /**
   * Inject cache context into response.
   *
   * @param \Symfony\Component\HttpFoundation\RedirectResponse $response
   *   The original response object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The response object with cacheable dependency.
   */
  public function setRedirectCacheableDependency(RedirectResponse $response) {
    if (!empty($cacheableDependency)) {
      $response->addCacheableDependency($cacheableDependency);
    }
    return $response;
  }

  /**
   * Follow redirects from aliases.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event object.
   *
   * @return string|null
   *   String to redirect to.
   */
  public function redirectBasedOnField(GetResponseEvent $event) {

    $media = $event->getRequest()->attributes->get('media');

    if ($this->addCacheContextsToNode($event) != NULL) {

      $node = $this->addCacheContextsToNode($event);
      $node_type = strtolower($node->type->entity->label());

      // Node redirect rule based on the field `field_direct_external_url`.
      if ($node->hasField('field_direct_external_url')) {
        $field_external_url = $node->get('field_direct_external_url')
          ->getValue();

        if (!empty($field_external_url[0]) && $field_external_url[0]['uri'] != '') {
          $redirect_url = $field_external_url[0]['uri'];
        }
      }

      // Node redirect rule based on the field `field_go_to_current_url`.
      if ($node_type == 'department' && $node->hasField('field_go_to_current_url')) {
        $field_go_to_current_url = $node->get('field_go_to_current_url')
          ->getValue();

        if (!empty($field_go_to_current_url[0]) && $field_go_to_current_url[0]['value'] == '1') {
          $field_dept_url = $node->get('field_url')->getValue();

          if (!empty($field_dept_url[0]) && $field_dept_url[0]['uri'] != '') {
            $redirect_url = $field_dept_url[0]['uri'];
          }
        }
      }
    }
    elseif ($media) {
      // Media field redirect rule.
      if ($media->hasField('field_document_url') || $media->hasField('field_media_file')) {
        $field_file = $media->get('field_media_file')->getValue();
        $field_doc_url = $media->get('field_document_url')->getValue();
        if (!empty($field_file)) {
          $file_id = $field_file[0]['target_id'];
          $file_url = File::load($file_id)->url();
          $redirect_url = $file_url;
        }
        elseif (!empty($field_doc_url)) {
          $redirect_url = $field_doc_url[0]['uri'];
        }
      }
    }
    return isset($redirect_url) ? $redirect_url : NULL;
  }

  /**
   * Follow redirects from aliases.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event object.
   *
   * @return string|null
   *   String to redirect to.
   */
  public function redirectBasedOnAlias(GetResponseEvent $event) {

    $node = $event->getRequest()->attributes->get('node');
    $current_language = $this->languageManager
      ->getCurrentLanguage()
      ->getId();

    if ($node instanceof NodeInterface) {
      $nid = $node->id();
      $path = '/node/' . $nid;
      $english_alias_array = $this->aliasRepository
        ->lookupBySystemPath($path, 'en');
      $english_alias = $english_alias_array['alias'];

      // Look up redirect by English alias.
      $redirect = $this->redirectRepository->findMatchingRedirect($english_alias);

      // If the redirect exists, keep going.
      if ($redirect instanceof Redirect) {

        // Get the redirect uri.
        $redirect_value = $redirect->redirect_redirect->getValue();
        $redirect_uri = $redirect_value[0]['uri'];

        // Set some helper strings.
        $node_prefix = '/node/';
        $internal_prefix = 'internal:';
        $path_prefix = $internal_prefix . $node_prefix;
        $entity_prefix = 'entity:';
        $entity_node_prefix = 'entity:node';

        // If this isn't a translated node path,
        // we need to find what it is the alias to.
        if (!str_starts_with($redirect_uri, $path_prefix) && $current_language != 'en') {
          $clean_alias = str_replace($internal_prefix, '', $redirect_uri);

          $destination_array = $this->aliasRepository
            ->lookupByAlias($clean_alias, 'en');

          // Now that we have the alias destination, get the destination nid.
          $destination = '';
          if ($destination_array) {
            $destination = $destination_array['path'];
          }
          elseif (str_starts_with($redirect_uri, $entity_node_prefix)) {
            $clean_alias = str_replace($entity_prefix, '', $redirect_uri);
            $destination = '/' . $clean_alias;
          }

          $redirect_url = '/' . $current_language . $destination;
        }

        // If this isn't a translated node path, we can get the url from uri.
        else {
          $redirect_url = Url::fromUri($redirect_uri)->toString();
        }
      }
    }

    return isset($redirect_url) ? $redirect_url : NULL;
  }

}
