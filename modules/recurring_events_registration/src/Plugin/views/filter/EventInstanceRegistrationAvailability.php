<?php

namespace Drupal\recurring_events_registration\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\recurring_events_registration\RegistrationCreationService;


/**
 * Filter handler to show the availability of registrations for event instances.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("eventinstance_registration_availability")
 */
class EventInstanceRegistrationAvailability extends FilterPluginBase {

  /**
   * The registration creation service.
   *
   * @var \Drupal\recurring_events_registration\RegistrationCreationService
   */
  protected $registrationCreationService;

  /**
   * Constructs a new EventInstanceRegistrationAvailability object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\recurring_events_registration\RegistrationCreationService $registration_creation_service
   *   The registration creation service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RegistrationCreationService $registration_creation_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->registrationCreationService = $registration_creation_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('recurring_events_registration.creation_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    return $this->options['available'];
  }

  /**
   * {@inheritdoc}
   */
  protected function operatorForm(&$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['available'] = [
      'default' => 'available',
    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['available'] = [
      '#title' => $this->t('Availability.'),
      '#type' => 'select',
      '#options' => [
        'available' => $this->t('Spaces Available'),
        'full' => $this->t('Event Full'),
      ],
      '#multiple' => FALSE,
      '#default_value' => $this->options['available'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Set -1 as the default value so that if no events match the checks, then
    // we should get no results, rather than all results.
    $items = ['-1'];
    $table = $this->ensureMyTable();

    // Grab the current view being executed.
    $view = clone $this->query->view;
    $filters = $view->filter;
    // Remove any instances of this filter from the filters.
    if (!empty($filters)) {
      foreach ($filters as $key => $filter) {
        if ($filter instanceof EventInstanceRegistrationAvailability) {
          unset($view->filter[$key]);
        }
      }
    }
    // Execute the current view with the filters removed, so we can reduce the
    // number of event instances we need to examine to find their availability.
    // This makes the query more efficient and avoids having to do messy union
    // selects across multiple tables to determine the availability of an event.
    $view->preExecute();
    $view->execute();

    if (!empty($view->result)) {
      foreach ($view->result as $key => $result) {
        $this->registrationCreationService->setEventInstance($result->_entity);
        $availability = $this->registrationCreationService->retrieveAvailability();

        switch ($this->options['available']) {
          // Filtering for available events means unlimited availability of an
          // availability greater than zero.
          case 'available':
            if ($availability === -1 || $availability > 0) {
              $items[] = $result->_entity->id();
            }
            break;

          // Filtering for full events means an event with exactly zero
          // availability.
          case 'full':
            if ($availability == 0) {
              $items[] = $result->_entity->id();
            }
            break;
        }
      }
    }

    // Filter this view by the events which match the availability above.
    $items = implode(',', $items);
    $this->query->addWhereExpression($this->options['group'], "$table.id IN (" . $items . ")");
  }

}
