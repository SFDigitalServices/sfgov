<?php

namespace Drupal\sfgov_event_subscriber\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\node\NodeInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Url;
use Drupal\redirect\Entity\Redirect;

/**
 * Event Subscriber RedirectEventSubscriber.
 */
class RedirectEventSubscriber implements EventSubscriberInterface {

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
    $account = \Drupal::currentUser();
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

    // Get the requested path alias.
    $request = $event->getRequest();
    $requested_path_alias = $request->getPathInfo();

    // Get original alias for translated nodes.
    $current_language = \Drupal::languageManager()
      ->getCurrentLanguage()
      ->getId();
    $lang_prefix = '/' . $current_language . '/';
    if (str_starts_with($requested_path_alias, $lang_prefix)) {
      $test_path = substr($requested_path_alias, 3);
      $english_alias_array = \Drupal::service('path_alias.repository')
        ->lookupBySystemPath($test_path, 'en');
      $english_alias = $english_alias_array['alias'];
    }

    // Set the alias to check against redirect repository.
    $alias_to_look_up = isset($english_alias) ? $english_alias : $requested_path_alias;

    // Check to see if a redirect matches the alias.
    $redirect = \Drupal::service('redirect.repository')
      ->findMatchingRedirect($alias_to_look_up);

    // If the redirect exists, keep going.
    if ($redirect instanceof Redirect) {

      // Get the redirect uri.
      $redirect_value = $redirect->redirect_redirect->getValue();
      $redirect_uri = $redirect_value[0]['uri'];

      // Set some helper strings.
      $internal_prefix = 'internal:';
      $node_prefix = '/node/';
      $path_prefix = $internal_prefix . $node_prefix;

      // If this isn't a translated node path,
      // we need to find what it is the alias to.
      if (!str_starts_with($redirect_uri, $path_prefix) && $current_language != 'en') {
        $clean_alias = str_replace($internal_prefix, '', $redirect_uri);

        $destination_array = \Drupal::service('path_alias.repository')
          ->lookupByAlias($clean_alias, 'en');

        // Now that we have the alias destination, get the destination nid.
        if ($destination_array) {
          $destination_nid = str_replace($node_prefix, '', $destination_array['path']);
          $redirect_url = '/' . $current_language . $node_prefix . $destination_nid;
        }
      }

      // If this isn't a translated node path, we can get the url from uri.
      else {
        $redirect_url = Url::fromUri($redirect_uri)->toString();
      }
    }
    return isset($redirect_url) ? $redirect_url : NULL;
  }

}
