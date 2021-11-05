<?php

namespace Drupal\Tests\formdazzle\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Render\Markup;
use Drupal\formdazzle\Dazzler;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\formdazzle\Dazzler
 * @group formdazzle
 */
class DazzlerTest extends UnitTestCase {

  /**
   * The mocked element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $elementInfoManager;

  /**
   * Form element fixtures.
   *
   * @var array
   */
  protected $fixtures;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Mock services.
    $this->initElementInfoManager();
    $twig_service = $this->createMock('\Twig_Environment');
    $twig_service->method('isDebug')->willReturn(TRUE);

    // Mock \Drupal::service() calls.
    $container = new ContainerBuilder();
    $container->set('element_info', $this->elementInfoManager);
    $container->set('twig', $twig_service);
    \Drupal::setContainer($container);
  }

  /**
   * Initializes the mocked element info manager.
   */
  public function initElementInfoManager() {
    if (is_null($this->elementInfoManager)) {
      $this->elementInfoManager = $this->createMock('\Drupal\Core\Render\ElementInfoManagerInterface');
      $this->elementInfoManager
        ->method('getInfo')
        ->will(
          $this->returnValueMap([
            ['no_theme_defaults', ['#fixture' => TRUE]],
            [
              'mixed_defaults',
              [
                '#fixture' => TRUE,
                '#theme' => 'mixed_defaults',
              ],
            ],
            ['with_theme', ['#theme' => 'with_theme']],
            [
              'with_theme_and_wrappers',
              [
                '#theme' => 'with_theme',
                '#theme_wrappers' => ['with_theme_wrapper'],
              ],
            ],
            [
              'form',
              [
                '#theme_wrappers' => ['form'],
              ],
            ],
          ])
        );
    }
  }

  /**
   * Returns the specified form element fixture.
   *
   * @param string $name
   *   The name of the fixture to return.
   *
   * @return array
   *   The fixture.
   */
  protected function getFixture($name) {
    // Setting up fixtures as a class member variable can't be done during
    // Setup() because dataProvider functions are run before Setup().
    if (is_null($this->fixtures)) {
      // Form element fixtures.
      $this->fixtures = [
        'no_theme_defaults' => [
          '#type' => 'no_theme_defaults',
        ],
        'mixed_defaults' => [
          '#type' => 'mixed_defaults',
        ],
        'with_theme_and_wrappers' => [
          '#type' => 'with_theme_and_wrappers',
        ],
        'no_default_overrides' => [
          '#type' => 'with_theme_and_wrappers',
          '#theme' => 'no_default_overrides',
          '#theme_wrappers' => ['no_default_overrides'],
        ],
        'no_type' => [
          '#fixture' => TRUE,
        ],
        'with_theme' => [
          '#type' => 'with_theme',
        ],
      ];

      // Form fixtures.
      $form_fixtures = [
        'simple_form' => [],
        'node_article_edit_form' => [
          '#theme' => ['node_article_edit_form', 'node_form'],
        ],
        'with_child' => [
          'child' => [
            '#type' => 'with_theme_and_wrappers',
          ],
        ],
      ];
      $this->fixtures += $form_fixtures;
      foreach (array_keys($form_fixtures) as $form_id) {
        // All our form fixtures share this structure.
        $this->fixtures[$form_id] += [
          '#type' => 'form',
          '#form_id' => $form_id,
          '#theme' => [$form_id],
        ];
      }
    }

    return $this->fixtures[$name];
  }

  /**
   * Gets a standard message to use on test failures.
   *
   * @return string
   *   The test message to use.
   */
  public function getTestMessage() {
    return preg_replace_callback('/^test(.)([^ ]+)/', function ($matches) {
      return Dazzler::class . '::' . strtolower($matches[1]) . $matches[2] . '()';
    }, $this->getName());
  }

  /**
   * Gets a Twig debug comment given the list of templates.
   *
   * @param string[] $templates
   *   A list of template files.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   The Twig debug comment.
   */
  public function getTwigDebugComment(array $templates) {
    return Markup::create(PHP_EOL . PHP_EOL
      . '<!-- THEME DEBUG -->' . PHP_EOL
      . '<!-- THEME HOOK: No templates found. -->' . PHP_EOL
      . '<!-- FILE NAME SUGGESTIONS:' . PHP_EOL
      . '   * ' . implode(PHP_EOL . '   * ', $templates) . PHP_EOL
      . '-->'
    );
  }

  /**
   * @covers ::formAlter
   *
   * @dataProvider providerFormAlter
   */
  public function testFormAlter(array $form, string $form_id, array $expected) {
    Dazzler::formAlter($form, $form_id);
    $this->assertEquals($expected, $form, $this->getTestMessage());
  }

  /**
   * Data provider for testFormAlter().
   *
   * @see testFormAlter()
   */
  public function providerFormAlter() {
    $data = [];
    $class = 'Drupal\formdazzle\Dazzler';

    $actual = $this->getFixture('simple_form');
    $expected = $actual + [
      '#pre_render' => [[$class, 'preRenderForm']],
      '#formdazzle' => ['form_id' => 'a_form_id'],
    ];
    $data['adds a #pre_render array to the form'] = [
      $actual,
      'a_form_id',
      $expected,
    ];

    $actual = $this->getFixture('simple_form') + [
      '#pre_render' => ['some_pre_render'],
    ];
    $expected = $actual + [
      '#formdazzle' => ['form_id' => 'a_form_id'],
    ];
    $expected['#pre_render'] = [
      'some_pre_render',
      [$class, 'preRenderForm'],
    ];
    $data['appends to an existing #pre_render array in the form'] = [
      $actual,
      'a_form_id',
      $expected,
    ];

    return $data;
  }

  /**
   * @covers ::preRenderForm
   *
   * @dataProvider providerPreRenderForm
   */
  public function testPreRenderForm(array $form, array $expected) {
    $actual = Dazzler::preRenderForm($form);
    $this->assertEquals($expected, $actual, $this->getTestMessage());
  }

  /**
   * Data provider for testPreRenderForm().
   *
   * @see testPreRenderForm()
   */
  public function providerPreRenderForm() {
    $data = [];

    // Basic test.
    $form = [
      '#form_id' => 'a_form_id',
      '#theme' => ['a_form_id'],
    ] + $this->getFixture('with_child');
    Dazzler::formAlter($form, 'a_form_id');
    $expected = $form;
    $expected['#theme_wrappers'] = ['form__a_form_id'];
    $expected['child'] = [
      '#type' => 'with_theme_and_wrappers',
      '#theme' => 'with_theme__a_form_id',
      '#theme_wrappers' => ['with_theme_wrapper__a_form_id'],
    ];
    $expected['#markup'] = $this->getTwigDebugComment(['a-form-id.html.twig']);
    unset($expected['#formdazzle']);
    $data['adds suggestions to the entire form'] = [
      $form,
      $expected,
    ];

    // Node edit form.
    $form = $this->getFixture('node_article_edit_form');
    Dazzler::formAlter($form, 'node_article_edit_form');
    $expected = $form + [
      '#theme_wrappers' => ['form__node_article_edit_form'],
      '#markup' => $this->getTwigDebugComment([
        'node-article-edit-form.html.twig',
        'node-form.html.twig',
      ]),
    ];
    unset($expected['#formdazzle']);
    $data['no form__FORMID suggestions (issue #3180152)'] = [
      $form,
      $expected,
    ];

    // Form with #theme as string.
    $form = $this->getFixture('node_article_edit_form');
    $form['#theme'] = 'node_form__article__edit';
    Dazzler::formAlter($form, 'node_article_edit_form');
    $expected = $form + [
      '#theme_wrappers' => ['form__node_article_edit_form'],
      '#markup' => $this->getTwigDebugComment([
        'node-form--article--edit.html.twig',
        'node-form--article.html.twig',
        'node-form.html.twig',
      ]),
    ];
    unset($expected['#formdazzle']);
    $data['#theme is a string with suggestions'] = [
      $form,
      $expected,
    ];

    // Form that has not had Dazzler::formAlter() run on it.
    $form = $this->getFixture('with_child');
    $data['does not alter forms lacking #formdazzle data'] = [
      $form,
      $form,
    ];

    // Form that has incorrect #formdazzle data in it.
    $form = $this->getFixture('with_child') + [
      '#formdazzle' => [
        'not_form_id' => TRUE,
      ],
    ];
    $data['does not alter forms with wrong #formdazzle data'] = [
      $form,
      $form,
    ];

    return $data;
  }

  /**
   * @covers ::preRenderForm
   *
   * @dataProvider providerRepeatedPreRenderFormCalls
   */
  public function testRepeatedPreRenderFormCalls(array $form, array $expected) {
    $actual = Dazzler::preRenderForm($form);
    // Repeated calls of pre-render should have no effect.
    $actual = Dazzler::preRenderForm($actual);
    $this->assertEquals($expected, $actual, $this->getTestMessage());
  }

  /**
   * Data provider for testRepeatedPreRenderFormCalls().
   *
   * @see testRepeatedPreRenderFormCalls()
   */
  public function providerRepeatedPreRenderFormCalls() {
    $data = [];

    // Node edit form.
    $form = $this->getFixture('with_child');
    Dazzler::formAlter($form, 'with_child');
    $expected = $form + [
      '#theme_wrappers' => ['form__with_child'],
      '#markup' => $this->getTwigDebugComment(['with-child.html.twig']),
    ];
    $expected['child'] = [
      '#type' => 'with_theme_and_wrappers',
      '#theme' => 'with_theme__with_child',
      '#theme_wrappers' => ['with_theme_wrapper__with_child'],
    ];
    unset($expected['#formdazzle']);
    $data['no duplicated suggestion parts (issue #3182297)'] = [
      $form,
      $expected,
    ];

    return $data;
  }

  /**
   * @covers ::preRenderForm
   *
   * @dataProvider providerRepeatedPreRenderFormCalls
   */
  public function testPreRenderFormNoDebugging() {
    // Turn off Twig debugging.
    $twig_service = $this->createMock('\Twig_Environment');
    $twig_service->method('isDebug')->willReturn(FALSE);
    $container = new ContainerBuilder();
    $container->set('element_info', $this->elementInfoManager);
    $container->set('twig', $twig_service);
    \Drupal::setContainer($container);

    $form = $this->getFixture('node_article_edit_form');
    Dazzler::formAlter($form, 'node_article_edit_form');
    // The expected form should not contain any #markup.
    $expected = $form + ['#theme_wrappers' => ['form__node_article_edit_form']];
    unset($expected['#formdazzle']);

    $actual = Dazzler::preRenderForm($form);
    $this->assertEquals($expected, $actual, $this->getTestMessage());
  }

  /**
   * @covers ::getFormIdSuggestion
   *
   * @dataProvider providerGetFormIdSuggestion
   */
  public function testGetFormIdSuggestion(array $form, string $form_id, string $expected) {
    $actual = Dazzler::getFormIdSuggestion($form, $form_id);
    $this->assertEquals($expected, $actual, $this->getTestMessage());
  }

  /**
   * Data provider for testGetFormIdSuggestion().
   *
   * @see testGetFormIdSuggestion()
   */
  public function providerGetFormIdSuggestion() {
    return [
      'simple form ID' => [['#theme' => ['form_id']], 'form_id', 'form_id'],
      'webform submission form' => [
        [
          '#webform_id' => 'machine_id',
          '#theme' => ['webform_submission_form'],
        ],
        'webform_submission_machine_id_add_form',
        'webform_machine_id',
      ],
      'webform with #theme string' => [
        [
          '#webform_id' => 'machine_id',
          '#theme' => 'webform_submission_form',
        ],
        'webform_submission_machine_id_add_form',
        'webform_machine_id',
      ],
      'views exposed form' => [
        [
          '#theme' => [
            'views_exposed_form__view_id__display_id',
            'views_exposed_form',
          ],
        ],
        'views_exposed_form',
        'views__view_id__display_id',
      ],
    ];
  }

  /**
   * @covers ::traverse
   *
   * @dataProvider providerTraverse
   */
  public function testTraverse(array $element, string $form_id, string $form_id_suggestion, array $expected) {
    Dazzler::traverse($element, $form_id, $form_id_suggestion);
    $this->assertEquals($expected, $element, $this->getTestMessage());
  }

  /**
   * Data provider for testFormAlter().
   *
   * @see testFormAlter()
   */
  public function providerTraverse() {
    // Initial form.
    $form_id = 'simple_form';
    $form_suggestion = 'a_form_suggestion';
    $form = $this->getFixture($form_id) + [
      'parent' => $this->getFixture('with_theme_and_wrappers') + [
        '#parents' => ['parent'],
        'child' => $this->getFixture('with_theme') + [
          '#parents' => ['parent', 'child'],
        ],
      ],
    ];
    // Expected form.
    $expected = $form;
    $expected['#theme_wrappers'] = ['form__a_form_suggestion'];
    $expected['parent']['#theme'] = 'with_theme__a_form_suggestion__parent';
    $expected['parent']['#theme_wrappers'] = ['with_theme_wrapper__a_form_suggestion__parent'];
    $expected['parent']['child']['#theme'] = 'with_theme__a_form_suggestion__parent_child';

    return [
      'traverses a form' => [
        $form,
        $form_id,
        $form_suggestion,
        $expected,
      ],
    ];
  }

  /**
   * @covers ::addDefaultThemeProperties
   *
   * @dataProvider providerAddDefaultThemeProperties
   */
  public function testAddDefaultThemeProperties(array $element, array $expected) {
    Dazzler::addDefaultThemeProperties($element);
    $this->assertEquals($expected, $element, $this->getTestMessage());
  }

  /**
   * Data provider for testAddDefaultThemeProperties().
   *
   * @see testAddDefaultThemeProperties()
   */
  public function providerAddDefaultThemeProperties() {
    return [
      'Ignores non-theme defaults (#1)' => [
        $this->getFixture('no_theme_defaults'),
        $this->getFixture('no_theme_defaults'),
      ],
      'Ignores non-theme defaults (#2)' => [
        $this->getFixture('mixed_defaults'),
        $this->getFixture('mixed_defaults') + ['#theme' => 'mixed_defaults'],
      ],
      'Adds #theme #theme_wrappers defaults' => [
        $this->getFixture('with_theme_and_wrappers'),
        $this->getFixture('with_theme_and_wrappers') + [
          '#theme' => 'with_theme',
          '#theme_wrappers' => ['with_theme_wrapper'],
        ],
      ],
      'Does not override existing properties' => [
        $this->getFixture('no_default_overrides'),
        $this->getFixture('no_default_overrides'),
      ],
      'Does not add defaults for non-#type elements' => [
        $this->getFixture('no_type'),
        $this->getFixture('no_type'),
      ],
    ];
  }

  /**
   * @covers ::addSuggestions
   *
   * @dataProvider providerAddSuggestions
   */
  public function testAddSuggestions(array $element, string $form_id, string $form_id_suggestion, array $expected) {
    Dazzler::addSuggestions($element, $form_id, $form_id_suggestion);
    $this->assertEquals($expected, $element, $this->getTestMessage());
  }

  /**
   * Data provider for testAddSuggestions().
   *
   * @see testAddSuggestions()
   */
  public function providerAddSuggestions() {
    return [
      'no suggestion needed' => [
        [
          '#empty' => TRUE,
        ],
        'form_id',
        'form_suggestion',
        [
          '#empty' => TRUE,
        ],
      ],
      'add #theme suggestion' => [
        [
          '#theme' => 'theme_hook',
        ],
        'form_id',
        'form_suggestion',
        [
          '#theme' => 'theme_hook__form_suggestion',
        ],
      ],
      'does not flatten #theme array' => [
        [
          '#theme' => ['theme_hook'],
        ],
        'form_id',
        'form_suggestion',
        [
          '#theme' => ['theme_hook__form_suggestion'],
        ],
      ],
      'only add #theme suggestion to last hook' => [
        [
          '#theme' => ['theme_suggestion', 'theme_hook'],
        ],
        'form_id',
        'form_suggestion',
        [
          '#theme' => ['theme_suggestion', 'theme_hook__form_suggestion'],
        ],
      ],
      'add #theme_wrappers suggestions' => [
        [
          '#theme_wrappers' => ['container', 'theme_hook'],
        ],
        'form_id',
        'form_suggestion',
        [
          '#theme_wrappers' => [
            'container__form_suggestion',
            'theme_hook__form_suggestion',
          ],
        ],
      ],
      'add name-based #theme suggestion' => [
        [
          '#name' => 'element_name',
          '#theme' => 'theme_hook',
          '#parents' => ['parent_is_ignored'],
        ],
        'form_id',
        'form_suggestion',
        [
          '#name' => 'element_name',
          '#theme' => 'theme_hook__form_suggestion__element_name',
          '#parents' => ['parent_is_ignored'],
        ],
      ],
      'add webform-based #theme suggestion' => [
        [
          '#webform_key' => 'webform_key',
          '#theme' => 'theme_hook',
          '#parents' => ['parent_is_ignored'],
        ],
        'form_id',
        'form_suggestion',
        [
          '#webform_key' => 'webform_key',
          '#theme' => 'theme_hook__form_suggestion__webform_key',
          '#parents' => ['parent_is_ignored'],
        ],
      ],
      'add parent-element-based #theme suggestion' => [
        [
          '#theme' => 'theme_hook',
          '#parents' => ['grandparent', 'parent'],
        ],
        'form_id',
        'form_suggestion',
        [
          '#theme' => 'theme_hook__form_suggestion__grandparent_parent',
          '#parents' => ['grandparent', 'parent'],
        ],
      ],
      'add file-based #theme suggestion' => [
        [
          '#type' => 'file',
          '#theme' => 'theme_hook',
          '#parents' => ['grandparent', 'parent'],
        ],
        'form_id',
        'form_suggestion',
        [
          '#type' => 'file',
          '#theme' => 'theme_hook__form_suggestion__files_grandparent',
          '#parents' => ['grandparent', 'parent'],
        ],
      ],
      'ensure valid name #theme suggestion' => [
        [
          '#name' => 'Element Key.Val[grey-box][a1/bü]',
          '#theme' => 'theme_hook',
        ],
        'form_id',
        'form_suggestion',
        [
          '#name' => 'Element Key.Val[grey-box][a1/bü]',
          '#theme' => 'theme_hook__form_suggestion__element_key_val_grey_box_a1_b',
        ],
      ],
      'add type-based #theme suggestion (unknown)' => [
        [
          '#type' => 'unknown',
          '#theme' => 'theme_hook',
        ],
        'form_id',
        'form_suggestion',
        [
          '#type' => 'unknown',
          '#theme' => 'theme_hook__form_suggestion',
        ],
      ],
      'add type-based #theme suggestion (actions)' => [
        [
          '#type' => 'actions',
          '#theme' => 'theme_hook',
          '#parents' => ['actions'],
        ],
        'form_id',
        'form_suggestion',
        [
          '#type' => 'actions',
          '#theme' => 'theme_hook__actions__form_suggestion',
          '#parents' => ['actions'],
        ],
      ],
      'add type-based #theme suggestion (more_link)' => [
        [
          '#type' => 'more_link',
          '#theme' => 'theme_hook',
        ],
        'form_id',
        'form_suggestion',
        [
          '#type' => 'more_link',
          '#theme' => 'theme_hook__more_link__form_suggestion',
        ],
      ],
      'add type-based #theme suggestion (password_confirm)' => [
        [
          '#type' => 'password_confirm',
          '#theme' => 'theme_hook',
        ],
        'form_id',
        'form_suggestion',
        [
          '#type' => 'password_confirm',
          '#theme' => 'theme_hook__password_confirm__form_suggestion',
        ],
      ],
      'add type-based #theme suggestion (system_compact_link)' => [
        [
          '#type' => 'system_compact_link',
          '#theme' => 'theme_hook',
        ],
        'form_id',
        'form_suggestion',
        [
          '#type' => 'system_compact_link',
          '#theme' => 'theme_hook__system_compact_link__form_suggestion',
        ],
      ],
      'add #formdazzle data (form_element)' => [
        [
          '#theme_wrappers' => ['form_element'],
          '#parents' => ['parent'],
        ],
        'form_id',
        'form_suggestion',
        [
          '#theme_wrappers' => ['form_element__form_suggestion__parent'],
          '#parents' => ['parent'],
          '#formdazzle' => [
            'suggestion_suffix' => '__form_suggestion__parent',
            'form_id' => 'form_id',
            'form_id_suggestion' => 'form_suggestion',
            'form_element_name' => 'parent',
          ],
        ],
      ],
      'add view/display ID for views_exposed_form' => [
        [
          '#type' => 'form',
          '#form_id' => 'views_exposed_form',
          '#theme' => [
            'views_exposed_form__view_id__display_id',
            'views_exposed_form',
          ],
          '#theme_wrappers' => [
            'container',
            'form',
          ],
        ],
        'form_id',
        'views__view_id__display_id',
        [
          '#type' => 'form',
          '#form_id' => 'views_exposed_form',
          '#theme' => [
            'views_exposed_form__view_id__display_id',
            'views_exposed_form__view_id__display_id',
          ],
          '#theme_wrappers' => [
            'container__views__view_id__display_id',
            'form__views__view_id__display_id',
          ],
        ],
      ],
      'find correct last suggestion with array_key_last' => [
        [
          '#type' => 'form',
          '#form_id' => 'views_exposed_form',
          '#theme' => [
            0 => 'views_exposed_form__view_id__display_id',
            2 => 'views_exposed_form',
            3 => 'views_form',
          ],
          '#theme_wrappers' => [
            'container',
            'form',
          ],
        ],
        'form_id',
        'views__view_id__display_id',
        [
          '#type' => 'form',
          '#form_id' => 'views_exposed_form',
          '#theme' => [
            0 => 'views_exposed_form__view_id__display_id',
            2 => 'views_exposed_form',
            3 => 'views_form',
          ],
          '#theme_wrappers' => [
            'container__views__view_id__display_id',
            'form__views__view_id__display_id',
          ],
        ],
      ],
      'skip #theme suggestion for form type' => [
        [
          '#type' => 'form',
          '#form_id' => 'form_id',
          '#theme' => [
            'another_suggestion',
            'form_id',
          ],
          '#theme_wrappers' => [
            'container',
          ],
        ],
        'form_id',
        'form_suggestion',
        [
          '#type' => 'form',
          '#form_id' => 'form_id',
          '#theme' => [
            'another_suggestion',
            'form_id',
          ],
          '#theme_wrappers' => [
            'container__form_suggestion',
          ],
        ],
      ],
      'add suggestions to #theme_wrappers' => [
        [
          '#theme_wrappers' => [
            'wrapper',
            'form',
          ],
        ],
        'form_id',
        'form_suggestion',
        [
          '#theme_wrappers' => [
            'wrapper__form_suggestion',
            'form__form_suggestion',
          ],
        ],
      ],
      'no duplicate suggestion on "form" #theme_wrappers' => [
        [
          '#theme_wrappers' => [
            'wrapper',
            'form__form_suggestion__edit',
          ],
        ],
        'form_id',
        'form_suggestion',
        [
          '#theme_wrappers' => [
            'wrapper__form_suggestion',
            'form__form_suggestion__edit',
          ],
        ],
      ],
      'add suggestion to #theme_wrappers key-based hook' => [
        [
          '#theme_wrappers' => [
            'container' => [
              '#some',
              '#data',
            ],
            0 => 'wrapper',
            1 => 'form',
          ],
        ],
        'form_id',
        'form_suggestion',
        [
          '#theme_wrappers' => [
            'container__form_suggestion' => [
              '#some',
              '#data',
            ],
            0 => 'wrapper__form_suggestion',
            1 => 'form__form_suggestion',
          ],
        ],
      ],
    ];
  }

  /**
   * @covers ::preprocessFormElement
   *
   * @dataProvider providerPreprocessFormElement
   */
  public function testPreprocessFormElement(array $variables, array $expected) {
    Dazzler::preprocessFormElement($variables);
    $this->assertEquals($expected, $variables, $this->getTestMessage());
  }

  /**
   * Data provider for testPreprocessFormElement().
   *
   * @see testPreprocessFormElement()
   */
  public function providerPreprocessFormElement() {
    $variables1 = [
      'element' => [
        '#formdazzle' => [
          'suggestion_suffix' => '__form_id_suggestion',
          'form_id' => 'form_id',
          'form_id_suggestion' => 'form_id_suggestion',
          'form_element_name' => 'element_name',
        ],
      ],
      'label' => ['#theme' => 'form_element_label'],
    ];
    $expected1 = $variables1;
    $expected1['label']['#theme'] = 'form_element_label__form_id_suggestion';
    $variables2 = $variables1;
    $variables2['label']['#theme'] = [
      'form_element_label__thing',
      'form_element_label',
    ];
    $expected2 = $variables2;
    $expected2['label']['#theme'][1] = 'form_element_label__form_id_suggestion';
    return [
      'Add suggestion to #theme string value' => [$variables1, $expected1],
      'Add suggestion to #theme last array value' => [$variables2, $expected2],
    ];
  }

  /**
   * @covers ::moduleImplementsAlter
   *
   * @dataProvider providerModuleImplementsAlter
   */
  public function testModuleImplementsAlter(array $implementations, string $hook, array $expected) {
    Dazzler::moduleImplementsAlter($implementations, $hook);
    $this->assertEquals(
      array_keys($expected),
      array_keys($implementations),
      $this->getTestMessage()
    );
  }

  /**
   * Data provider for testModuleImplementsAlter().
   *
   * @see testModuleImplementsAlter()
   */
  public function providerModuleImplementsAlter() {
    $implementations = [
      'media_library' => FALSE,
      'menu_ui' => FALSE,
      'system' => FALSE,
      'formdazzle' => FALSE,
      'webform' => FALSE,
    ];
    $expected = [
      'media_library' => FALSE,
      'menu_ui' => FALSE,
      'system' => FALSE,
      'webform' => FALSE,
      'formdazzle' => FALSE,
    ];
    $implementations2 = $implementations;

    return [
      'reorders form_alter implementations' => [
        $implementations,
        'form_alter',
        $expected,
      ],
      'does not reorder other implementations' => [
        $implementations2,
        'other_hook_alter',
        $implementations2,
      ],
    ];
  }

}
