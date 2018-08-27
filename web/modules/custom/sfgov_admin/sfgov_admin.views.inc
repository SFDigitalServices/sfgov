<?php

function sfgov_admin_views_data_alter(&$data) {
  $data['node__field_departments']['field_departments_target_id'] = array(
    'title' => t('Related Department(s)'),
    'filter' => array(
      'title' => t('Related Department(s)'),
      'help' => t('Specify a list of related departments'),
      'group' => 'Content',
      'field' => 'field_departments_target_id',
      'id' => 'sfgov_admin_content_type_filter',
      'argument' => [
        'content_type' => 'department',
      ],
    ),
  );

  $data['node__field_topics']['field_topics_target_id'] = array(
    'title' => t('Topic(s)'),
    'filter' => array(
      'title' => t('Topic(s)'),
      'help' => t('Specify a list of related topics.'),
      'group' => 'Content',
      'field' => 'field_topics_target_id',
      'id' => 'sfgov_admin_content_type_filter',
      'argument' => [
        'content_type' => 'topic',
      ],
    ),
  );
}