<?php

namespace Drupal\override_webform\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Override webform routes.
 */
class OverrideWebformController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
