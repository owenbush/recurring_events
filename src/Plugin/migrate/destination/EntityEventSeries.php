<?php

namespace Drupal\recurring_events\Plugin\migrate\destination;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * The 'entity:eventseries' destination plugin for Recurring Events.
 *
 * Usage:
 *
 * @code
 * destination:
 *   plugin: 'entity:eventseries'
 *   default_bundle: default
 *   source_date_field: field_event_datetime
 *   source_timezone: 'America/New_York'
 * @endcode
 *
 * Where "field_event_datetime" is the machine name of the existing date field.
 *
 * @MigrateDestination(
 *   id = "entity:eventseries"
 * )
 */
class EntityEventSeries extends EntityContentBase {

  const DATETIME_FORMAT = 'Y-m-d\TH:i:s';

  const SECONDS_IN_DAY = 60 * 60 * 24;

  const DAYS_OF_WEEK_TR = [
    'MO' => 'monday',
    'TU' => 'tuesday',
    'WE' => 'wednesday',
    'TH' => 'thursday',
    'FR' => 'friday',
    'SA' => 'saturday',
    'SU' => 'sunday',
  ];

  const BYDAY_TR = [
    '+1' => 'first',
    '+2' => 'second',
    '+3' => 'third',
    '+4' => 'fourth',
    '-1' => 'last',
  ];

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'source_date_field' => $this->t('The source date field.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $source_date_field = $this->configuration['source_date_field'];
    $source_timezone = new \DateTimeZone($this->configuration['source_timezone']);
    $source = $row->getSourceProperty($source_date_field);
    $this->setRecurringDateValues($source, $source_timezone, $row);
    return parent::import($row, $old_destination_id_values);
  }

  /**
   * Convert date field data including RRULE values to format used by module.
   *
   * @param array $source
   *   Date field data.
   * @param \DateTimeZone $source_timezone
   *   The source timezone for the recurring dates.
   * @param \Drupal\migrate\Row $row
   *   Row object.
   */
  private function setRecurringDateValues(array $source, \DateTimeZone $source_timezone, Row $row) {

    // @todo Fix problem of specifying what months here.
    // @todo Fix problem of no yearly pattern here.
    if (!$num_dates = count($source)) {
      return;
    }
    $first = $source[0];
    $last = $source[$num_dates - 1];

    $start_event = new DrupalDateTime($first['value'], DateTimeItemInterface::STORAGE_TIMEZONE);
    $start_event->setTimezone($source_timezone);
    $end_event = new DrupalDateTime($first['value2'], DateTimeItemInterface::STORAGE_TIMEZONE);
    $end_event->setTimezone($source_timezone);
    $duration = $end_event->getTimestamp() - $start_event->getTimestamp();

    $end_series = new DrupalDateTime($last['value2'], DateTimeItemInterface::STORAGE_TIMEZONE);
    $end_series->setTimezone($source_timezone);
    $rule_in = $first['rrule'] ?? NULL;

    if ($rule_in) {
      if (!$rrule = $this->parseRule($rule_in)) {
        // @todo Fix problem of what type of exception should be thrown here.
        throw new InvalidDataTypeException('Invalid RRULE.');
      }
    }

    $freq = $rrule['FREQ'] ?? NULL;
    switch ($freq) {

      case 'WEEKLY':
        $recur_type = 'weekly_recurring_date';
        if (!empty($rrule['COUNT']) && empty($rrule['UNTIL'])) {
          $rrule['UNTIL'] = $start_event->getTimestamp() + self::SECONDS_IN_DAY * 7 * $rrule['COUNT'];
        }
        $options = [
          'value' => $start_event->format(self::DATETIME_FORMAT),
          'end_value' => $rrule['UNTIL'] ?? $end_series->format(self::DATETIME_FORMAT),
          'time' => $start_event->format('h:i a'),
          'duration' => $duration,
          'days' => $rrule['BYDAY']['days'] ?? strtolower($start_event->format('l')),
        ];
        break;

      case 'MONTHLY':
        $recur_type = 'monthly_recurring_date';
        $options = [
          'value' => $start_event->format(self::DATETIME_FORMAT),
          'end_value' => $rrule['UNTIL'] ?? $end_series->format(self::DATETIME_FORMAT),
          'time' => $start_event->format('h:i a'),
          'duration' => $duration,
          'days' => $rrule['BYDAY']['days'] ?? strtolower($start_event->format('l')),
          'type' => (!empty($rrule['BYMONTHDAY'])) ? 'monthday' : 'weekday',
          'day_occurrence' => $rrule['BYDAY']['day_occurrence'] ?? NULL,
          'day_of_month' => $rrule['BYMONTHDAY'] ?? NULL,
        ];
        break;

      default:
        $recur_type = 'custom';
        $options = [];
        $custom = [];
        foreach ($source as $date) {
          $date_start = new DrupalDateTime($date['value'], $source_timezone);
          $date_end = new DrupalDateTime($date['value2'], $source_timezone);
          $custom[] = [
            'value' => $date_start->format(self::DATETIME_FORMAT),
            'end_value' => $date_end->format(self::DATETIME_FORMAT),
          ];
        } // Loop thru dates.
        $row->setDestinationProperty('custom_date', $custom);

    }

    if (!empty($rrule['EXDATE'])) {
      $row->setDestinationProperty('excluded_dates', $rrule['EXDATE']);
    }

    // @todo Fix problem of how to set this in D7 here.
    // if (!empty($rrule['INCDATE'])) {
    // $row->setDestinationProperty('excluded_dates', $rrule['INCDATE']);
    // }
    $row->setDestinationProperty('recur_type', $recur_type);
    $row->setDestinationProperty($recur_type, $options);
    // $row->setDestinationProperty('event_registration', ['value' => 0]);
  }

  /**
   * Parse an RRULE into an array.
   *
   * @param string $rrule
   *   The RRULE to parse.
   */
  private function parseRule($rrule) {

    if (!$attrs = array_filter(explode(';', preg_replace('/^(?:RRULE|EXRULE):/i', '', str_replace("\n", ';', $rrule))))) {
      return FALSE;
    }

    $options = [];
    foreach ($attrs as $attr) {
      [$key, $value] = preg_split('/[=:]/', $attr);
      $key = strtoupper($key);
      switch ($key) {

        case 'COUNT':
        case 'INTERVAL':
        case 'BYSETPOS':
        case 'BYMONTHDAY':
        case 'BYYEARDAY':
        case 'BYWEEKNO':
        case 'BYHOUR':
        case 'BYMINUTE':
        case 'BYSECOND':
        case 'FREQ':
        case 'WKST':
        case 'DTSTART':
        case 'TZID':
        case 'BYEASTER':
          $options[$key] = $value;
          break;

        case 'UNTIL':
          $options[$key] = $this->formatDate($value);
          break;

        case 'BYDAY':
          if (preg_match('/([+-]\d)(.*)/', trim($value), $matches)) {
            $options['BYDAY']['day_occurrence'] = $matches[1] ?? NULL;
            $options['BYDAY']['day_occurrence'] = $this->formatByDay($options['BYDAY']['day_occurrence']);
            $options['BYDAY']['days'] = $matches[2] ?? NULL;
            $options['BYDAY']['days'] = $this->formatDaysOfWeek($options['BYDAY']['days']);
          }
          else {
            $options['BYDAY']['days'] = $this->formatDaysOfWeek(trim($value));
          }
          break;

        case 'BYMONTH':
        case 'BYWEEKDAY':
          $value = $this->formatDaysOfWeek($value);
          $options[$key] = array_filter(explode(',', $value));
          break;

        case 'EXDATE':
          foreach (array_filter(explode(',', $value)) as $datetime) {
            $options['EXDATE'][] = [
              'value' => $this->formatDate($datetime, 'Y-m-d'),
              'end_value' => $this->formatDate($datetime, 'Y-m-d'),
            ];
          }
          break;

        default:
          // @todo Fix problem of what type of exception should be thrown here.
          throw new InvalidDataTypeException(sprintf('Invalid RRULE attribute of "%s".', $key));

      }

    } // Loop thru attributes.
    return $options;

  }

  /**
   * Get formatted date from RRULE data.
   *
   * @param string $value
   *   Date in format 20210107T050000Z.
   * @param string $format
   *   Date format for output.
   *
   * @return false|string
   *   Formatted date string.
   */
  private function formatDate($value, $format = self::DATETIME_FORMAT) {
    $timestamp = $this->getTimestamp($value);
    if ($timestamp === FALSE) {
      return FALSE;
    }
    return date($format, $timestamp);
  }

  /**
   * Get timestamp for RRULE data.
   *
   * @param string $value
   *   Date in format 20210107T050000Z.
   *
   * @return false|int
   *   Unix timestamp.
   */
  private function getTimestamp($value) {
    $value = trim(preg_replace('/[TZ]/', ' ', $value));
    return strtotime($value);
  }

  /**
   * Convert RRULE day values to format used by recurring_events.
   *
   * @param string $value
   *   Day(s) in RRULE format (e.g. MO,TU).
   *
   * @return string
   *   Days in recurring_events format (e.g. monday,tuesday).
   */
  private function formatDaysOfWeek($value) {
    return strtr($value, self::DAYS_OF_WEEK_TR);
  }

  /**
   * Convert RRULE format for BYDAY to format used by recurring_events.
   *
   * @param string $value
   *   BYDAY values like "+1" in "+1TH".
   *
   * @return string
   *   BYDAY in recurring_events format (e.g. "first").
   */
  private function formatByDay($value) {
    return strtr($value, self::BYDAY_TR);
  }

}
