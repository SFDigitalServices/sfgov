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
    // only redirect if in view mode. This relies on proper URL path setting for all nodes.  
    $uri_array = explode('/',  $event->getRequest()->getRequestUri() ); 
    if($uri_array[1] == 'node' && ( $uri_array[2] && is_numeric($uri_array[2]) ) && $uri_array[3] != '' ){
        // do nothing
        //\Drupal::logger('sfgov_event_subscriber')->notice('edit mode');
    }
    else {
        $node = $event->getRequest()->attributes->get('node'); 
      
        if($node && $node->hasField('field_direct_external_url') ){
          $field_external_url = $node->get('field_direct_external_url')->getValue();

          if( !empty($field_external_url[0]) && $field_external_url[0]['uri'] != ''){
              // This is where you set the destination.
              $response = new TrustedRedirectResponse($field_external_url[0]['uri']);
              $event->setResponse($response);
          }
        }
      }
  }

}

