<?php

/**
 * @file
 * Contains \Drupal\spn\Form\NotificationsForm.
 */

namespace Drupal\spn\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Module configuration form
 */
class NotificationsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'spn_notifications_form';
  }
  
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'spn.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('spn.settings');
    $message_expiry = $config->get('spn_message_expiry');
    $message_validation = $config->get('spn_message_validation');
    $message_error = $config->get('spn_message_error');

    // variables
    $i = 1;
    $keys = self::getEmailsInfos();

    $form['description_messages'] = [
      '#markup' =>'
        <h2>' . t('Messages') . '</h2>',
    ];

    $form['message_expiry'] =[
      '#type' => 'textfield',
      '#default_value' => $message_expiry,
      '#title'=> t('Link expiry message') ,
      '#description' => t('Message to show the user if the validation link has expired or has already been used before.'),
    ];

    $form['message_validation'] =[
      '#type' => 'textfield',
      '#default_value' => $message_validation,
      '#title'=> t('Link Validation message'),
      '#description' => t('Message to show the user if the validation link has been clicked and worked successfully.'),
    ];

    $form['message_error'] =[
      '#type' => 'textfield',
      '#default_value' => $message_error,
      '#title'=> t('Error message'),
      '#description' => t('Message to show the user if there\'s any other error during validation.'),
    ];

    // token usage description
    $form['variables_tokens'] = [
      '#markup' =>'
        <h2>' . t('Emails') . '</h2>
        <ul>
          <li><b>' . t('Petition Validation Link') . '</b> -> {_Validate-Link_} </li>
          <li><b>' . t('Petition Node Title') . '</b> -> {_Node-Title_}</li>
        </ul>',
    ];

    // create a form for each email type
    foreach ($keys as $k=>$v){

      // set as tree
      $form[$k] = [
        '#tree' => true,
      ];

      // subject field
      $form[$k]['subject'] =[
        '#type' => 'textfield',
        '#prefix' => "<div class='email-" . $i . "'><h3>" . $i . ") " . $v['title'] . "</h3>",
        '#default_value' => self::getEmailSubject($k),
        '#title'=> t('Subject'),
      ];

      // body field
      $form[$k]['body'] = array(
        '#type' => 'text_format',
        '#format' => 'full_html',
        '#description' => $v['description'],
        '#suffix' => '<div>',
        '#default_value' => self::getEmailBody($k),
        '#title'=> t('Body'),
      );

      $i++;
    }

    // submit field
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    );

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('spn.settings');
    // save all required fields in our configuration
    foreach ($form_state->getValues() as $k => $v) {
      if(($k != 'submit') && ($k != 'form_build_id') && ($k != 'form_token') && ($k != 'form_id') && ($k != 'op')) {
        $email_configs = $form_state->getValue($k);
        $config->set($k, serialize($email_configs))->save();
      }
    }

    $message_expiry = $form_state->getValue('message_expiry');
    $message_validation = $form_state->getValue('message_validation');
    $message_error = $form_state->getValue('message_error');

    if(isset($message_expiry) && $message_expiry != '')
      $config->set('spn_message_expiry', $message_expiry)->save();
    else
      $config->set('spn_message_expiry', 'Link expired or signature had been already validated!')->save();

    if(isset($message_validation) && $message_validation != '')
      $config->set('spn_message_validation', $message_validation)->save();
    else
      $config->set('spn_message_validation', 'Your signature has been validated.')->save();

    if(isset($message_error) && $message_error != '')
      $config->set('spn_message_error', $message_error)->save();
    else
      $config->set('spn_message_error', 'Invalid link.')->save();

    $this->messenger()->addStatus($this->t('Your settings have been saved succesfully.', array()));

  }

  /**
   * Gets all the available email templates
   */
  public function getEmailsInfos() {
    $keys = [
      'petition_pending_confirmation' => [
        'title'=> t('Petition pending confirmation'),
        'description'=> t('A new petition has been signed, email sent to the subscriber to validate his signature.'),
      ],
      'petition_confirmed' => [
        'title'=> t('Petition confirmed'),
        'description'=> t('Sent when signature is validated, email sent to thank the subscriber.'),
      ],
    ];
    return $keys;
  }

  /**
   * Get email subject
   */
  public function getEmailSubject($token) {
    $email = unserialize((string) \Drupal::config('spn.settings')->get($token));
    return $email['subject'];
  }

  /**
   * Get email body
   */
  public function getEmailBody($token) {
    $email = unserialize((string) \Drupal::config('spn.settings')->get($token));
    return $email['body']['value'];
  }

}
