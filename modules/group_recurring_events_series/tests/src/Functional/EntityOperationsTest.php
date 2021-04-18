<?php

namespace Drupal\Tests\group_recurring_events_series\Functional;

use Drupal\Tests\group\Functional\EntityOperationsTest as GroupEntityOperationsTest;

/**
 * Tests that entity operations (do not) show up on the group overview.
 *
 * @see group_recurring_events_series_entity_operation()
 *
 * @group group_recurring_events_series
 */
class EntityOperationsTest extends GroupEntityOperationsTest {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group_recurring_events_series'];

  /**
   * {@inheritdoc}
   */
  public function provideEntityOperationScenarios() {
    $scenarios['withoutAccess'] = [
      [],
      ['group/1/eventseries' => 'Event series'],
    ];

    $scenarios['withAccess'] = [
      [],
      ['group/1/eventseries' => 'Event series'],
      ['access group_recurring_events_series overview'],
    ];

    $scenarios['withAccessAndViews'] = [
      ['group/1/eventseries' => 'Event series'],
      [],
      ['access group_recurring_events_series overview'],
      ['views'],
    ];

    return $scenarios;
  }

}
