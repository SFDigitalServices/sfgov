<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\DrupalExtension\Context\MinkContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Hook\Scope\AfterStepScope;

use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Define application features from the specific context.
 */
class SfGovContext extends RawDrupalContext implements Context, SnippetAcceptingContext {
  /**
   * Initializes context.
   * Every scenario gets its own context object.
   *
   * @param array $parameters
   *   Context parameters (set them in behat.yml)
   */
  public function __construct(array $parameters = []) {
    // Initialize your context here
  }

  /** @var \Drupal\DrupalExtension\Context\MinkContext */
  private $minkContext;
  /** @BeforeScenario */
  public function gatherContexts(BeforeScenarioScope $scope)
  {
      $environment = $scope->getEnvironment();
      $this->minkContext = $environment->getContext('Drupal\DrupalExtension\Context\MinkContext');
  }

  //
  // Place your definition and hook methods here:
  //
  //  /**
  //   * @Given I have done something with :stuff
  //   */
  //  public function iHaveDoneSomethingWith($stuff) {
  //    doSomethingWith($stuff);
  //  }
  //

  /**
   * Checks that the element with specified css selector has the specified attribute equal to the specified attribute_value
   * Example: Then the ".title-url" element should have the attribute "target" equal to "_blank"
   * 
   * @Then /^the "(?P<element>[^"]*)" element should have the attribute "(?P<attribute>(?:[^"]|\\")*)" equal to "(?P<attribute_value>(?:[^"]|\\")*)"$/
   */
  public function theElementShouldHaveTheAttribute($element, $attribute, $attribute_value) {
    $page = $this->getSession()->getPage();
    $el = $page->find('css', $element);
    if(!$el) {
      throw new Exception('The element with selector "' . $element . '" was not found');
    }
    if($el->hasAttribute($attribute)) {
      if($el->getAttribute($attribute) != $attribute_value) {
        $exceptionMsg = 'The element with selector "' . $element . '" does not have the attribute "' . $attribute . '" equal to "' . $attribute_value . '"' . "\n";
        $exceptionMsg .= 'It has the attribute "' . $attribute . '" equal to "' . $el->getAttribute($attribute) . '"';
        throw new Exception($exceptionMsg);
      }
    } else {
      throw new Exception('The element with selector "' . $element . '" does not have the attribute "' . $attribute . '"');
    }
  }

}