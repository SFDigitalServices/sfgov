<?php

/**
 * @file
 * Contains \Drupal\mandrill_template\MandrillTemplateService.
 */

namespace Drupal\mandrill_template;

use Drupal\mandrill\MandrillService;

/**
 * Mandrill Template service.
 *
 * Overrides Mandrill Service to allow sending of templated messages.
 */
class MandrillTemplateService extends MandrillService {

  /**
   * {@inheritdoc}
   */
  public function send($message) {
    $template_map = null;

    if (isset($message['id']) && isset($message['module'])) {
      // Check for a template map to use with this message.
      $template_map = mandrill_template_map_load_by_mailsystem($message['id'], $message['module']);
    }

    try {
      if (!empty($template_map)) {
        // Send the message with template information.
        $template_content = array(
          array(
            'name' => $template_map->main_section,
            'content' => $message['html'],
          ),
        );

        if (isset($message['mandrill_template_content'])) {
          $template_content = array_merge($message['mandrill_template_content'], $template_content);
        }

        $response = $this->mandrill_api->sendTemplate($message, $template_map->template_id, $template_content);
      }
      else {
        // No template map, so send a standard message.
        $response = $this->mandrill_api->send($message);
      }
    }
    catch (\Exception $e) {
      $this->log->error('Error sending email from %from to %to. @code: @message', array(
        '%from' => $message['from_email'],
        '%to' => $message['to'],
        '@code' => $e->getCode(),
        '@message' => $e->getMessage(),
      ));
      return FALSE;
    }

    if (!empty($response)) {
      return $this->handleSendResponse($response, $message);
    }
    else {
      return FALSE;
    }
  }

}
