<?php

/**
 * @file
 * Views functionality for the recurring_events_registration module.
 */

/**
 * Implements hook_views_data_alter().
 */
function recurring_events_registration_views_data_alter(array &$data) {
  // Set the default field for a view based on registrants.
  $data['registrant']['table']['base']['defaults']['field'] = 'email';

  $data['eventinstance_field_data']['registration_availability'] = [
    'title' => t('Event Instance Registration Availability'),
    'field' => [
      'title' => t('Event Instance Registration Availability'),
      'help' => t('The number of registration spaces available for an event instance.'),
      'field' => 'event_registration__capacity',
      'id' => 'eventinstance_registration_availability',
    ],
    'filter' => [
      'title' => t('Event Instance Registration Availability'),
      'help' => t('Whether the event instance has availability.'),
      'field' => 'event_registration__capacity',
      'id' => 'eventinstance_registration_availability',
    ],
  ];

  $data['eventinstance_field_data']['registration_count'] = [
    'title' => t('Event Instance Registration Count'),
    'field' => [
      'title' => t('Event Instance Registration Count'),
      'help' => t('The number of registrations for an event instance.'),
      'id' => 'eventinstance_registration_count',
    ],
  ];

  $data['eventinstance_field_data']['waitlist_count'] = [
    'title' => t('Event Instance Waitlist Count'),
    'field' => [
      'title' => t('Event Instance Waitlist Count'),
      'help' => t('The number of waitlisted registrants for an event instance.'),
      'id' => 'eventinstance_waitlist_count',
    ],
  ];
}
