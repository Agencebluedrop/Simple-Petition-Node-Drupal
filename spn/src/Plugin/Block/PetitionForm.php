<?php

/**
 * @file
 * Contains \Drupal\spn\Plugin\Block\PetitionFormBlock.
 */

namespace Drupal\spn\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormInterface;

/**
 * Provides a 'Petition' block.
 *
 * @Block(
 *   id = "petition_form",
 *   admin_label = @Translation("Petition Form Block"),
 *   category = @Translation("Petition")
 * )
 */
class PetitionForm extends BlockBase {

  /**
  * {@inheritdoc}
  */
  public function build() {
    
    // if route is an node
    if (\Drupal::routeMatch()->getRouteName() == 'entity.node.canonical') {

      // if node is a petition then load the form
      if (\Drupal::routeMatch()->getParameter('node')->getType() == 'petition') {
        return [
          '#machine_name'     => $this->getConfiguration()['id'],
          '#form'             => \Drupal::formBuilder()->getForm('Drupal\spn\Form\PetitionForm'),
          '#theme'            => 'spn__block',
          '#cache' => [
            'max-age' => 0,
          ],
        ];
      }
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
      return 0;
  }

}