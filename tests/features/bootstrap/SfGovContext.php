<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\DrupalExtension\Context\MinkContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Hook\Scope\AfterStepScope;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\DrupalExtension\Hook\Scope\EntityScope;

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
   * Example: Then the "target" attribute of the ".title-url" element should contain "_blank"
   * 
   * @Then /^the "(?P<attribute>(?:[^"]|\\")*)" attribute of the "(?P<element>[^"]*)" element should contain "(?P<attribute_value>(?:[^"]|\\")*)"$/
   */
  public function theAttributeOfTheElementShouldContain($attribute, $element, $attribute_value) {
    $page = $this->getSession()->getPage();
    $el = $page->find('css', $element);
    if(!$el) {
      throw new Exception('The element with selector "' . $element . '" was not found');
    }
    if($el->hasAttribute($attribute)) {
      if(strpos($el->getAttribute($attribute), $attribute_value) < 0) {
        $exceptionMsg = 'The "' . $attribute . '" attribute of the "' . $element . '" element does not contain "' . $attribute_value . '"' . "\n";
        $exceptionMsg .= 'It has the attribute "' . $attribute . '" equal to "' . $el->getAttribute($attribute) . '"';
        throw new Exception($exceptionMsg);
      }
    } else {
      throw new Exception('The element with selector "' . $element . '" does not have the attribute "' . $attribute . '"');
    }
  }

  /**
   * @When I click the :element element
   */
  public function iClickTheElement($element) {
    $page = $this->getSession()->getPage();
    $el = $page->find('css', $element);
    if(!$el) {
      throw new Exception('The element with selector "' . $element . '" was not found');
    }
    $el->click();
  }
}