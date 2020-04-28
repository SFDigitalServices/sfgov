<?php

namespace Drupal\Tests\tmgmt_config\Kernel;

use Drupal\Tests\tmgmt\Kernel\TMGMTKernelTestBase;

/**
 * Unit tests for exporting translatable data from config entities and saving it back.
 *
 * @group tmgmt
 *
 * @requires module webform
 */
class ConfigSourceWebformTest extends TMGMTKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('tmgmt_config', 'language', 'config_translation', 'locale', 'options', 'webform');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Add the languages.
    $this->installConfig(['language']);


    $this->installEntitySchema('tmgmt_job');
    $this->installEntitySchema('tmgmt_job_item');
    $this->installEntitySchema('tmgmt_message');
    $this->installSchema('system', array('router'));
    $this->installSchema('locale', array('locales_location', 'locales_source', 'locales_target'));

    \Drupal::service('router.builder')->rebuild();


    // Install webform default configuration.
    $this->installSchema('webform', ['webform']);
    $this->installConfig(['webform']);

    tmgmt_translator_auto_create(\Drupal::service('plugin.manager.tmgmt.translator')->getDefinition('test_translator'));
  }

  /**
   * Tests the webform config entity.
   */
  public function testWebForm() {

    $webform_storage  = \Drupal::entityTypeManager()->getStorage('webform');
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $webform_storage->load('contact');

    // Add a select field to the form.
    $webform->setElementProperties('select_test', [
      '#type' => 'select',
      '#title' => 'Test',
      '#options' => [
        'Value1' => 'Text1',
        'Value2' => 'Text2',
      ],
    ]);

    // Add a placeholder to the name field.
    $name = $webform->getElement('name');
    $name['#placeholder'] = 'The placeholder';
    $webform->setElementProperties('name', $name);
    $webform->save();

    $job = tmgmt_job_create('en', 'de');
    $job->translator = 'test_translator';
    $job->save();

    // @todo This relies on the webform default configuration, use a custom
    //   form to have a more predictable test?
    $job_item = tmgmt_job_item_create('config', 'webform', 'webform.webform.contact', array('tjid' => $job->id()));
    $job_item->save();

    $source_plugin = $this->container->get('plugin.manager.tmgmt.source')->createInstance('config');
    $data = $source_plugin->getData($job_item);

    // Assert the data.
    $this->assertEquals('Title', $data['title']['#label']);
    $this->assertEquals('Contact', $data['title']['#text']);
    $this->assertTrue($data['title']['#translate']);

    $this->assertEquals('#title', $data['elements']['name']['pound_title']['#label']);
    $this->assertEquals('#placeholder', $data['elements']['name']['pound_placeholder']['#label']);
    $this->assertEquals('Your Name', $data['elements']['name']['pound_title']['#text']);
    $this->assertTrue($data['elements']['name']['pound_title']['#translate']);

    $this->assertEquals('The placeholder', $data['elements']['name']['pound_placeholder']['#text']);
    $this->assertTrue($data['elements']['name']['pound_title']['#translate']);

    $this->assertEquals('#title', $data['elements']['email']['pound_title']['#label']);
    $this->assertEquals('Your Email', $data['elements']['email']['pound_title']['#text']);
    $this->assertTrue($data['elements']['email']['pound_title']['#translate']);

    $this->assertEquals('[current-user:mail]', $data['elements']['email']['pound_default_value']['#text']);
    $this->assertFalse($data['elements']['email']['pound_default_value']['#translate']);

    $this->assertEquals('#test', $data['elements']['subject']['pound_test']['#label']);
    $this->assertEquals('Testing contact webform from [site:name]', $data['elements']['subject']['pound_test']['#text']);

    $this->assertEquals('Value1', $data['elements']['select_test']['pound_options']['Value1']['#label']);
    $this->assertEquals('Text1', $data['elements']['select_test']['pound_options']['Value1']['#text']);
    $this->assertEquals('Value2', $data['elements']['select_test']['pound_options']['Value2']['#label']);
    $this->assertEquals('Text2', $data['elements']['select_test']['pound_options']['Value2']['#text']);

    $this->assertEquals('Send message', $data['elements']['actions']['pound_submit__label']['#text']);

    $this->assertEquals('[webform_submission:values:name:raw]', $data['handlers']['email_notification']['settings']['from_name']['#text']);
    $this->assertFalse($data['handlers']['email_notification']['settings']['from_name']['#translate']);

    // Now request a translation and save it back.
    $job->requestTranslation();
    $items = $job->getItems();
    $item = reset($items);
    $item->acceptTranslation();

    // Check that the translations were saved correctly.
    $language_manager = \Drupal::languageManager();
    $language_manager->setConfigOverrideLanguage($language_manager->getLanguage('de'));

    $webform_storage  = \Drupal::entityTypeManager()->getStorage('webform');
    $webform_storage->resetCache();
    $entities = $webform_storage->loadMultiple(['contact']);
    $webform = reset($entities);

    $this->assertEquals('de(de-ch): Contact', $webform->label());

    $name = $webform->getElement('name');
    $this->assertEquals('de(de-ch): Your Name', $name['#title']);
    $this->assertEquals('de(de-ch): The placeholder', $name['#placeholder']);
    $select_test = $webform->getElement('select_test');
    $this->assertEquals('de(de-ch): Text1', $select_test['#options']['Value1']);
    $this->assertEquals('de(de-ch): Text2', $select_test['#options']['Value2']);
    $actions = $webform->getElement('actions');
    $this->assertEquals('de(de-ch): Submit button(s)', $actions['#title']);
    $this->assertEquals('de(de-ch): Send message', $actions['#submit__label']);
  }
}
