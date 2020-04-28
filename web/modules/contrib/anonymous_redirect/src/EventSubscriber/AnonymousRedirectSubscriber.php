<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 20/2/2016
 * Time: 1:22 PM
 */

namespace Drupal\anonymous_redirect\EventSubscriber;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AnonymousRedirectSubscriber extends ControllerBase implements EventSubscriberInterface {


  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    $events[KernelEvents::REQUEST][] = ['redirectAnonymous', 100];
    return $events;
  }




  /**
   * Redirects anonymous users to the /user route
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function redirectAnonymous(GetResponseEvent $event) {

    $config = $this->config('anonymous_redirect.settings');

    $redirectEnabled = $config->get('enable_redirect');
    $redirectUrl = $config->get('redirect_url');
    $redirectUrlOverridesText = $config->get('redirect_url_overrides');
    $redirectUrlOverrides = $redirectUrlOverridesText ? explode("\r\n", $redirectUrlOverridesText) : array();
    $currentPath = $event->getRequest()->getPathInfo();
    $currentUser = \Drupal::currentUser();
    /** @var \Drupal\Core\Path\PathMatcher $pathMatcher */
    $pathMatcher = \Drupal::service('path.matcher');


    // Do nothing if redirect_url is not enabled or if the user is authenticated.
    if (!$redirectEnabled || $currentUser->isAuthenticated()) {
      return;
    }

    // Do nothing if the url is in the list of overrides
    if (in_array($currentPath, $redirectUrlOverrides) || $pathMatcher->matchPath($currentPath, $redirectUrlOverridesText)) {
      return;
    }

    // External URL must use TrustedRedirectResponse class.
    if (UrlHelper::isExternal($redirectUrl)) {
      $event->setResponse(new TrustedRedirectResponse($redirectUrl));
      return;
    }


    // Redirect the user to the front page
    if($this->isFrontPage($redirectUrl) && $currentPath !== Url::fromRoute("<front>")->toString()){
      $event->setResponse(new RedirectResponse(Url::fromRoute("<front>")->toString()));
    }


    // redirect the user the configured route
    if ($this->isFrontPage($redirectUrl) == false && strpos($currentPath, $redirectUrl) === FALSE) {
      $event->setResponse(new RedirectResponse($redirectUrl));
    }


  }



  /**
   * Returns true if the entered string matches the route for the configured front page
   *
   * @param $urlString
   *
   * @return bool
   */
  public function isFrontPage($urlString){

    if($urlString == "<front>"){
      return true;
    }

    return false;
  }
}
