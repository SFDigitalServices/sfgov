<?php

namespace Drupal\sfgov_video;
use Drupal\Component\Utility\UrlHelper;
use GuzzleHttp\ClientInterface;
use Drupal\key\KeyRepositoryInterface;
use Exception;
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
   * Constructs a new VideoService object.
   */

  /**
  * The api key repository.
  *
  * @var \Drupal\key\KeyRepositoryInterface
  */
  protected $keyRepository;

  /**
  * The APIKey.
  */
  protected $apiKey;

  /**
  * Constructs a new VideoService object.
  */
  public function __construct(ClientInterface $http_client, KeyRepositoryInterface $key_repository) {
    $this->httpClient = $http_client;
    $this->keyRepository = $key_repository;
    $this->apiKey = $this->getApikey();
  }

  private function getApikey() {
    if (!$this->apiKey) {
      if ($apiKey = $this->keyRepository->getKey('youtube')->getKeyValue())
        return $apiKey;
      else {
        throw new Exception("Api Key is Empty");
      }
    }
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
   *
   * @param $video_id
   *
   * @return mixed
   */
  public function getVideoTitle($video_id) {
    $data = $this->getVideoData($video_id);
    $title = $data['snippet']['title'] ?? FALSE;
    return $title;
  }

  /**
   * Get Video Data
   *
   * @param $video_id
   *
   * @return array
   *   The snippet data from the video.
   */
  public function getVideoData($video_id) {
    $data = [];
    // We might need to break this into smaller pieces later depending on how
    // extensively we use the youtube API.
    $url = 'https://youtube.googleapis.com/youtube/v3/videos?part=snippet%2CcontentDetails&id=' . $video_id . '&key=' . $this->apiKey . '&format=json';
    $request = $this->httpClient->request('GET', $url, [
      'http_errors' => false
    ]);
    if ($request->getStatusCode() !== 200) {
      return $data;
    }

    if ($snippet = json_decode($request->getBody()->getContents(), TRUE)) {
      $data = $snippet['items'][0];
    }

    return $data;
  }

}
