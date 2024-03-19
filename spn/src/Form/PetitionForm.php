<?php

/**
 * @file
 * Contains \Drupal\spn\Form\PetitionForm.
 */

namespace Drupal\spn\Form;

use Drupal\user\Entity\User;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\node\Entity\Node;
use Drupal\spn\Entity\PetitionSignature;
use Drupal\spn\Entity\PetitionUser;

/**
 * Petition form the user fills
 */
class PetitionForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'petition_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // variables
    $uid = \Drupal::currentUser()->id();
    $user = User::load($uid);
    $petition = \Drupal::routeMatch()->getParameter('node');

    // if for some reason the node is empty or not a petition throw error
    if (empty($petition) || ($petition->bundle() != 'petition')) {
      throw \Exception("Error, current node is not a petition");
    }

    $email = ($uid == 0) ? '' : $user->get('mail')->value;
    //Later, add more fields to fill automatically if Drupal user is authenticated (profile name...)

    // email field
    $form['email'] = [
      '#type' => 'email',
      '#title' => t('Email'),
      '#required' => true,
      '#default_value' => $email,
    ];

    // name field
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#required' => true,
    ];

    // lastname field
    $form['surname'] = [
      '#type' => 'textfield',
      '#title' => t('Surname'),
      '#required' => true,
    ];

    // postal code
    $form['postal_code'] = [
      '#type' => 'textfield',
      '#title' => t('Postal code'),
      '#required' => false,
    ];

    // comment field
    $form['comment'] = [
      '#type' => 'textfield',
      '#title' => t('Comment'),
      '#required' => false,
    ];

    // option for user to remain anonymous
    $form['anonymous_sign'] = [
      '#type' => 'checkbox',
      '#title' => t('Sign anonymously'),
    ];

    // hidden field with the user id
    $form['drupal_uid'] = [
      '#type' => 'hidden',
      '#value' => $uid,
    ];

    // hidden field with info if the current user is signed up to the website
    $form['is_drupal_user'] = [
      '#type' => 'hidden',
      '#value' => ($uid == 0) ? 0:1,
    ];

    // captcha
    // $form['captcha'] = [
    //   '#type' => 'captcha',
    //   '#captcha_type' => 'recaptcha/reCAPTCHA',
    // ];

    // submit field
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Sign petition'),
      '#button_type' => 'primary',
    ];

    // add theme settings
    $form['#theme'] = 'spn__form';

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // variables
    $petition = \Drupal::routeMatch()->getParameter('node');
    $flag = true;

    // get values needed for the user type case
    switch ($form_state->getValue('is_drupal_user')) {
      // case user is a drupal user
      case 0:

        // create the petition info
        $user = new PetitionUser(
          $form_state->getValue('email'),
          $form_state->getValue('name'),
          $form_state->getValue('surname'),
          $form_state->getValue('postal_code')
        );

        // if the user is already saved in our database get his instance
        if ($user->exists()) {
          $user->getPetitionUser($form_state->getValue('email'));

        // if the user isn't saved yet, create the petition user element
        } else {
          $user->save();
          $user->fetchUID();
        }

        // create the petition signature
        $signature = new PetitionSignature(
          $petition->id(),
          $user->getUID(),
          0,
          0,
          $form_state->getValue('anonymous_sign'),
          0,
          $form_state->getValue('comment'),
          self::getToken(),
          time()
        );

        // if the user already signed the petition update the flag
        if ($signature->ifExists()) {
          $flag = false;

        // if not create the signature
        } else {
          $signature->save();
          $user_mail = $user->getEmail();
        }

        break;

      // case where user is not signed up yet
      case 1:

        // create a petition signature
        $signature = new PetitionSignature(
          $petition->id(),
          null,
          $form_state->getValue('drupal_uid'),
          1,
          $form_state->getValue('anonymous_sign'),
          0,
          $form_state->getValue('comment'),
          self::getToken(),
          time()
        );

        // if the user already signed the petition update the flag
        if ($signature->ifExists()) {
          $flag = false;

        // if not create the signature
        } else {
          $signature->save();
          $uid = \Drupal::currentUser()->id();
          $user = User::load($uid);
          $user_mail = $user->get('mail')->value;
        }

        break;

      default:

    }

    // if we need to create the signature
    if ($flag) {

      // variables
      $host = \Drupal::request()->getSchemeAndHttpHost();
      //$petition_link = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $petition->id());
      $validation_link = $host . '/petition/node/' . $signature->getToken() . '/' . $petition->id();

      // setup mail data
      $mailManager = \Drupal::service('plugin.manager.mail');
      $module = 'spn';
      $mail_key = 'petition_pending_confirmation';
      $to = $user_mail;
      $language_code = \Drupal::languageManager()->getCurrentLanguage()->getId();

      $params = [
        'entity_url' => $validation_link,
        'entity_title' => $petition->getTitle(),
        'node_url' => $host . '/node/' . $petition->id(),
        'email_content' => $petition->get('field_email_validation')->getValue(),
        'email_subject' => $petition->get('field_email_validation_subject')->getValue(),
      ];
      $reply = null;
      $send = true;

      // send mail and post notification
      $result = $mailManager->mail($module, $mail_key, $to, $language_code, $params, $reply, $send);
      $this->messenger()->addStatus($this->t('@emp_name, A confirmation link has been sent to you by email to the following adress: @mail', array('@emp_name' => $form_state->getValue('name'), '@mail'=>$user_mail)));

    // if signature already exists alert the user
    } else {
      $this->messenger()->addStatus($this->t('@emp_name, You have already signed this petition!', array('@emp_name' => $form_state->getValue('name'))));
    }

  }

  /**
   * Generate token
   */
  public static function getToken() {
    $length = 32;
    return bin2hex(random_bytes($length));
  }

}
