<?php

/**
 * @file
 * Contains \Drupal\sfgov_event_subscriber\EventSubscriber\RedirectEventSubscriber.
 */

namespace Drupal\sfgov_event_subscriber\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\node\NodeInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Url;

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

  public function sfgovRedirect(GetResponseEvent $event) {
    // Don't redirect authenticated users.
    $account = \Drupal::currentUser();
    if (!in_array('anonymous', $account->getRoles())) {
      return;
    }

    $redirect_url = NULL;
    $cacheableDependency = NULL;

    $redirectBasedOnField = $this->redirectBasedOnField($event);
    $redirectBasedOnAlias = $this->redirectBasedOnAlias($event);

    if ($redirectBasedOnField) {
      $redirect_url = $redirectBasedOnField;
    } elseif($redirectBasedOnAlias) {
      $redirect_url = $redirectBasedOnAlias;
    }

    if(!empty($redirect_url)) {
      $response_headers = [
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
      ];
      $response = new RedirectResponse($redirect_url, '302', $response_headers);
      if(!empty($cacheableDependency)) {
        $response->addCacheableDependency($cacheableDependency);
      }
      \Drupal::service('page_cache_kill_switch')->trigger(); // disable page cache for anonymous requests
      $event->setResponse($response);
    }
  }

  public function redirectBasedOnField(GetResponseEvent $event) {
    $redirect_url = NULL;

    $node = $event->getRequest()->attributes->get('node');
    $media = $event->getRequest()->attributes->get('media');

    if ($node && $node instanceof NodeInterface) {
      $route_name = \Drupal::routeMatch()->getRouteName();

      if (!$node->isPublished() || $route_name === 'public_preview.preview_link') {
        return;
      }
      else {
        // Add cache context to make sure the request won't be cached for authenticated users.
        $node->addCacheContexts(['user.roles:anonymous']);

        // Get node type.
        $node_type = strtolower($node->type->entity->label());

        // Redirect rule based on the field `field_direct_external_url`.
        if ($node->hasField('field_direct_external_url') ){
          $field_external_url = $node->get('field_direct_external_url')->getValue();

          if (!empty($field_external_url[0]) && $field_external_url[0]['uri'] != ''){
            // This is where you set the destination.
            $redirect_url = $field_external_url[0]['uri'];
            $cacheableDependency = $node;
          }
        }

        if ($node_type == 'department' && $node->hasField('field_go_to_current_url')) {
          $redirect_url = NULL;
          $cacheableDependency = NULL;
          $field_go_to_current_url = $node->get('field_go_to_current_url')->getValue();

          if (!empty($field_go_to_current_url[0]) && $field_go_to_current_url[0]['value'] == '1') {
            $field_dept_url = $node->get('field_url')->getValue();

            if (!empty($field_dept_url[0]) && $field_dept_url[0]['uri'] != '') {
              $redirect_url = $field_dept_url[0]['uri'];
              $cacheableDependency = $node;
            }
          }
        }
      }
    }
    else if($media) {
      if($media->hasField('field_document_url') || $media->hasField('field_media_file')) {
        $field_file = $media->get('field_media_file')->getValue();
        $field_doc_url = $media->get('field_document_url')->getValue();
        if(!empty($field_file)) {
          $file_id = $field_file[0]['target_id'];
          $file_url = File::load($file_id)->url();
          $redirect_url = $file_url;
        }
        else if(!empty($field_doc_url)) {
          $redirect_url = $field_doc_url[0]['uri'];
        }
      }
    }

    return $redirect_url;
  }

  public function redirectBasedOnAlias(GetResponseEvent $event) {

    $redirect_url = NULL;

    // Get the requested path alias.
    $request = $event->getRequest();
    $current_path_alias = $request->getPathInfo();

    // Check to see if a redirect matches the alias.
    $redirect = \Drupal::service('redirect.repository')->findMatchingRedirect($current_path_alias);

    // If the redirect exists, set the url to the destination.
    if ($redirect) {
      $redirect_value = $redirect->redirect_redirect->getValue();
      $redirect_uri = $redirect_value[0]['uri'];
      $redirect_url = Url::fromUri($redirect_uri)->toString();
    }

    return $redirect_url;
  }
}

