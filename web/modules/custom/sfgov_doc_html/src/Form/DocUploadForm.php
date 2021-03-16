<?php

namespace Drupal\sfgov_doc_html\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;
use PhpOffice\PhpWord\IOFactory;
use Vikpe\HtmlHeadingNormalizer;

/**
 * Class DocUploadForm.
 *
 * @ingroup digital_product
 */
class DocUploadForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'convert_doc_html_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['doc_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Choose a file to convert'),
      '#upload_location' => 'public://doc-to-html',
      '#upload_validators' => [
        'file_validate_extensions' => ['docx'],
      ],
      '#description' => $this->t('Please upload .docx files only.'),
      '#required' => TRUE,
    ];
    $options = [];
    foreach (sfgov_doc_html_supported_content_types() as $id => $info) {
      $options[$id] = $info['label'];
    }
    $form['destination'] = [
      '#type' => 'select',
      '#title' => $this->t('Content type'),
      '#options' => $options,
      '#description' => $this->t('Please choose a content type that will be prepopulated with the contents of the uploaded file.'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload & Convert'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $doc_file = $form_state->getValue('doc_file', 0);
    if (isset($doc_file[0]) && !empty($doc_file[0])) {
      $file = File::load($doc_file[0]);
      $uri = $file->getFileUri();
      $source = \Drupal::service('file_system')->realpath($uri);
      $doc_object = IOFactory::load($source);
      $info = $doc_object->getDocumentProperties();
      $html_object = IOFactory::createWriter($doc_object, 'HTML');
      if ($contents = $html_object->getContent()) {
        preg_match("/<body[^>]*>(.*?)<\/body>/is", $contents, $matches);
        if (!empty($matches[1])) {
          $clean_content = $matches[1];
        }
        else {
          $clean_content = $contents;
        }

        $normalized = HtmlHeadingNormalizer::demote($clean_content, 1);
        $normalized = $this->convertBase64($normalized);

        if (!empty($clean_content)) {
          \Drupal::database()->insert('sfgov_doc_html_files')
            ->fields([
              'fid'   => $file->id(),
              'title' => $info->getTitle(),
              'html'  => $normalized,
            ])
          ->execute();

          $url = Url::fromRoute('node.add', ['node_type' => $form_state->getValue('destination')], ['query' => ['converted_file' => $file->id()]]);
          $form_state->setRedirectUrl($url);
        }
      }
    }
  }

  /**
   * Convert `img` (base64) into file entity.
   */
  protected function convertBase64($content) {
    $dom = new \DOMDocument();
    $imageFound = FALSE;
    @$dom->loadHTML($content);
    if ($tags = $dom->getElementsByTagName('img')) {
      foreach ($tags as $tag) {
        $src = $tag->getAttribute('src');
        $ext = end(explode('/', (explode(';', $src))[0]));
        $base64 = end(explode(';base64,', $src));
        $base64 = str_replace(' ', '+', $base64);
        $base64 = str_replace('%0D%0A', '', $base64);

        if (!empty($base64)) {

          $hash = md5($base64);
          $fid = \Drupal::database()
              ->select('sfgov_doc_html_images', 't')
              ->fields('t', ['fid'])
              ->condition('t.hash', $hash)
              ->execute()
              ->fetchField();

          if ($file = File::load($fid)) {
            $tag->setAttribute('data-entity-type', 'file');
            $tag->setAttribute('data-entity-uuid', $file->uuid());
            $tag->setAttribute('src', file_url_transform_relative(file_create_url($file->getFileUri())));
          }
          else {
            $directory = 'public://inline-images/' . date("Y-m") . '/';
            \Drupal::service('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
            if ($file = file_save_data(base64_decode($base64),  $directory . uniqid() . '.' . $ext)) {
              $tag->setAttribute('data-entity-type', 'file');
              $tag->setAttribute('data-entity-uuid', $file->uuid());
              $tag->setAttribute('src', file_url_transform_relative(file_create_url($file->getFileUri())));
              \Drupal::database()
                ->insert('sfgov_doc_html_images')
                ->fields([
                  'fid' => $file->id(),
                  'hash' => $hash,
                ])
                ->execute();
              $imageFound = TRUE;
            }
          }
        }
      }
    }
    if ($imageFound) {
      return $dom->saveHTML();
    }
    else {
      return $content;
    }
  }

}
