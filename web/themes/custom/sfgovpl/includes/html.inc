<?php

/**
 * @file
 * Preprocess functions for the 'html' theme hooks.
 */

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Implements hook_preprocess_HOOK().
 */
function sfgovpl_preprocess_html(&$variables) {
  // Make sure we have a node object that works on revisions, diffs, etc.
  _sfgovpl_node_object($variables);

  if (!empty($variables['node']) && $variables['node'] instanceof NodeInterface) {
    $node = $variables['node'];
    $node_type = $node->bundle();

    // Send these variables to html.html.twig as well.
    $variables['node'] = $node;
    $variables['node_type'] = $node_type;

    // For metadata.
    switch ($node_type) {
      case 'department':
        $metaDeptPhone = [
          '#tag' => 'meta',
          '#attributes' => [
            'name' => 'departmentPhone',
            'content' => '',
          ]
        ];
        $metaDeptAddress = [
          '#tag' => 'meta',
          '#attributes' => [
            'name' => 'departmentAddress',
            'content' => '',
          ]
        ];

        if (count($node->get('field_phone_numbers')->getValue()) > 0) {
          $paragraphPhoneId = $node->get('field_phone_numbers')->getValue()[0]['target_id'];
          $phone = Paragraph::load($paragraphPhoneId);
          $metaDeptPhone['#attributes']['content'] = $phone->field_tel->value;
        }

        if (count($node->get('field_address')->getValue()) > 0) {
          $paragraphAddressId = $node->get('field_address')->getValue()[0]['target_id'];
          $paragraphAddress = Paragraph::load($paragraphAddressId);
          $address = $node->get('field_address')->referencedEntities()[0];
          $metaDeptAddress['#attributes']['content'] = $address->field_address->address_line1;
        }

        $variables['page']['#attached']['html_head'][] = [$metaDeptPhone, 'departmentPhone'];
        $variables['page']['#attached']['html_head'][] = [$metaDeptAddress, 'departmentAddress'];

        // Add the Mastodon verification to the Head if a url is present.
        if ($node->hasField('field_social_media')) {
          if (count($node->get('field_social_media')->getValue()) > 0) {
            $paragraphSocialMediaId = $node->get('field_social_media')->getValue()[0]['target_id'];
            $paragraphSocialMedia = Paragraph::load($paragraphSocialMediaId);
            if ($paragraphSocialMedia->hasField('field_mastodon')) {
              $mastodon_url = $paragraphSocialMedia->get('field_mastodon')
                ->getValue();
              if (isset($mastodon_url[0]['uri'])) {
                $mastadonVerificationLink = [
                  '#tag' => 'link',
                  '#attributes' => [
                    'href' => $mastodon_url[0]['uri'],
                    'rel' => 'me'
                  ]
                ];
                $variables['page']['#attached']['html_head'][] = [
                  $mastadonVerificationLink,
                  'mastadonVerificationLink'
                ];
              }
            }
          }
        }
        break;

      case 'transaction':
        $relatedDepts = $node->get('field_departments')->getValue();
        $transaction = [
          'related_depts' => [],
        ];
        foreach ($relatedDepts as $relatedDept) {
          $relatedDeptId = $relatedDept['target_id'];
          $relatedDeptNode = Node::load($relatedDeptId);
          $relatedDeptInfo = [
            'id' => $relatedDeptId,
            'title' => $relatedDeptNode ? $relatedDeptNode->getTitle() : '',
          ];
          $transaction['related_depts'][] = $relatedDeptInfo;
        }
        $metaTx = [
          '#tag' => 'meta',
          '#attributes' => [
            'name' => 'transaction',
            'content' => json_encode($transaction, JSON_HEX_QUOT),
          ],
        ];

        $metaTxRelatedDept = [
          '#tag' => 'meta',
          '#attributes' => [
            'name' => 'transactionRelatedDept',
            'content' => '',
          ]
        ];

        if (count($relatedDepts) > 0) {
          $oneRelatedDept = Node::load($relatedDepts[0]['target_id']);
          $metaTxRelatedDept['#attributes']['content'] = $oneRelatedDept ? $oneRelatedDept->getTitle() : '';
        }
        $variables['page']['#attached']['html_head'][] = [$metaTx, 'transaction'];
        $variables['page']['#attached']['html_head'][] = [$metaTxRelatedDept, 'transactionRelatedDept'];
        break;

      case 'campaign':
        // Add class page-campaign-[theme] to body tag.
        $theme = $node->field_campaign_theme->entity;
        if (!empty($theme)) {
          $theme_class = 'page-campaign-' . \Drupal::service('pathauto.alias_cleaner')->cleanString($theme->label());
          $variables['attributes']['class'][] = $theme_class;
        }
        break;
    }
  }

  /*   Translations logic.

     Add the URLs of the current page translations (or URL in different languages
     if no translation available). The script "translate.js" would then redirect
     to the given URL instead of translating on the fly.
     This is needed because translated nodes are wrapped in a "notranslate"
     class, but the rest of the page is ran through Gtranslate to translate
     header, footer, etc. If we just translated on the fly we could end up,
     for example, with a translated node in spanish (wrapped in "notranslate",
     and if the user translated into an non-existing translation the node would
     not be translated.
     Redirecting to the correct URL for the translation ensures the scenario
     described above never happens.
  */

  // For translations, if it is a node page.
  $languageManager = \Drupal::languageManager();
  $languages = $languageManager->getLanguages();
  $currentLanguage = $languageManager->getCurrentLanguage()->getId();
  $currentRoute = \Drupal::service('current_route_match');
  $routeParameters = $currentRoute->getParameters()->all();
  $gTranslatePrefixMap = [
    'fil' => 'tl',
    'zh-hant' => 'zh-TW',
  ];
  $pageInfo = [];
  $pageInfo['current_language'] = isset($gTranslatePrefixMap[$currentLanguage]) ? $gTranslatePrefixMap[$currentLanguage] : $currentLanguage;
  $isFront = \Drupal::service('path.matcher')->isFrontPage();

  if (!empty($variables['node']) && $variables['node'] instanceof NodeInterface && $currentRoute->getRouteName() == 'entity.node.canonical') {
    $theNode = $variables['node'];
    $pageInfo['nid'] = $theNode->id();
    $pageInfo['nurl'] = $theNode->toUrl()->toString();
    $langs = $theNode->getTranslationLanguages();
    $pageInfo['translations'] = [];
    foreach ($languages as $langCode => $language) {
      $nodeUrl = isset($langs[$langCode]) ?
        $theNode->getTranslation($langCode)->toUrl()->toString() :
        Url::fromRoute($currentRoute->getRouteName(), [
          'node' => $theNode->id(),
        ], ['language' => $language])->toString();
      $pageInfo['translations'][] = [
        'lang' => isset($gTranslatePrefixMap[$langCode]) ? $gTranslatePrefixMap[$langCode] : $langCode,
        'turl' => $isFront ?
          Url::fromRoute('<front>', [], ['language' => $language])->toString() :
          $nodeUrl,
          'status' => isset($langs[$langCode]) ?  $theNode->getTranslation($langCode)->isPublished() : false,
      ];
    }
  }
  $variables['page']['#attached']['drupalSettings']['sfgov_translations']['page'] = $pageInfo;

  // For translations, if it is a views page.
  if (!empty($currentRoute->getRouteName()) && $currentRoute->getRouteName() != 'entity.node.canonical') {
    $routeParameters = $currentRoute->getRawParameters()->all();
    foreach ($languages as $langCode => $language) {
      $pageInfo['translations'][] = [
        'lang' => isset($gTranslatePrefixMap[$langCode]) ? $gTranslatePrefixMap[$langCode] : $langCode,
        'turl' => Url::fromRoute($currentRoute->getRouteName(), $routeParameters, [
          'language' => $language,
          'query' => \Drupal::request()->query->all(),
        ])->toString(),
        'status' => isset($langs[$langCode]) ?  $theNode->getTranslation($langCode)->isPublished() : false,
      ];
    }
    $variables['page']['#attached']['drupalSettings']['sfgov_translations']['page'] = $pageInfo;
  }
}
