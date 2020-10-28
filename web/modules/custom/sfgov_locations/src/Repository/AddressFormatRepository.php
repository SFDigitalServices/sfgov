<?php

namespace Drupal\sfgov_locations\Repository;

use Drupal\address\Repository\AddressFormatRepository as AddressFormatRepositoryBase;
use Drupal\address\Event\AddressEvents;
use Drupal\address\Event\AddressFormatEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\sfgov_locations\AddressField;
use Drupal\sfgov_locations\AddressFormat;

/**
 * Provides address formats.
 *
 * Address formats are stored inside the base class, which is extended here to
 * allow the definitions to be altered via events.
 */
class AddressFormatRepository extends AddressFormatRepositoryBase {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Creates an AddressFormatRepository instance.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher) {
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function get($countryCode) {
    $countryCode = strtoupper($countryCode);
    if (!isset($this->addressFormats[$countryCode])) {
      $definitions = $this->getDefinitions();
      $definition = isset($definitions[$countryCode]) ? $definitions[$countryCode] : [];
      $definition = $this->processDefinition($countryCode, $definition);
        $this->addressFormats[$countryCode] = new AddressFormat($definition);
    }
    return $this->addressFormats[$countryCode];
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    $definitions = $this->getDefinitions();
    $addressFormats = [];
    foreach ($definitions as $countryCode => $definition) {
        $definition = $this->processDefinition($countryCode, $definition);
        $addressFormats[$countryCode] = new AddressFormat($definition);
    }

    return $addressFormats;
  }

  /**
   * {@inheritdoc}
   */
  protected function processDefinition($countryCode, array $definition) {
    $definition['country_code'] = $countryCode;
    // Merge-in defaults.
    $definition += $this->getGenericDefinition();
    // Always require the given name and family name.
    $definition['required_fields'][] = AddressField::GIVEN_NAME;
    $definition['required_fields'][] = AddressField::FAMILY_NAME;

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefinitions() {
    $definitions = parent::getDefinitions();
    if (!empty($definitions['US'])) {
      $definitions['US']['format'] = "%givenName %familyName\n%organization\n%addressee\n%location_name\n%addressLine1\n%addressLine2\n%locality, %administrativeArea %postalCode";
    }
    return $definitions;
  }

}
