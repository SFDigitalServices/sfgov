<?php

namespace Drupal\paragraphs\Plugin\Field\FieldWidget;

/**
 * Override t()
 */
function t($string, array $args = [], array $options = []) {
  return $string;
}

namespace Drupal\Tests\sfgov_admin\Unit\Plugin\Field\FieldWidget;

use Drupal\sfgov_admin\Plugin\Field\FieldWidget\SfgovParagraphsWidget;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\sfgov_admin\Plugin\Field\FieldWidget\SfgovParagraphsWidget
 * @group afgov_admin
 */
class SfgovParagraphsWidgetTest extends UnitTestCase {

  /**
   * @var \Drupal\sfgov_admin\Plugin\Field\FieldWidget\SfgovParagraphsWidget
   */
  protected $widget;


  public function setUp() {
    parent::setUp();

    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
    $field_definition = $this
      ->getMockBuilder('\Drupal\Core\Field\FieldDefinitionInterface')
      ->getMock();

    // Mock the string translation service.
    $string_translation = $this
      ->getMockBuilder('\Drupal\Core\StringTranslation\TranslationManager')
      ->disableOriginalConstructor()
      ->setMethods(['translate'])
      ->getMock();
    $string_translation
      ->expects($this->any())
      ->method('translate')
      // Always return the source value.
      ->willReturn($this->callback(function ($val) { return $val; }));

    // Mock the container.
    $container = $this
      ->getMockBuilder('\Symfony\Component\DependencyInjection\Container')
      ->setMethods(['get'])
      ->getMock();
    $container
      ->expects($this->any())
      ->method('get')
      ->with('string_translation')
      ->willReturn($string_translation);

    \Drupal::setContainer($container);

    $this->widget = new SfgovParagraphsWidget('sfgov_paragraphs', [], $field_definition, [], []);
  }

  /**
   * Test getting settings options.
   *
   * @test
   *
   * @throws \ReflectionException
   */
  public function getSettingOptions() {
    $options = self::callMethod($this->widget, 'getSettingOptions', ['add_mode']);

    self::assertArraySubset([
      'dropdown_custom' => 'Add drop button',
    ],  array_map('strval', $options));
  }

  /**
   * @param $obj
   * @param $name
   * @param array $args
   *
   * @return mixed
   * @throws \ReflectionException
   */
  public static function callMethod($obj, $name, array $args) {
    $class = new \ReflectionClass($obj);
    $method = $class->getMethod($name);
    $method->setAccessible(true);
    return $method->invokeArgs($obj, $args);
  }
}
