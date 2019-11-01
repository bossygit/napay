<?php
/**
namespace Drupal\napay\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;

 */
/**
 * @CommerceCheckoutPane(
 *  id = "napay_checkout_pane",
 *  label = @Translation("Mobile money."),
 *  display_label = @Translation("Mobile money."),
 *  default_step = "string",
 *  wrapper_element = "string",
 * )
 */
/**
class NapayCheckoutPane extends CheckoutPaneBase implements  CheckoutPaneInterface {




      /**
      * {@inheritdoc}
     
      public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
        // Builds the pane form.

$pane_form['mobile_number'] = array(
  '#type' => 'textfield',
  '#title' => t('Your number phone'),
  '#size' => '10',
  '#description' => t('Send 1000 XFA to 06 478 14 14'),
  '#required' => TRUE,
  );
return $pane_form;
      }
      /**
      * {@inheritdoc}
    
      public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
        // Validates the pane form.
      }
      /**
      * {@inheritdoc}
     
      public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
        // Handles the submission of an pane form.
      }


}
 */
