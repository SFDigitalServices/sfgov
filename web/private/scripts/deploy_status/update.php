<?php

require '../shared.php';

$workflow_type = $_POST['wf_type'];
echo "deployment status update for workflow '$workflow_type'...";

$secrets = _get_secrets(['github']);
$token = $secrets['github'];
$environment = $_ENV['PANTHEON_ENVIRONMENT'];
$repo = 'sfdigitalservices/sfgov';    # FIXME: needs to be generalized
$sha = `git rev-parse HEAD`;          # FIXME: not sure if this works?
$transient = false;

$api_url = "https://api.github.com/repos/$repo";
$api_headers = ['Accept' => 'application/vnd.github.ant-man-preview+json'];

# TODO: get this from the environment?
$target_url = sprintf(
  "https://%s-%s.pantheonsite.io",
  $_ENV['PANTHEON_ENVIRONMENT'],
  $_ENV['PANTHEON_SITE_NAME']
);

switch ($workflow_type) {
case 'deploy':
  # "Deploy code to Test or Live"

case 'deploy_product':
  # "Create site: dev"

case 'create_cloud_development_environment':
  # "Create Multidev environment"
  $transient = true;

default:
  echo "The workflow '$workflow_type' does not apply to GitHub deployment status.";
  exit(0);
}

$deployment = createDeployment([
  'ref' => $sha,
  'environment' => $environment,
  'auto_merge' => false,
  'transient_environment' => $transient,
  'required_contexts' => []
]);

$status = createDeploymentStatus($deployment['id'], [
  'state' => 'success',
  'target_url' => $target_url,
  'environment' => $environment,
  'environment_url' => $target_url,
  'auto_inactive' => true
]);

function createDeployment($data, $headers=[])
{
  $res = _post_json(
    "$api_url/deployments?access_token=$token",
    $api_headers + $headers,
    json_encode($data)
  );
  return json_decode($res['body']);
}

function createDeploymentStatus($deployment_id, $data, $headers=[])
{
  $res = _post_json(
    "$api_url/deployments/$deployment_id/statuses?access_token=$token",
    $api_headers + $headers,
    json_encode($data)
  );
  return json_decode($res['body']);
}

?>
