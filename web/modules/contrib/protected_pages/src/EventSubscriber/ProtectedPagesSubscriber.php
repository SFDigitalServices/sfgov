<?php

namespace Drupal\protected_pages\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Url;

/**
 * Redirects user to protected page login screen.
 */
class ProtectedPagesSubscriber implements EventSubscriberInterface {

  /**
   * Redirects user to protected page login screen.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function checkProtectedPage(FilterResponseEvent $event) {
    $account = \Drupal::currentUser();
    if ($account->hasPermission('bypass pages password protection')) {
      return;
    }
    $current_path = \Drupal::service('path.alias_manager')
      ->getAliasByPath(\Drupal::service('path.current')->getPath());
    $normal_path = Unicode::strtolower(\Drupal::service('path.alias_manager')
      ->getPathByAlias($current_path));
    $pid = $this->protectedPagesIsPageLocked($current_path, $normal_path);
    $this->sendAccessDenied($pid);

    if (empty($pid)) {
      $page_node = \Drupal::request()->attributes->get('node');
      if (is_object($page_node)) {
        $nid = $page_node->id();
        if (isset($nid) && is_numeric($nid)) {
          $path_to_node = '/node/' . $nid;
          $current_path = Unicode::strtolower(\Drupal::service('path.alias_manager')
            ->getAliasByPath($path_to_node));
          $normal_path = Unicode::strtolower(\Drupal::service('path.alias_manager')
            ->getPathByAlias($current_path));
          $pid = $this->protectedPagesIsPageLocked($current_path, $normal_path);
          $this->sendAccessDenied($pid);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('checkProtectedPage');
    return $events;
  }

  /**
   * Send Access Denied for pid.
   *
   * @param int $pid
   *   The Protected Page ID.
   */
  public function sendAccessDenied($pid) {
    if (empty($pid)) {
      return;
    }

    $query = \Drupal::destination()->getAsArray();
    $query['protected_page'] = $pid;
    \Drupal::service('page_cache_kill_switch')->trigger();
    $response = new RedirectResponse(Url::fromUri('internal:/protected-page', array('query' => $query))
      ->toString());
    $response->send();
  }

  /**
   * Returns protected page id.
   *
   * @param string $current_path
   *   Current path alias.
   * @param string $normal_path
   *   Current normal path.
   *
   * @return int
   *   The protected page id.
   */
  public function protectedPagesIsPageLocked($current_path, $normal_path) {
    $fields = array('pid');
    $conditions = array();
    $conditions['or'][] = array(
      'field' => 'path',
      'value' => $normal_path,
      'operator' => '=',
    );
    $conditions['or'][] = array(
      'field' => 'path',
      'value' => $current_path,
      'operator' => '=',
    );
    $protectedPagesStorage = \Drupal::service('protected_pages.storage');
    $pid = $protectedPagesStorage->loadProtectedPage($fields, $conditions, TRUE);

    if (isset($_SESSION['_protected_page']['passwords'][$pid]['expire_time'])) {
      if (time() >= $_SESSION['_protected_page']['passwords'][$pid]['expire_time']) {
        unset($_SESSION['_protected_page']['passwords'][$pid]['request_time']);
        unset($_SESSION['_protected_page']['passwords'][$pid]['expire_time']);
      }
    }
    if (isset($_SESSION['_protected_page']['passwords'][$pid]['request_time'])) {
      return FALSE;
    }
    return $pid;
  }

}
