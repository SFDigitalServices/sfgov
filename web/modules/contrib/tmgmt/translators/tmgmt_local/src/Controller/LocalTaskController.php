<?php

namespace Drupal\tmgmt_local\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\tmgmt_local\LocalTaskInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Returns responses for Local task routes.
 */
class LocalTaskController extends ControllerBase {

  /**
   * Assign this task to the current user and reloads the listing page.
   *
   * @param \Drupal\tmgmt_local\LocalTaskInterface $tmgmt_local_task
   *   The task being acted upon.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Either returns a rebuilt listing page as an AJAX response, or redirects
   *   back to the listing page.
   */
  public function assignToMe(LocalTaskInterface $tmgmt_local_task, Request $request) {
    $tmgmt_local_task->assign(\Drupal::currentUser());
    $tmgmt_local_task->save();

    $this->messenger()->addStatus(t('The task has been assigned to you.'));

    // If the request is via AJAX, return the rendered list as JSON.
    if ($request->request->get('js')) {
      $list = $this->entityTypeManager()->getListBuilder('view')->render();
      $response = new AjaxResponse();
      $response->addCommand(new ReplaceCommand('#views-entity-list', $list));
      return $response;
    }

    // Otherwise, redirect back to the page.
    return $this->redirect('<current>');
  }

}
