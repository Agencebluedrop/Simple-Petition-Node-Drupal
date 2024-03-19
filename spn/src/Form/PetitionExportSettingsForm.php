<?php
namespace Drupal\spn\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\StreamWrapper\PrivateStream;

class PetitionExportSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'spn_export_petitions';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = "Here you can export the signatures of your petitions in CSV format.";
    $base_path = PrivateStream::basePath();
    $array_petitions = [];
    $nids = \Drupal::entityQuery('node')->condition('type', 'petition')->accessCheck(false)->execute();
    $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);

    foreach ($nodes as $nodekey => $node) {
        $petition_nodes[$nodekey] = $node->label();
    }

    krsort($petition_nodes);

    $form['petition_node'] = [
        '#type' => 'select',
        '#title' => $this->t('Choose the petition to export:'),
        '#options' => $petition_nodes,
    ];

    $options = [];
    $options['validated_signatures'] = 'Validated signatures only';

    $form['validated_selected'] = [
    '#type' => 'checkboxes',
    '#options' => $options,
    '#title' => $this->t('Keep this field unchecked to export all the signatures.'),
    ];
    
    $form['actions']['#type'] = 'actions';
    $form['actions'] = [
        '#type' => 'button',
        '#value' => "Exporter",
        '#ajax' => [
            'callback' => '::export',
        ],
    ];

    $form['message'] = [
      '#type' => 'markup',
      '#markup' => '<br><div class="result_message"></div>',
    ];

    return $form;
  }

    /**
   * Validate the title and the checkbox of the form
   * 
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * 
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $petition = $form_state->getValue('petition_node');

    if ($petition == NULL || $petition == '' || !isset($petition) || empty($petition)) {
      // Set an error for the form element with a key of "title".
      $form_state->setErrorByName('petition_node', $this->t('Please choose a petition before launching the export.'));
    }

  }

    /**
   * Submitting the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function export(array &$form, FormStateInterface $form_state) {

    $petition = $form_state->getValue('petition_node');
    $validated = $form_state->getValue('validated_selected');

    //print_r($validated);
    //exit;
    $date_formatted = date ("Ymd-His");
    
    $csv_filename = 'petition_' . $petition. '_' . $date_formatted . '.csv';
    
    $base_path = PrivateStream::basePath();

    if ($wrapper = \Drupal::service('stream_wrapper_manager')->getViaUri('private://')) {
		  $base_path = $wrapper->realpath();
	  }
    try {
      $handle = fopen($base_path ."/". $csv_filename, 'w+');
      if ( !$handle ) {
        throw new \Exception('File open failed.');
      } 
      $file_link = "<h3><a href=/system/files/". $csv_filename ." download>Download your file by clicking here</a></h3><br>";
      //$csv_filename = 'petition_' . $petition. '_' . $date_formatted . '.csv';
      //$handle = fopen('php://temp', 'w+');
      //echo "file ----> link";
      //print_r($file_link);
      $csv_header = [
          'Name',
          'Surname',
          'Email',
          'Drupal Email',
          'Postal Code',
          'Comment',
          'Date',
          'Validated by email ?',
          'Anonymous opinion ?'
      ];
      // Add the header as the first line of the CSV.
      fputcsv($handle, $csv_header);
  
      $database = \Drupal::database();
      $query = $database->select('petition_signatures', 'ps');
      $query->leftJoin('petition_user', 'pu', 'user_id=uid');
      $query->leftJoin('users_field_data', 'ufd', 'drupal_uid=ufd.uid');
      $query->addField('pu', 'name');
      $query->addField('pu', 'surname');
      $query->addField('pu', 'email');
      $query->addField('ufd', 'mail');
      $query->addField('pu', 'postal_code');
      $query->addField('ps', 'comment');
      $query->addField('ps', 'validated');
      $query->addField('ps', 'anonymous_opinion');
      $query->addField('ps', 'timestamp');
      $query->condition('nid', $petition);
      $query->orderBy('timestamp' , 'DESC'); 
      if($validated['validated_signatures'] != 0){
          $query->condition('validated', 1);
      }
      $results = $query->execute()->fetchAll();
      $resultcount = count($results);
      foreach ($results as $record) {
          $data = $this->csvBuildLine($record);
  
          // Add the data we exported to the next line of the CSV>
          fputcsv($handle, array_values($data));
      }
      
      // Reset where we are in the CSV.
      rewind($handle);
      
      // Retrieve the data from the file handler.
      $csv_data = stream_get_contents($handle);
  
      // Close the file handler since we don't need it anymore.  We are not storing
      // this file anywhere in the filesystem.
      fclose($handle);
      
      // This is the "magic" part of the code.  Once the data is built, we can
      // return it as a response.
      //$response = new Response();
      
      // By setting these 2 header options, the browser will see the URL
      // used by this Controller to return a CSV file called $csv_filename.
      //$response->headers->set('Content-Type', 'text/csv');
      //$response->headers->set('Content-Disposition', 'attachment; filename="'. $csv_filename .'"');
      // This line physically adds the CSV data we created 
      //$response->setContent($csv_data);
  
      $response = new AjaxResponse();
      $response->addCommand(
       new HtmlCommand(
         '.result_message',
          $file_link
       )
      );
  
      $message_highlighted = '<div role="contentinfo" aria-label="Message d\'état" class="messages messages--status">
          <h2 class="visually-hidden">Status message</h2>
          Your file has been created successfully you can download it by clicking on the link below.
      </div>';
      $response->addCommand(
        new HtmlCommand(
          '.region-highlighted',
           $message_highlighted
        )
       );
  
      return $response;
    }catch (\Exception $e){
      \Drupal::logger('spn')->error('Exception when trying to create file in the private directory, please make sure your private file system is configured correctly: ' . $e->getMessage());
      $error_message = '<div role="contentinfo" aria-label="Error Message" class="messages messages--error">
          <h2 class="visually-hidden">Error message</h2>
          Error while creating your file, please check the logs.
      </div>';
      $error_response = new AjaxResponse();
      $error_response->addCommand(
        new HtmlCommand(
          '.region-highlighted',
           $error_message
        )
       );
      return $error_response;
    }
  }

  private function csvBuildLine($record){
      $data = [
        'nom' => $record->name,
        'surname' => $record->surname,
        'email' => $record->email,
        'mail' => $record->mail,
        'postal_code' => $record->postal_code,
        'comment' => $record->comment,
        'timestamp' => date("d/m/Y à H:i:s", $record->timestamp),
        'validated' => $record->validated,
        'anonymous_opinion' => $record->anonymous_opinion,
      ];
    return $data;
  }
}
