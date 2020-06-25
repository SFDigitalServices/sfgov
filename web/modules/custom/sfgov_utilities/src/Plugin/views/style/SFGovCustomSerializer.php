<?php

  namespace Drupal\sfgov_utilities\Plugin\views\style;

  use Drupal\rest\Plugin\views\style\Serializer;
  /**
   * Custom serializer
   *
   * @ViewsStyle(
   *   id = "sfgov_custom_serializer",
   *   title = @Translation("SF.gov custom serializer"),
   *   help = @Translation("Serializes views row data using the Serializer component."),
   *   display_types = {"data"}
   * )
   */
  class SFGovCustomSerializer extends Serializer {
    public function render() {
      $rows = [];
      // If the Data Entity row plugin is used, this will be an array of entities
      // which will pass through Serializer to one of the registered Normalizers,
      // which will transform it to arrays/scalars. If the Data field row plugin
      // is used, $rows will not contain objects and will pass directly to the
      // Encoder.
      foreach ($this->view->result as $row_index => $row) {
        $this->view->row_index = $row_index;
        $row_render = $this->view->rowPlugin->render($row);
        foreach($row_render as $key => $value) {
          if($key == 'field_formio_render_options') {
            $the_row[$key] = json_decode($value, TRUE);
          } else {
            $the_row[$key] = $value;
          }
        }
        $rows[] = $the_row;
      }
  
      unset($this->view->row_index);
      // Get the content type configured in the display or fallback to the
      // default.
      if ((empty($this->view->live_preview))) {
        $content_type = $this->displayHandler->getContentType();
      }
      else {
        $content_type = !empty($this->options['formats']) ? reset($this->options['formats']) : 'json';
      }
      return $this->serializer->serialize($rows, $content_type, ['views_style_plugin' => $this, 'json_encode_options' => JSON_UNESCAPED_SLASHES]);
    }
  }
