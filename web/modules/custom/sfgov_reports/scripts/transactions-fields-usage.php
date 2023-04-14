<?php

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\sfgov_utilities\Utility;

// field_step_online
// field_step_phone
// field_step_in_person
// email
// mail
// other_title ---\
//                            \___ grouped together
// other ----------/


try {
  $txNodes = Utility::getNodes('transaction'); // get all english published and unpublished

  $txs = [];

  foreach ($txNodes as $tx) {
    // if ($tx->id() == 215) {
    // if ($tx->isPublished()) {
      $txItem = [
        "nid" => $tx->id(),
        "title" => $tx->getTitle(),
      ];
  
      $online = $tx->get('field_step_online')->getValue();
      $phone = $tx->get('field_step_phone')->getValue();
      $in_person = $tx->get('field_step_in_person')->getValue();
      $email = $tx->get('field_step_email')->getValue();
      $mail = $tx->get('field_step_mail')->getValue();
      $other_title = $tx->get('field_step_other_title')->getValue();
      $other = $tx->get('field_step_other')->getValue();
  
  
  
      $txItem['online'] = markX($online);
      $txItem['phone'] = markX($phone);
      $txItem['in_person'] = markX($in_person);
      $txItem['email'] = markX($email);
      $txItem['mail'] = markX($mail);
      $txItem['other'] = markX($other);

      // // find a node that uses all of them
      // if (
      //   !empty($online) && 
      //   !empty($phone) &&
      //   !empty($in_person) &&
      //   !empty($email) &&
      //   !empty($mail) &&
      //   !empty($other)
      // ) {
      //   $txs[] = $txItem;
      // }

      $txItem["published"] = $tx->isPublished();
      $txs[] = $txItem;
    // }
  }

  echo json_encode($txs, JSON_PRETTY_PRINT);
  echo "\n";
  echo "count: " . count($txs) . "\n";
} catch (\Exception $e) {
  error_log($e->getMessage());
}

function markX($test) {
  return (!empty($test) ? "X" : "");
}