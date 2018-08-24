<?php

namespace Drupal\Tests\sfgov_utility\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;

use Drupal\sfgov_utilities\Utility;

class UtilityTest extends EntityKernelTestBase {
  public static $modules = array('node');

  public function setUp() {
    parent::setUp();
  }

  public function testGetNodesOfContentType() {
    $user = $this->createUser();

    $container = \Drupal::getContainer();
    $container->get('current_user')->setAccount($user);
    
    // create article node
    $articleTitle = 'Some Title';
    $article = Node::create(array(
      'title' => $articleTitle,
      'type' => 'article',
    ));
    $article->save();
    $articleId = $article->id();

    $nodes = Utility::getNodesOfContentType('article');
    $this->assertNotNull($nodes);
    $this->assertContainsOnlyInstancesOf('Drupal\node\Entity\Node', $nodes);
    $this->assertEquals($nodes[$articleId]->getTitle(), $articleTitle);
  }
}