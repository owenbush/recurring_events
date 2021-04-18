<?php

namespace Drupal\group_recurring_events_series\Plugin\GroupContentEnabler;

use Drupal\recurring_events\Entity\EventSeriesType;
use Drupal\Component\Plugin\Derivative\DeriverBase;

class GroupEventSeriesDeriver extends DeriverBase {

  /**
   * {@inheritdoc}.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    foreach (EventSeriesType::loadMultiple() as $name => $eventseries_type) {
      $label = $eventseries_type->label();

      $this->derivatives[$name] = [
        'entity_bundle' => $name,
        'label' => t('Group event series (@type)', ['@type' => $label]),
        'description' => t('Adds %type content to groups both publicly and privately.', ['%type' => $label]),
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
