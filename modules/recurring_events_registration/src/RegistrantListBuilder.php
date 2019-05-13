<?php

namespace Drupal\recurring_events_registration;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a class to build a listing of Registrant entities.
 *
 * @ingroup recurring_events_registration
 */
class RegistrantListBuilder extends EntityListBuilder {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * The request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * The registration creation service.
   *
   * @var \Drupal\recurring_events_registration\RegistrationCreationService
   */
  protected $creationService;

  /**
   * Constructs a new EventInstanceListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The config factory service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The request object.
   * @param \Drupal\recurring_events_registration\RegistrationCreationService $creation_service
   *   The registration creation service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, ConfigFactory $config, RequestStack $request, RegistrationCreationService $creation_service) {
    parent::__construct($entity_type, $storage);
    $this->config = $config;

    $config = $this->config->get('recurring_events_registration.registrant.config');
    $this->limit = $config->get('limit');
    $this->request = $request;
    $this->creationService = $creation_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('config.factory'),
      $container->get('request_stack'),
      $container->get('recurring_events_registration.creation_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Registrant ID');
    $header['series'] = $this->t('Series');
    $header['instance'] = $this->t('Instance');
    $header['type'] = $this->t('Type');
    foreach ($this->getCustomFields() as $machine_name => $field) {
      $header[$machine_name] = $field;
    }
    $header['email'] = $this->t('Email');
    $header['waitlist'] = $this->t('Waitlist');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\recurring_events_registration\Entity\Registrant */
    $series = $entity->getEventSeries();
    $instance = $entity->getEventInstance();

    $row['id'] = $entity->id();
    $row['series'] = $series->toLink($series->title->value);
    $row['instance'] = $instance->toLink($instance->date->start_date->format($this->config->get('recurring_events_registration.registrant.config')->get('date_format')));
    $row['type'] = $entity->getRegistrationType() == 'series' ? $this->t('Series') : $this->t('Instance');
    foreach ($this->getCustomFields() as $machine_name => $field) {
      $row[$machine_name] = $entity->get($machine_name)->value;
    }
    $row['email'] = $entity->get('email')->value;
    $row['waitlist'] = $entity->get('waitlist')->value ? $this->t('Yes') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

  /**
   * Get custom fields.
   *
   * @return array
   *   An array of custom fields.
   */
  protected function getCustomFields() {
    $custom_fields = [];
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('registrant', 'registrant');
    foreach ($fields as $machine_name => $field) {
      if (strpos($machine_name, 'field_') === 0) {
        $custom_fields[$machine_name] = $field->label();
      }
    }
    return $custom_fields;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $request = $this->request->getCurrentRequest();
    $params = $request->attributes->all();

    $query = $this->getStorage()->getQuery()
      ->sort('changed', 'DESC');

    switch ($params['_route']) {
      case 'entity.registrant.instance_listing':
        $event_instance = $params['eventinstance'];
        $this->creationService->setEventInstance($event_instance);
        if ($this->creationService->getRegistrationType() === 'series') {
          $query->condition('eventseries_id', $event_instance->getEventSeries()->id());
        }
        else {
          $query->condition('eventinstance_id', $event_instance->id());
        }
        break;

      case 'registrations.user_tab':
        $user = $params['user'];
        $query->condition('user_id', $user->id());
        break;
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

}
