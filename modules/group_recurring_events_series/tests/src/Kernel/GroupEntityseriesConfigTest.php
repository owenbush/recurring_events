<?php

namespace Drupal\Tests\group_recurring_events_series\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests that all config provided by this module passes validation.
 *
 * @group group_recurring_events_series
 */
class GroupEntityseriesConfigTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['group', 'options', 'entity', 'variationcache', 'recurring_events', 'group_recurring_events_series', 'views'];

  /**
   * Tests that the module's config installs properly.
   */
  public function testConfig() {
    $this->installConfig(['group_recurring_events_series']);
  }

}
