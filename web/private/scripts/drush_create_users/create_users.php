<?php
  require dirname(__DIR__) . '/../shared.php';

  // get the roles
  ob_start();
  passthru('drush role:list --fields=label --format=json');
  $roles = ob_get_contents();
  ob_end_clean();
  $json = json_decode($roles);

  $debug = '';

  foreach ($json as $role => $val) {
    if ($role !== 'authenticated' && $role !== 'anonymous') {
      $machineNameRole = $role; 
      $normalizedRole = strtolower(str_replace('_', '', $role));
      $user = 'test.' . $normalizedRole;
      $email = $user . '@test.com';
      $exitStatus = null;
      $output = null;
      exec("drush user:create ${user} --mail=\"${email}\" --password=\"password\"", $output, $exitStatus);
      if ($exitStatus == 0) {
        exec("drush user-add-role \"${machineNameRole}\" ${user}");
        $debug .= "  - user ${user} created with role {$machineNameRole}\n";
      }
    }
  }  

  _test_hook_slack_notification("create users: \n" . (strlen($debug) > 0 ? $debug : '  no users created'));
