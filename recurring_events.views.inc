<?php

/**
 * @file
 * Views functionality for the recurring_events module.
 */

/**
 * Implements hook_views_data_alter().
 */
function recurring_events_views_data_alter(array &$data) {
  // Create a field to show the number of instances of an eventseries.
  $data['eventseries_field_data']['eventseries_instance_count'] = [
    'title' => t('Event series instance count'),
    'field' => [
      'title' => t('Event series instance count'),
      'help' => t('The number of event instances in a series.'),
      'id' => 'eventseries_instance_count',
      'click sortable' => FALSE,
    ],
  ];

  // Create a field to show the start date of an event series.
  $data['eventseries_field_data']['eventseries_start_date'] = [
    'title' => t('Event series start date'),
    'field' => [
      'title' => t('Event series start date'),
      'help' => t('The date on which an event first occurs.'),
      'id' => 'eventseries_start_date',
      'click sortable' => FALSE,
    ],
  ];

  // Set the default field for a view based on eventinstances.
  $data['eventinstance_field_data']['table']['base']['defaults']['field'] = 'id';

  // @todo Remove these declarations when
  // https://www.drupal.org/project/drupal/issues/2489476 is resolved.
  $table_name = 'eventinstance_field_data';

  $fields = [
    'Event Date' => 'date__value',
    'Event Date (End)' => 'date__end_value',
  ];

  $arguments = [
    // Argument type => help text.
    'year' => t('Date in the form of YYYY.'),
    'month' => t('Date in the form of MM (01 - 12).'),
    'day' => t('Date in the form of DD (01 - 31).'),
    'week' => t('Date in the form of WW (01 - 53).'),
    'year_month' => t('Date in the form of YYYYMM.'),
    'full_date' => t('Date in the form of CCYYMMDD.'),
  ];

  foreach ($fields as $field => $field_name) {
    $data[$table_name][$field_name]['filter']['id'] = 'datetime';
    $data[$table_name][$field_name]['filter']['field_name'] = 'date';
    $data[$table_name][$field_name]['sort']['id'] = 'datetime';
    $data[$table_name][$field_name]['sort']['field_name'] = 'date';
    $data[$table_name][$field_name]['argument']['id'] = 'datetime';
    $data[$table_name][$field_name]['argument']['field_name'] = 'date';

    $group = $data[$table_name][$field_name]['group'] ?? '';

    foreach ($arguments as $argument_type => $help_text) {
      $data[$table_name][$field_name . '_' . $argument_type] = [
        'title' => t('@label (@argument)', [
          '@label' => $field,
          '@argument' => $argument_type,
        ]),
        'help' => $help_text,
        'argument' => [
          'field' => $field_name,
          'id' => 'datetime_' . $argument_type,
          'entity_type' => 'eventinstance',
          'field_name' => 'date',
        ],
        'group' => $group,
      ];
    }
  }

  // We do not want people adding the recurrence fields to views as they will
  // not work. Instead for any fields necessary we create them above.
  $fields = [
    'consecutive_recurring_date__',
    'daily_recurring_date__',
    'weekly_recurring_date__',
    'monthly_recurring_date__',
  ];
  foreach ($fields as $field) {
    foreach ($data['eventseries_field_data'] as $field_name => $field_info) {
      if (strpos($field_name, $field) === 0) {
        unset($data['eventseries_field_data'][$field_name]);
      }
    }

    foreach ($data['eventseries_field_revision'] as $field_name => $field_info) {
      if (strpos($field_name, $field) === 0) {
        unset($data['eventseries_field_revision'][$field_name]);
      }
    }
  }
}
