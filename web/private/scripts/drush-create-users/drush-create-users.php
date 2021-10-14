<?php
  require dirname(__DIR__) . '/../shared.php';

  // get the roles
  echo "get roles\n";

  // ob_start();
  // passthru('drush role:list --fields=label --format=json');
  // $roles = ob_get_contents();
  // ob_end_clean();
  // $json = json_decode($roles);

  echo "roles\n";

  $json = (object) [
    'anonymous' => [
      'label' => 'Anonymous user'
    ],
    'authenticated' => [
      'label' => 'Authenticated user'
    ],
    'writer' => [
      'label' => 'Writer'
    ],
    'publisher' => [
      'label' => 'Writer'
    ],
    'digital_services' => [
      'label' => 'Writer'
    ],
    'administrator' => [
      'label' => 'Administrator'
    ],
  ];

  print_r($json);

  $pw = _get_secrets(['drush_pw'])['drush_pw'];
  $debug = '';

  foreach ($json as $role => $val) {
    if ($role !== 'authenticated' && $role !== 'anonymous') {
      $machineNameRole = $role; 
      $normalizedRole = strtolower(str_replace('_', '', $role));
      $user = 'test.' . $normalizedRole;
      $email = $user . '@test.com';
      $exitStatus = null;
      $output = null;
      exec("drush user:create ${user} --mail=\"${email}\" --password=\"${pw}\"", $output, $exitStatus);
      if ($exitStatus == 0) {
        exec("drush user-add-role \"${machineNameRole}\" ${user}");
        $msg = "user ${user} created with role ${machineNameRole}\n";
        echo $msg;
        $debug .= "  - " . $msg;
      }
    }
  }  

  _test_hook_slack_notification("create users: \n" . (strlen($debug) > 0 ? $debug : '  no users created'));
