<?php

namespace Drupal\sfgov_user\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for sfgov user routes.
 */
class PageController extends ControllerBase {

  /**
   * The user login "start" page.
   *
   * @return array
   *   The render array.
   */
  public function startPage() {
    return [
      '#theme' => 'sfgov_user_start_page',
    ];
  }

  /**
   * Redirects users to their profile page or to the login "start" page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the profile of the currently logged in user.
   */
  public function userPage() {
    if ($this->currentUser()->isAuthenticated()) {
      return $this->redirect('entity.user.canonical', ['user' => $this->currentUser()->id()]);
    }
    else {
      return $this->redirect('sfgov_user.start_page');
    }
  }

}
