<?php

namespace Drupal\sfgov_video\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\sfgov_video\VideoService;

/**
 * Class VideoController.
 */
class VideoController extends ControllerBase {

  /**
   * Drupal\sfgov_video\VideoService definition.
   *
   * @var \Drupal\sfgov_video\VideoService
   */
  protected $sfgovVideoUtilities;

  /**
   * Constructs a new VideoController object.
   */
  public function __construct(VideoService $sfgov_video_utilities) {
    $this->sfgovVideoUtilities = $sfgov_video_utilities;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sfgov_video.utilities')
    );
  }

  /**
   * @param $video_id
   *
   * @return mixed
   */
  public function getTitle($video_id) {
    return $this->sfgovVideoUtilities->getVideoTitle($video_id);
  }

  /**
   * @param $video_id
   *
   * @return array
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function page($video_id) {
    return [
      '#theme' => 'sfgov_video_page',
      '#title' => $this->sfgovVideoUtilities->getVideoTitle($video_id),
      '#body' => $this->sfgovVideoUtilities->getYoutubeTranscript($video_id)
    ];
  }

}
