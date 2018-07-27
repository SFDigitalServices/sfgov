<?php

/**
 * @file
 * Contains \Drupal\sfgov_event_subscriber\EventSubscriber\RedirectEventSubscriber.
 */

namespace Drupal\sfgov_event_subscriber\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
//use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
//use Drupal\node\Entity\Node;
use Drupal\Core\Routing\TrustedRedirectResponse;

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

