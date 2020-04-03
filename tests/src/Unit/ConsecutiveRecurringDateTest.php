<?php

namespace Drupal\Tests\recurring_events\Unit;

use Drupal\recurring_events\Plugin\Field\FieldType\ConsecutiveRecurringDate;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\recurring_events\Plugin\Field\FieldType\ConsecutiveRecurringDate
 * @group recurring_events
 */
class ConsecutiveRecurringDateTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Some of Drupal's global functions are unavailable so we mock them up in
    // a separate file to keep them from muddying the global scope.
    require_once 'includes/OverriddenGlobalFunctions.php';

    // We need a mocked container which is used by DrupalDateTime.
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);

    // DrupalDateTime also needs the language manager and a mocked language.
    $language_manager_mock = $this->getMockBuilder('Drupal\\Core\\Language\\LanguageManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $language_mock = $this->createMock('Drupal\\Core\\Language\\LanguageInterface');

    // Ensure that getCurrentLanguage returns the mocked language.
    $language_manager_mock->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue($language_mock));

    $container->set('language_manager', $language_manager_mock);
  }

  /**
   * Tests ConsecutiveRecurringDate::findDailyDatesBetweenDates().
   */
  public function testFindDailyDatesBetweenDates() {
    // We want to test for generating all the days between Jan 1st and Jan 7th.
    $start_date = new DrupalDateTime('2019-01-01 00:00:00');
    $end_date = new DrupalDateTime('2019-01-07 00:00:00');

    $expected_dates = $dates = [];

    $expected_date_objects = [
      new DrupalDateTime('2019-01-01 00:00:00'),
      new DrupalDateTime('2019-01-02 00:00:00'),
      new DrupalDateTime('2019-01-03 00:00:00'),
      new DrupalDateTime('2019-01-04 00:00:00'),
      new DrupalDateTime('2019-01-05 00:00:00'),
      new DrupalDateTime('2019-01-06 00:00:00'),
      new DrupalDateTime('2019-01-07 00:00:00'),
    ];

    $date_objects = ConsecutiveRecurringDate::findDailyDatesBetweenDates($start_date, $end_date);

    // Because the objects themselves will be different we convert each of the
    // date time objects into an ISO standard date format for comparison.
    foreach ($expected_date_objects as $date) {
      $expected_dates[] = $date->format('r');
    }

    foreach ($date_objects as $date) {
      $dates[] = $date->format('r');
    }

    $this->assertSame($expected_dates, $dates);
  }

  /**
   * Tests ConsecutiveRecurringDate::findSlotsBetweenTimes().
   */
  public function testFindSlotsBetweenTimes() {
    // We want to test for generating all the time slots between midnight and
    // 1am with a 10min duration and 5min buffer.
    $start_date = new DrupalDateTime('2019-01-01 00:00:00');

    $form_data = [
      'end_time' => '01:00:00',
      'duration' => '10',
      'duration_units' => 'minute',
      'buffer' => '5',
      'buffer_units' => 'minute',
    ];

    $expected_dates = $dates = [];

    $expected_date_objects = [
      new DrupalDateTime('2019-01-01 00:00:00'),
      new DrupalDateTime('2019-01-01 00:15:00'),
      new DrupalDateTime('2019-01-01 00:30:00'),
      new DrupalDateTime('2019-01-01 00:45:00'),
      new DrupalDateTime('2019-01-01 01:00:00'),
    ];

    $date_objects = ConsecutiveRecurringDate::findSlotsBetweenTimes($start_date, $form_data);

    // Because the objects themselves will be different we convert each of the
    // date time objects into an ISO standard date format for comparison.
    foreach ($expected_date_objects as $date) {
      $expected_dates[] = $date->format('r');
    }

    foreach ($date_objects as $date) {
      $dates[] = $date->format('r');
    }

    $this->assertSame($expected_dates, $dates);
  }

}
