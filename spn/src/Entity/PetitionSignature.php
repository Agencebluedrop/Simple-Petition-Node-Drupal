<?php

/**
 * @file
 * Contains \Drupal\spn\Entity\PetitionSignature.
 */

namespace Drupal\spn\Entity;

use Drupal\Core\Database\Database;

/**
 * Petition Signature, represents a signature on a petition made by a Petition User
 */
class PetitionSignature {

  // variables
  private $sid;
  private $nid;
  private $uid;
  private $drupal_uid;
  private $is_drupal_user;
  private $anonymous_opinion;
  private $validated;
  private $comment;
  private $token;
  private $timestamp;

  /**
   * Constructor
   *
   *  @param integer $nid
   *    Instance ID
   *
   *  @param integer $uid
   *    Petition User ID if the user id not a drupal user
   *
   *  @param integer $drupal_uid
   *    User ID of the default drupal User Entity
   *
   *  @param boolean $is_drupal_user
   *    Boolean value to determine which ID to use
   *
   *  @param boolean $anonymous_opinion
   *    Option for user to remain anonymous
   *
   *  @param boolean $validated
   *    True if the user has validated his vote through email
   *
   *  @param string $comment
   *    (Optional) An additional comment the user may leave
   *
   *  @param hash $token
   *    Token for the current user
   *
   *  @param date $timestamp
   *    Date of signature
   */
  public function __construct($nid = '', $uid = '', $drupal_uid = '', $is_drupal_user = '', $anonymous_opinion = '', $validated = '', $comment = '', $token = '', $timestamp = '', $sid = -1) {
      $this->sid = $sid;
      $this->nid = $nid;
      $this->uid = $uid;
      $this->drupal_uid = $drupal_uid;
      $this->is_drupal_user = $is_drupal_user;
      $this->anonymous_opinion = $anonymous_opinion;
      $this->validated = $validated;
      $this->comment = $comment;
      $this->token = $token;
      $this->timestamp = $timestamp;
  }

  /**
   * Save the signature into sql
   */
  public function save() {

    // fetch database and setup fields
    $database = \Drupal::database();

    $fields = [
      'nid' =>$this->nid,
      'uid' =>$this->uid,
      'drupal_uid' =>$this->drupal_uid,
      'is_drupal_user' =>$this->is_drupal_user,
      'anonymous_opinion' =>$this->anonymous_opinion,
      'validated' =>$this->validated,
      'comment' =>$this->comment,
      'token' =>$this->token,
      'timestamp' =>$this->timestamp,
    ];

    // insert signature into database
    $database->insert('petition_signatures')->fields($fields)->execute();

  }

  /**
   * Get a Petition Signature instance by token
   */
  public function getSignatureByToken($token) {

    // load signature by token
    // TODO: replace with drupal connection method
    $query = "SELECT * FROM petition_signatures WHERE token='". $token . "'";
    $rs = \Drupal::database()->query($query)->fetchObject();

    // return false if not found
    if (empty($rs)) {
      return false;
    }

    // update all current fields
    $this->setSID($rs->sid);
    $this->setNID($rs->nid);
    $this->setUID($rs->uid);
    $this->setDrupalUID($rs->drupal_uid);
    $this->setIsDrupalUser($rs->is_drupal_user);
    $this->setAnonOpinion($rs->anonymous_opinion);
    $this->setValidated($rs->validated);
    $this->setComment($rs->comment);
    $this->setToken($rs->token);
    $this->setTimestamp($rs->timestamp);

    // return success
    return true;

  }

  /**
   * Update the validated field (when the user confirms by email)
   */
  public function updateValidated() {

    // update validated field for current signature
    $connection = \Drupal::database();
    $validate = $connection->update('petition_signatures')->fields([
      'validated' => $this->validated,
    ])->condition('sid', $this->sid, '=')->execute();

  }

  /**
   * Get if signature is validated
   */
  public function ifValidated() {
    if($this->getValidated() == 1) {
      return true;
    }
    return false;
  }

  /**
   * Set instance as validated
   */
  public function validate() {
    $this->setValidated(1);
  }

  /**
   * Check if signature exists
   */
  public function ifExists() {

    // get signature from sql based on current info
    $query = "SELECT sid from petition_signatures WHERE (uid='" . $this->uid ."' AND uid IS NOT NULL AND nid ='" . $this->nid . "') OR (drupal_uid <> 0 AND drupal_uid ='" . $this->drupal_uid . "' AND nid ='" . $this->nid . "')";
    $rs = \Drupal::database()->query($query)->fetchAll();

    // if not found return false
    if (empty($rs)) {
      return false;
    }

    // return true if found
    return true;

  }

  /***************************************************************************/
  // Getters
  /***************************************************************************/

  public function getSID() {
    return $this->sid;
  }

  public function getNID() {
    return $this->nid;
  }

  public function getUID() {
    return $this->uid;
  }

  public function getDrupalUID() {
    return $this->drupal_uid;
  }

  public function getIsDrupalUser() {
    return $this->is_drupal_user;
  }

  public function getAnonOpinion() {
    return $this->anonymous_opinion;
  }

  public function getValidated() {
    return $this->validated;
  }

  public function getComment() {
    return $this->comment;
  }

  public function getToken() {
    return $this->token;
  }

  public function getTimestamp() {
    return $this->timestamp;
  }

  /***************************************************************************/
  // Setters
  /***************************************************************************/

  public function setSID($sid) {
    $this->sid = $sid;
  }

  public function setNID($nid) {
    $this->nid = $nid;
  }

  public function setUID($uid) {
    $this->uid = $uid;
  }

  public function setDrupalUID($duid) {
    $this->drupal_uid = $duid;
  }

  public function setIsDrupalUser($is_drupal_user) {
    $this->is_drupal_user = $is_drupal_user;
  }

  public function setAnonOpinion($anonymous_opinion) {
    $this->anonymous_opinion = $anonymous_opinion;
  }

  public function setValidated($validated) {
    $this->validated = $validated;
  }

  public function setComment($comment) {
    $this->comment = $comment;
  }

  public function setToken($token) {
    $this->token = $token;
  }

  public function setTimestamp($timestamp) {
    $this->timestamp = $timestamp;
  }

}