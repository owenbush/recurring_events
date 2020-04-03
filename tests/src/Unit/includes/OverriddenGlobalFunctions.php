<?php

/**
 * @file
 * Includes some overridden global functions.
 */

if (!function_exists('drupal_get_user_timezone')) {

  /**
   * Overrides global drupal_get_user_timezone if not exists.
   *
   * @return string
   *   Timezone mocked.
   */
  function drupal_get_user_timezone() {
    return @date_default_timezone_get();
  }

}
