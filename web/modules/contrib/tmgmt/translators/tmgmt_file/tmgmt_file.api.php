<?php

/*
 * @file
 * API and hook documentation for the File Translator module.
 */

/**
 * Alter file format plugins provided by other modules.
 */
function hook_tmgmt_file_format_plugin_info_alter(&$file_formats) {
  // Switch the used HTML plugin controller class.
  $file_formats['html']['class'] = '\Drupal\mymodule\DifferentHtmlImplementation';
}

/**
 * Provide information about available text processors.
 *
 * @return array
 *   An array of available text processor definitions. The key is the text
 *   processor name.
 */
function hook_tmgmt_file_text_processor_plugin_info() {
  return array(
    'mask_html_for_xliff' => array(
      'label' => t('Escape HTML'),
      'processor class' => 'TMGMTFileXLIFFMaskHTMLProcessor',
    ),
  );
}
