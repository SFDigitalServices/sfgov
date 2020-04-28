<?php

namespace Drupal\color_field;

/**
 * ColorCMY represents the CMY color format.
 */
class ColorCMY extends ColorBase {

  /**
   * The cyan.
   *
   * @var float
   */
  private $cyan;

  /**
   * The magenta.
   *
   * @var float
   */
  private $magenta;

  /**
   * The yellow.
   *
   * @var float
   */
  private $yellow;

  /**
   * Create a new CMYK color.
   *
   * @param float $cyan
   *   The cyan.
   * @param float $magenta
   *   The magenta.
   * @param float $yellow
   *   The yellow.
   * @param float $opacity
   *   The opacity.
   */
  public function __construct($cyan, $magenta, $yellow, $opacity) {
    $this->cyan = $cyan;
    $this->magenta = $magenta;
    $this->yellow = $yellow;
    $this->opacity = floatval($opacity);
  }

  /**
   * Get the amount of Cyan.
   *
   * @return int
   *   The amount of cyan.
   */
  public function getCyan() {
    return $this->cyan;
  }

  /**
   * Get the amount of Magenta.
   *
   * @return int
   *   The amount of magenta.
   */
  public function getMagenta() {
    return $this->magenta;
  }

  /**
   * Get the amount of Yellow.
   *
   * @return int
   *   The amount of yellow.
   */
  public function getYellow() {
    return $this->yellow;
  }

  /**
   * A string representation of this color in the current format.
   *
   * @param bool $opacity
   *   Whether or not to display the opacity.
   *
   * @return string
   *   The color in format: #RRGGBB.
   */
  public function toString($opacity = TRUE) {
    return $this->toHex()->toString($opacity);
  }

  /**
   * {@inheritdoc}
   */
  public function toHex() {
    return $this->toRgb()->toHex();
  }

  /**
   * {@inheritdoc}
   */
  public function toRgb() {
    $red = (1 - $this->cyan) * 255;
    $green = (1 - $this->magenta) * 255;
    $blue = (1 - $this->yellow) * 255;
    return new ColorRGB($red, $green, $blue, $this->getOpacity());
  }

  /**
   * {@inheritdoc}
   */
  public function toCmy() {
    $cyan = ($this->cyan * (1 - $this->key) + $this->key);
    $magenta = ($this->magenta * (1 - $this->key) + $this->key);
    $yellow = ($this->yellow * (1 - $this->key) + $this->key);
    return new ColorCMY($cyan, $magenta, $yellow);
  }

  /**
   * {@inheritdoc}
   */
  public function toHsl() {
    return $this->toRgb()->toHsl();
  }

}
