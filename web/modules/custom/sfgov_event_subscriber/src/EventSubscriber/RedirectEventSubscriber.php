<?php

/**
 * @file
 * Contains \Drupal\sfgov_event_subscriber\EventSubscriber\RedirectEventSubscriber.
 */

namespace Drupal\sfgov_event_subscriber\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;

/**
 * Event Subscriber RedirectEventSubscriber.
 */
class RedirectEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Drupal logger not working,  need to as CH3

    $events[KernelEvents::REQUEST][] = ['redirectBasedOnField'];
    return $events;
  }

  public function redirectBasedOnField(GetResponseEvent $event) {
    // Don't redirect authenticated users.
    $account = \Drupal::currentUser();
    if (!in_array('anonymous', $account->getRoles())) {
      return;
    }

    // Get node object.
    $node = $event->getRequest()->attributes->get('node');

    // If node is published.
    if ($node && $node->isPublished()) {
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
          $response = new TrustedRedirectResponse($redirect_url);
          $response->addCacheableDependency($node);
          $event->setResponse($response);
        }
      }

      if ($node_type == 'department' && $node->hasField('field_go_to_current_url')) {
        $field_go_to_current_url = $node->get('field_go_to_current_url')->getValue();

        if (!empty($field_go_to_current_url[0]) && $field_go_to_current_url[0]['value'] == '1') {
          $field_dept_url = $node->get('field_url')->getValue();

          if (!empty($field_dept_url[0]) && $field_dept_url[0]['uri'] != '') {
            $redirect_url = $field_dept_url[0]['uri'];
            $response = new TrustedRedirectResponse($redirect_url);
            $response->addCacheableDependency($node);
            $event->setResponse($response);
          }
        }
      }
    }

  }
}

