<?php

namespace Drupal\sfgov_video;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use GuzzleHttp\ClientInterface;
use Drupal\Component\Serialization\SerializationInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
   * Get Video title
   * @param $video_id
   *
   * @return mixed
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getVideoTitle($video_id) {
    $metadata = $this->getYoutubeMetadata($video_id);
    return $metadata['videoDetails']['title'];
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
    $metadata = $this->getYoutubeMetadata($video_id, $languageCode);
    $caption = $metadata['captionTracks'];
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
   * Get Youtube metadata: video title, caption tracks.
   *
   * @param $video_id
   * @param string $languageCode
   *
   * @return array
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getYoutubeMetadata($video_id, $languageCode = 'en') {
    // Retrieve the response from the API.
    $youtube_api_url = 'https://www.youtube.com/youtubei/v1/player?key=AIzaSyAO_FJ2SlqU8Q4STEHLGCilw_Y9_11qcW8';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $youtube_api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{  "context": {    "client": {      "hl": "en",      "clientName": "WEB",      "clientVersion": "2.20210721.00.00",      "clientFormFactor": "UNKNOWN_FORM_FACTOR",   "clientScreen": "WATCH",      "mainAppWebInfo": {        "graftUrl": "/watch?v=' . $video_id . '",           }    },    "user": {      "lockedSafetyMode": false    },    "request": {      "useSsl": true,      "internalExperimentFlags": [],      "consistencyTokenJars": []    }  },  "videoId": "' . $video_id . '",  "playbackContext": {    "contentPlaybackContext": {        "vis": 0,      "splay": false,      "autoCaptionsDefaultOn": false,      "autonavState": "STATE_NONE",      "html5Preference": "HTML5_PREF_WANTS",      "lactMilliseconds": "-1"    }  },  "racyCheckOk": false,  "contentCheckOk": false}');
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);

    if (json_decode($result)->playabilityStatus->status != 'OK') {
      throw new NotFoundHttpException();
    }

    $json = JSON::decode($result);

    $caption_tracks = isset($json['captions']) ? $json['captions']['playerCaptionsTracklistRenderer']['captionTracks'] : [];

    return [
      'captionTracks' => $this->getYoutubeCaptionTrack($caption_tracks, $languageCode),
      'videoDetails' => $json['videoDetails']
    ];
  }

  /**
   * Get caption track of a specific language.
   *
   * @param $caption_tracks
   * @param $languageCode
   *
   * @return mixed|null
   */
  private function getYoutubeCaptionTrack($caption_tracks, $languageCode) {
    $caption_track = array_filter($caption_tracks, function($track) use ($languageCode) {
      return $track['languageCode'] == $languageCode;
    });

    return !empty($caption_track) ? reset($caption_track) : NULL;
  }

}
