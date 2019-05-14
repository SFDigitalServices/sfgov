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
    // don't redirect logged in users.
    $account = \Drupal::currentUser();
    if ( !empty($account->id()) ) {
      return;
    }

    $node = $event->getRequest()->attributes->get('node'); 
    if($node && $node->isPublished()) {
      $node_type = strtolower($node->type->entity->label());
      if($node_type == 'transaction' && $node->hasField('field_direct_external_url') ){
        $field_external_url = $node->get('field_direct_external_url')->getValue();
        if( !empty($field_external_url[0]) && $field_external_url[0]['uri'] != ''){
          // This is where you set the destination.
          $redirect_url = $field_external_url[0]['uri'];
          $response = new TrustedRedirectResponse($redirect_url);
          $event->setResponse($response);
        }
      }
      if($node_type == 'department' && $node->hasField('field_go_to_current_url')) {
        $field_go_to_current_url = $node->get('field_go_to_current_url')->getValue();
        if(!empty($field_go_to_current_url[0]) && $field_go_to_current_url[0]['value'] == '1') {
          $field_dept_url = $node->get('field_url')->getValue();
          if(!empty($field_dept_url[0]) && $field_dept_url[0]['uri'] != '') {
            $redirect_url = $field_dept_url[0]['uri'];
            $response = new TrustedRedirectResponse($redirect_url);
            $event->setResponse($response);
          }
        }
      }
    }

  }
}

