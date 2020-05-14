<?php

namespace Drupal\sfgov_utilities;

use GuzzleHttp\ClientInterface;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Serialization\Json;

/**
 * Class VideoService.
 */
class VideoService {

  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;
  /**
   * Drupal\Component\Serialization\SerializationInterface definition.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializationJson;
  /**
   * Constructs a new VideoService object.
   */
  public function __construct(ClientInterface $http_client, SerializationInterface $serialization_json) {
    $this->httpClient = $http_client;
    $this->serializationJson = $serialization_json;
  }

  /**
   * Extract video id from video embed URL.
   *
   * @param $video_url
   *   The Youtube or Vimeo URL.
   * @return mixed|null
   *   The video id
   */
  public function getVideoId($video_url) {
    $url = UrlHelper::parse($video_url);
    if (isset($url['query']) && isset($url['query']['v'])) {
      return $url['query']['v'];
    }
    return NULL;
  }

  /**
   * Get transcript by language.
   * TODO: detect site language to get the right transcript.
   *
   * @param $video_id
   * @param string $languageCode
   *
   * @return array
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getYoutubeTranscript($video_id, $languageCode = 'en') {
    $caption = $this->getYoutubeCaptionTrack($video_id, $languageCode);
    if (is_null($caption)) return [];

    $caption_track_url = $caption['baseUrl'];

    $request = $this->httpClient->request('GET', $caption_track_url);
    $content = $request->getBody()->getContents();

    $data = simplexml_load_string($content);

    $captions = [];
    for ($i = 0; $i < $data->count(); $i++) {
      $item = $data[0]->text[$i];
      $captions[$i] = [
        'text' => $item->__toString(),
        'start' => $item['start']->__toString(),
        'dur' => $item['dur']->__toString(),
      ];
    }

    return $captions;
  }

  /**
   * Extract video information to get caption tracks.
   *
   * @param $video_id
   * @param $languageCode
   *
   * @return mixed|null
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function getYoutubeCaptionTrack($video_id, $languageCode) {
    $video_info_url = "https://www.youtube.com/get_video_info?&video_id=" . $video_id;
    $request = $this->httpClient->request('GET', $video_info_url);
    $contents = $request->getBody()->getContents();

    parse_str($contents, $video_info_array);
    $response = $video_info_array['player_response'];
    $json = JSON::decode($response);
    $caption_tracks = $json['captions']['playerCaptionsTracklistRenderer']['captionTracks'];

    $caption_track = array_filter($caption_tracks, function($track) {
      return $track['languageCode'];
    });

    if (!empty($caption_track)) {
      return reset($caption_track);
    } else {
      return NULL;
    }
  }

}
