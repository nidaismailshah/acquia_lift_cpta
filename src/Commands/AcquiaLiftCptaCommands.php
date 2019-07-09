<?php

namespace Drupal\acquia_lift_cpta\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Component\Utility\Random;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\block_content\Entity\BlockContent;
use Drupal\acquia_contenthub\Client\ClientManagerInterface;
use Drupal\node\Entity\Node;


/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class AcquiaLiftCptaCommands extends DrushCommands {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Content Hub Client Manager.
   *
   * @var \Drupal\acquia_contenthub\Client\ClientManager
   */
  private $clientManager;

  /**
   * Public Constructor.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   The node storage.
   * @param \Drupal\acquia_contenthub\Client\ClientManagerInterface $client_manager
   *   The client manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ClientManagerInterface $client_manager) {
    //parent::__construct($configuration, $plugin_id, $plugin_definition);
    parent::__construct();
    $this->entityTypeManager = $entityTypeManager;
    $this->clientManager = $client_manager;
  }


  /**
   * Generate dummy contents for test
   *
   * @command acquia:liftcpta-generate-content
   * @aliases alc-genc
   */
  public function generateContent() {

    if ($this->clientManager->isConnected()) {
      $this->logger()->success(dt('Connected to Acquia Content Hub.'));
    } else {
      $this->logger()->error(dt('Not Connected to Acquia Content Hub.'));
      return;
    }

    $node_type = 'lift_cpta';

    $node = $this->entityTypeManager->getStorage('node')->create([
        'type' => $node_type,
        'title' => $this->getRandom()->sentences(2, TRUE),
        'body' => $this->getRandom()->paragraphs(4),
        'status' => TRUE,
    ]);

    $node->save();
    $node_uuid = $node->uuid();

    $this->logger()->success(dt('Created 1 Node with UUID: @node_uuid',
      ['@node_uuid' => $node_uuid]));


    $block = BlockContent::create([
        'info' => $this->getRandom()->sentences(2, TRUE),
        'type' => 'lift_cpta',
        'langcode' => 'en',
        'body' => $this->getRandom()->paragraphs(4),
    ]);
    $block->save();
    $block_uuid = $block->uuid();

    $this->logger()->success(dt('Created 1 Block with UUID: @block_uuid',
      ['@block_uuid' => $block_uuid]));

  }


  /**
   * Verify dummy contents for test
   *
   * @command acquia:liftcpta-verify-content
   * @aliases alc-verc
   */
  public function verifyContent($uuid) {
    if ($entity = $this->clientManager->createRequest('readEntity', [$uuid])) {
      if (isset($entity['metadata']['view_modes']['default'])) {
        $this->logger()->success(dt('Content is present with default view mode on Acquia Content Hub.'));
      }
    }
    else {
      $this->logger()->error(dt('No entity found on content hub for the provided uuid.'));
    }
  }


  /**
   * Delete dummy contents for test
   *
   * @command acquia:liftcpta-delete-content
   * @aliases alc-delc
   */
  public function deleteContent() {
    $node_type = 'lift_cpta';
    $this->contentDelete($node_type);
  }


  /**
   * Returns the random data generator.
   *
   * @return \Drupal\Component\Utility\Random
   *   The random data generator.
   */
  protected function getRandom() {
    if (!$this->random) {
      $this->random = new Random();
    }
    return $this->random;
  }

  /**
   * Deletes all nodes of given node type.
   *
   * @param $node_type
   *   Node type of which contents to delete.
   */
  protected function contentDelete($node_type) {
    $nids = $this->entityTypeManager->getStorage('node')->getQuery()
        ->condition('type', $node_type)
        ->execute();

    if (!empty($nids)) {
      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
      $this->entityTypeManager->getStorage('node')->delete($nodes);
      $this->logger()->success(dt('Deleted %count nodes.', array('%count' => count($nids))));
    }
    else {
      $this->logger()->notice(dt('No Nodes exist.'));
    }

    $bids = $this->entityTypeManager->getStorage('block_content')->getQuery()
      ->condition('type', $node_type)
      ->execute();

    if (!empty($bids)) {
      $blocks = $this->entityTypeManager->getStorage('block_content')->loadMultiple($bids);
      $this->entityTypeManager->getStorage('block_content')->delete($blocks);
      $this->logger()->success(dt('Deleted %count blocks.', array('%count' => count($bids))));
    }
    else {
      $this->logger()->notice(dt('No Blocks exist.'));
    }

  }

}
