<?php

/**
 * @file
 * Contains \Drupal\spn\Entity\PetitionUser.
 */

namespace Drupal\spn\Entity;

use Drupal\Core\Database\Database;

/**
 * Petition User, used to recognize a single user across multiple signatures
 */
class PetitionUser {

  // variables
  private $user_id;
  private $email;
  private $name;
  private $surname;
  private $postal_code;

  /**
   * Constructor
   *
   *  @param string $email
   *    Email of the user
   *
   *  @param string $name
   *    Mame of the user
   *
   *  @param string $surname
   *    Last name of the user
   *
   *  @param string $postal_code
   *    Postal code of the user
   *
   *  @param integer $uid
   *    Id of the user
   *
   */
  public function __construct($email, $name, $surname, $postal_code = '', $uid = -1) {
    $this->user_id = $uid;
    $this->email = $email;
    $this->name = $name;
    $this->surname = $surname;
    $this->postal_code = $postal_code;
  }

  /**
   * Save the user data into sql
   */
  public function save() {

    // insert new user to database
    if ($this->exists()) {
      return false;
    }

    // fetch database and setup fields
    $database = \Drupal::database();
    $fields = [
      'email' => $this->email,
      'name' => $this->name,
      'surname' => $this->surname,
      'postal_code' => $this->postal_code,
    ];

    // insert and return true
    $database->insert('petition_user')->fields($fields)->execute();
    return true;

  }

  /**
   * Check if the user already exists
   */
  public function exists() {
     
    // fetch related entry in database
    // TODO: replace with drupal connection method
    $query = "SELECT user_id from petition_user WHERE email='". $this->email ."'";
    $rs = \Drupal::database()->query($query)->fetchAll();

    // return not found
    if (empty($rs)) {
      return false;
    }

    // return found
    return true;

  }

  /**
   * Load petition user by email
   */
  public function getPetitionUser($email) {

    // load petition user by email
    // TODO: replace with drupal connection method
    $query = "SELECT * from petition_user WHERE email='". $email ."'";
    $rs = \Drupal::database()->query($query)->fetchObject();

    // return false if none found
    if (empty($rs)) {
      return false;
    }

    // set current data if found
    $this->setUID($rs->user_id);
    $this->setEmail($rs->email);
    $this->setName($rs->name);
    $this->setSurname($rs->surname);
    $this->setPostalCode($rs->postal_code);

    // return success
    return true;

  }

  /**
   * Update the petition user UID field
   */
  public function fetchUID() {

    // getting petition user uid by email
    // TODO: replace with drupal connection method
    $query = "SELECT user_id from petition_user WHERE email='". $this->email ."'";
    $rs = \Drupal::database()->query($query)->fetchObject();

    // return false if none found
    if (empty($rs)) {
      return false;
    }

    // set UID and return success
    $this->setUID($rs->user_id);
    return true;

  }

  /**
   * Load petition by uid
   */
  public static function loadPetitionUser($uid) {
    
    // load petition user by uid
    // TODO: replace with drupal connection method
    $query = "SELECT * from petition_user WHERE user_id='". $uid ."'";
    $rs = \Drupal::database()->query($query)->fetchObject();

    // return null if none found
    if (empty($rs)) {
      return null;
    }

    // return the loaded petition user
    return new PetitionUser($rs->email, $rs->name, $rs->surname, $rs->postal_code, $rs->user_id);

  }

  /***************************************************************************/
  // Getters
  /***************************************************************************/

  public function getUID() {
    return $this->user_id;
  }

  public function getEmail() {
    return $this->email;
  }

  public function getName() {
    return $this->name;
  }

  public function getSurname() {
    return $this->surname;
  }

  public function getPostalCode() {
    return $this->postal_code;
  }
  
  /***************************************************************************/
  // Getters
  /***************************************************************************/

  public function setUID($uid) {
    $this->user_id = $uid;
  }

  public function setEmail($email) {
    $this->email = $email;
  }

  public function setName($name) {
    $this->name = $name;
  }

  public function setSurname($surname) {
    $this->surname = $surname;
  }

  public function setPostalCode($postal_code) {
    $this->postal_code = $postal_code;
  }

}