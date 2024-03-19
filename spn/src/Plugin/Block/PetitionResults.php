<?php

namespace Drupal\spn\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Petition Results' Block.
 *
 * @Block(
 *   id = "spn_results",
 *   admin_label = @Translation("Petition Results Block"),
 *   category = @Translation("Petition"),
 * )
 */
class PetitionResults extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    
    // variables
    $route = \Drupal::routeMatch()->getRouteName();
    
    // if route is a node
    if ($route == 'entity.node.canonical') {
        
      // variables
      $signatures = [];
      $nid = \Drupal::routeMatch()->getParameter('node')->Id();
      $bundle = \Drupal::routeMatch()->getParameter('node')->getType();

      // if node is a petition then load the results
      if ($bundle == 'petition') {

        // establish a basic connection with all petitions
        $connection = \Drupal\Core\Database\Database::getConnection();
        $query = $connection->select('petition_signatures', 'petition');
        $query->condition('petition.nid', $nid);
        $query->condition('petition.validated', 1);
        
        // get the number of results and exit if empty
        $petition_count = clone $query;
        $query_result_count = $petition_count->countQuery()->execute()->fetchField();

        if ($query_result_count == 0) {
          return [];
        }
        
        // fetch all non-anonymous applications
        $query->condition('petition.anonymous_opinion', 0);
        $query->addField('petition', 'is_drupal_user');
        $query->addField('petition', 'drupal_uid', 'id_drupal');
        $query->addField('petition', 'uid', 'id_external');
        $query->addField('petition', 'comment');
        $query->orderBy('petition.timestamp', 'DESC');
        $query->range(0, 20);
        $query_result = $query->execute()->fetchAll();
        
        // make a list of all signatures
        if (!empty($query_result)) {
          foreach ($query_result as $user_key => $user_data) {
            
            // if the user is a drupal user get his data
            if($user_data->{'is_drupal_user'} == 1) {

              // get the user info
              $query_user = $connection->select('users_field_data', 'user');
              $query_user->condition('user.uid', $user_data->{'id_drupal'});
              // TODO: find solution to name and lastname combo (super combo!!! => https://youtu.be/rWWoLJ4wZ6Q?t=31)
              //$query_user->innerJoin('user__field_prenom', 'name', 'user.uid = name.entity_id');
              //$query_user->addField('user__field_prenom', 'field_prenom_value', 'name');
              //$query_user->innerJoin('user__field_nom', 'lastname', 'user.uid = lastname.entity_id');
              //$query_user->addField('user__field_nom', 'field_nom_value', 'lastname');
              $query_user->addField('user', 'mail');
              $user_result = $query_user->execute()->fetchObject();

              // add signature
              $signatures[] = [
                'name' => $user_result->{'name'},
                'lastname' => $user_result->{'lastname'},
                'mail' => $user_result->{'mail'},
                'comment' => $user_data->{'comment'},
              ];
              
            // if the user is not signed up get his data from our custom sql database
            } else {
              
              // get the user info
              $query_user = $connection->select('petition_user', 'user');
              $query_user->condition('user.user_id', $user_data->{'id_external'});
              $query_user->addField('user', 'name', 'name');
              $query_user->addField('user', 'surname', 'lastname');
              $query_user->addField('user', 'email', 'mail');
              $user_result = $query_user->execute()->fetchObject();
              
              // add signature
              $signatures[] = [
                'name' => $user_result->{'name'},
                'lastname' => $user_result->{'lastname'},
                'mail' => $user_result->{'mail'},
                'comment' => $user_data->{'comment'},
              ];
              
            }
            
          }
        }
        
        return [
          '#machine_name'     => $this->getConfiguration()['id'],
          '#signatures'       => $signatures,
          '#signature_count'  => $query_result_count,
          '#theme'            => 'spn_result__block',
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