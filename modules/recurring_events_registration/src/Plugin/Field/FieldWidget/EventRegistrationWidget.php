<?php

namespace Drupal\recurring_events_registration\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\datetime_range\Plugin\Field\FieldWidget\DateRangeDefaultWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Plugin implementation of the 'event registration' widget.
 *
 * @FieldWidget (
 *   id = "event_registration",
 *   label = @Translation("Event registration widget"),
 *   field_types = {
 *     "event_registration"
 *   }
 * )
 */
class EventRegistrationWidget extends DateRangeDefaultWidget {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#type'] = 'container';

    $element['registration'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Registration'),
      '#description' => $this->t('Select this box to enable registrations for this event. By doing so you will be able to specify the capacity of the event, and if applicable enable a waitlist.'),
      '#weight' => 0,
      '#default_value' => $items[$delta]->registration ?: '',
    ];

    $element['registration_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Registration Type'),
      '#description' => $this->t('Select whether registrations are for the entire series, or for individual instances.'),
      '#weight' => 1,
      '#default_value' => $items[$delta]->registration_type ?: 'instance',
      '#options' => [
        'instance' => $this->t('Individual Event Registration'),
        'series' => $this->t('Entire Series Registration'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="event_registration[0][registration]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['registration_dates'] = [
      '#type' => 'radios',
      '#title' => $this->t('Registration Dates'),
      '#description' => $this->t('Choose between open or scheduled registration. Open registration ends when the event begins.'),
      '#weight' => 2,
      '#default_value' => $items[$delta]->registration_dates ?: 'open',
      '#options' => [
        'open' => $this->t('Open Registration'),
        'scheduled' => $this->t('Scheduled Registration'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="event_registration[0][registration]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['series_registration'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Series Registration'),
      '#weight' => 3,
      '#states' => [
        'visible' => [
          ':input[name="event_registration[0][registration]"]' => ['checked' => TRUE],
          ':input[name="event_registration[0][registration_type]"]' => ['value' => 'series'],
          ':input[name="event_registration[0][registration_dates]"]' => ['value' => 'scheduled'],
        ],
      ],
    ];

    $element['series_registration']['value'] = $element['value'];
    $element['series_registration']['end_value'] = $element['end_value'];
    unset($element['value']);
    unset($element['end_value']);

    $element['series_registration']['value']['#title'] = $this->t('Registration Opens');
    $element['series_registration']['end_value']['#title'] = $this->t('Registration Closes');

    $element['instance_registration'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Instance Registration'),
      '#weight' => 3,
      '#states' => [
        'visible' => [
          ':input[name="event_registration[0][registration]"]' => ['checked' => TRUE],
          ':input[name="event_registration[0][registration_type]"]' => ['value' => 'instance'],
          ':input[name="event_registration[0][registration_dates]"]' => ['value' => 'scheduled'],
        ],
      ],
    ];

    $element['instance_registration']['instance_schedule_open'] = [
      '#type' => 'select',
      '#title' => $this->t('When Should Registration Open?'),
      '#description' => $this->t('Select when to open registration'),
      '#weight' => 0,
      '#default_value' => $items[$delta]->instance_schedule_open,
      '#options' => [
        'now' => $this->t('Now'),
        'start' => $this->t('At the start of the event'),
        'custom' => $this->t('Before the start of the event'),
      ],
    ];

    $element['instance_registration']['open_registration'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Open Schedule'),
      '#weight' => 1,
      '#states' => [
        'visible' => [
          ':input[name="event_registration[0][instance_registration][instance_schedule_open]"]' => ['value' => 'custom'],
        ],
      ],
      '#description' => $this->t('Select how long before the event starts that registration should open'),
    ];

    $element['instance_registration']['open_registration']['instance_schedule_open_amount'] = [
      '#type' => 'number',
      '#title' => $this->t('By how much time?'),
      '#weight' => 1,
      '#default_value' => $items[$delta]->instance_schedule_open_amount ?? '1',
      '#min' => 0,
    ];

    $element['instance_registration']['open_registration']['instance_schedule_open_units'] = [
      '#type' => 'select',
      '#title' => '',
      '#weight' => 2,
      '#default_value' => $items[$delta]->instance_schedule_open_units ?? 'month',
      '#options' => [
        'month' => $this->t('Months'),
        'week' => $this->t('Weeks'),
        'day' => $this->t('Days'),
        'hour' => $this->t('Hours'),
        'minute' => $this->t('Minutes'),
        'second' => $this->t('Seconds'),
      ],
    ];

    $element['instance_registration']['instance_schedule_close'] = [
      '#type' => 'select',
      '#title' => $this->t('When Should Registration Close?'),
      '#description' => $this->t('Select when to close registration'),
      '#weight' => 3,
      '#default_value' => $items[$delta]->instance_schedule_close,
      '#options' => [
        'start' => $this->t('At the start of the event'),
        'end' => $this->t('At the end of the event'),
        'custom' => $this->t('Custom schedule'),
      ],
    ];

    $element['instance_registration']['close_registration'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Close Schedule'),
      '#weight' => 4,
      '#states' => [
        'visible' => [
          ':input[name="event_registration[0][instance_registration][instance_schedule_close]"]' => ['value' => 'custom'],
        ],
      ],
      '#description' => $this->t('Select how long before or after the event starts that registration should close'),
    ];

    $element['instance_registration']['close_registration']['instance_schedule_close_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Close Registration'),
      '#weight' => 1,
      '#default_value' => $items[$delta]->instance_schedule_close_type ?: '',
      '#options' => [
        'before' => $this->t('Before the event starts'),
        'after' => $this->t('After the event starts'),
      ],
    ];

    $element['instance_registration']['close_registration']['instance_schedule_close_amount'] = [
      '#type' => 'number',
      '#title' => $this->t('By How Much Time?'),
      '#weight' => 2,
      '#default_value' => $items[$delta]->instance_schedule_close_amount ?? '1',
      '#min' => 0,
    ];

    $element['instance_registration']['close_registration']['instance_schedule_close_units'] = [
      '#type' => 'select',
      '#title' => '',
      '#weight' => 3,
      '#default_value' => $items[$delta]->instance_schedule_close_units ?? 'week',
      '#min' => 0,
      '#options' => [
        'month' => $this->t('Months'),
        'week' => $this->t('Weeks'),
        'day' => $this->t('Days'),
        'hour' => $this->t('Hours'),
        'minute' => $this->t('Minutes'),
        'second' => $this->t('Seconds'),
      ],
    ];

    $element['capacity'] = [
      '#type' => 'number',
      '#title' => $this->t('Total Number of Spaces Available'),
      '#description' => $this->t('Maximum number of attendees available for each series, or individual event. Leave blank for unlimited.'),
      '#weight' => 5,
      '#default_value' => $items[$delta]->capacity ?: '',
      '#min' => 0,
      '#states' => [
        'visible' => [
          ':input[name="event_registration[0][registration]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['waitlist'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Waiting List'),
      '#description' => $this->t('Enable a waiting list if the number of registrations reaches capacity.'),
      '#weight' => 6,
      '#default_value' => $items[$delta]->waitlist ?: '',
      '#states' => [
        'visible' => [
          ':input[name="event_registration[0][registration]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      $item['value'] = $item['series_registration']['value'];
      $item['end_value'] = $item['series_registration']['end_value'];
      $item['time_amount'] = (int) $item['instance_registration']['time_amount'];
      $item['time_type'] = $item['instance_registration']['time_type'];
      $item['capacity'] = (int) $item['capacity'];
      unset($item['series_registration']);
      unset($item['instance_registration']);

      if (empty($item['value'])) {
        $item['value'] = '';
      }

      if (empty($item['end_value'])) {
        $item['end_value'] = '';
      }

      if (empty($item['registration'])) {
        $item['registration'] = 0;
      }

      if (empty($item['registration_type'])) {
        $item['registration_type'] = '';
      }

      if (empty($item['registration_dates'])) {
        $item['registration_dates'] = '';
      }

      if (empty($item['capacity'])) {
        $item['capacity'] = 0;
      }

      if (empty($item['waitlist'])) {
        $item['waitlist'] = 0;
      }

      if (empty($item['time_amount'])) {
        $item['time_amount'] = 0;
      }

      if (empty($item['time_type'])) {
        $item['time_type'] = '';
      }

    }
    $values = parent::massageFormValues($values, $form, $form_state);
    return $values;
  }

  /**
   * Validate callback to ensure that the start date <= the end date.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public function validateStartEnd(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $start_date = $element['series_registration']['value']['#value']['object'];
    $end_date = $element['series_registration']['end_value']['#value']['object'];

    if ($start_date instanceof DrupalDateTime && $end_date instanceof DrupalDateTime) {
      if ($start_date->getTimestamp() !== $end_date->getTimestamp()) {
        $interval = $start_date->diff($end_date);
        if ($interval->invert === 1) {
          $form_state->setError($element, $this->t('The @title end date cannot be before the start date', ['@title' => $element['#title']]));
        }
      }
    }
  }

}
