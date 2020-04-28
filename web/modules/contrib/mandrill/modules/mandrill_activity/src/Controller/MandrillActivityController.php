<?php

/**
 * @file
 * Contains \Drupal\mandrill_activity\Controller\MandrillActivityController.
 */

namespace Drupal\mandrill_activity\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;

/**
 * MandrillActivity controller.
 */
class MandrillActivityController extends ControllerBase {

  /**
   * View Mandrill activity for a given user.
   *
   * @param User $user
   *   The User to view activity for.
   *
   * @return array
   *   Renderable array of page content.
   */
  public function overview(User $user) {
    $content = array();

    /* @var $api \Drupal\mandrill\MandrillAPI */
    $api = \Drupal::service('mandrill.api');
    $activity = $api->getMessages($user->getEmail());

    $content['activity'] = array(
      '#markup' => t('The 100 most recent Emails sent to %email via Mandrill.', array('%email' => $email)),
    );

    $content['activity_table'] = array(
      '#type' => 'table',
      '#header' => array(t('Subject'), t('Timestamp'), t('State'), t('Opens'), t('Clicks'), t('Tags')),
      '#empty' => 'No activity yet.',
    );

    foreach ($activity as $index => $message) {
      $content['activity_table'][$index]['subject'] = array(
        '#markup' => $message['subject'],
      );

      $content['activity_table'][$index]['timestamp'] = array(
        '#markup' => format_date($message['ts'], 'short'),
      );

      $content['activity_table'][$index]['state'] = array(
        '#markup' => $message['state'],
      );

      $content['activity_table'][$index]['opens'] = array(
        '#markup' => $message['opens'],
      );

      $content['activity_table'][$index]['clicks'] = array(
        '#markup' => $message['clicks'],
      );

      $content['activity_table'][$index]['tags'] = array(
        '#markup' => implode(', ', $message['tags']),
      );
    }

    return $content;
  }

}
