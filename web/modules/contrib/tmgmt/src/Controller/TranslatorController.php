<?php

namespace Drupal\tmgmt\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\CssSelector\XPath\TranslatorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Route controller class for the tmgmt translator entity.
 */
class TranslatorController extends ControllerBase {

  /**
   * Enables a Translator object.
   *
   * @param \Drupal\tmgmt\TranslatorInterface $tmgmt_translator
   *   The Translator object to enable.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the tmgmt listing page.
   */
  function enable(TranslatorInterface $tmgmt_translator) {
    $tmgmt_translator->enable()->save();
    return new RedirectResponse(url('admin/tmgmt/translators', array('absolute' => TRUE)));
  }

  /**
   * Disables a Translator object.
   *
   * @param \Drupal\tmgmt\TranslatorInterface $tmgmt_translator
   *   The Translator object to disable.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the tmgmt listing page.
   */
  function disable(TranslatorInterface $tmgmt_translator) {
    $tmgmt_translator->disable()->save();
    return new RedirectResponse(url('admin/tmgmt/translators', array('absolute' => TRUE)));
  }

}
