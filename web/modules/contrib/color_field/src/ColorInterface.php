<?php

namespace Drupal\color_field;

/**
 * Defines a common interface for color classes.
 */
interface ColorInterface {

  /**
   * Get the color as a string.
   *
   * @return string
   *   The color as a string.
   */
  public function toString();

  /**
   * Get the color as a hex instance.
   *
   * @return \Drupal\color_field\ColorHex
   *   The color as a hex instance.
   */
  public function toHex();

  /**
   * Get the color as a RGB instance.
   *
   * @return \Drupal\color_field\ColorRGB
   *   The color as a RGB instance.
   */
  public function toRgb();

  /**
   * Get the color as a HSL instance.
   *
   * @return \Drupal\color_field\ColorHSL
   *   The color as a HSL instance.
   */
  public function toHsl();

  // Public function toHSV();
  // public function toCMYK();
  // public function toCSS();
}
