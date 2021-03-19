<?php

namespace Drupal\booksindexblock\Plugin\Block;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\book\BookManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
/**
 * Provides a block with a simple text.
 *
 * @Block(
 *    id = "books_index_block",
 *    admin_label = @Translation("BooksIndexBlock"),
 *    category= @Translation("Books")
 * )
 */
class BooksIndexBlock extends BlockBase implements ContainerFactoryPluginInterface {
  protected $bookManager;
  protected $entityTypeManager;
  public function __construct(array $configuration, $plugin_id, $plugin_definition, 
  BookManagerInterface $bookManager,
  EntityTypeManagerInterface $entityTypeManager
  ){
    $this->bookManager=$bookManager;
    $this->entityTypeManager=$entityTypeManager;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
   
  }
  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('book.manager'),
      $container->get('entity_type.manager')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function build() {
    $content=$this->buildBlockContent($this->bookManager->bookTreeAllData(1),[],0, 1000);
    return [
      '#markup' =>  $content,
    ];
  }

  private function buildBlockContent(array $tree, array $exclude, int $depth,int $maxdepth){
    $nids = [];
    foreach ($tree as $data) {
      if ($depth > $maxdepth) {
        // Don't iterate through any links on this level.
        return;
      }
      if (!in_array($data['link']['nid'], $exclude)) {
        $nids[] = $data['link']['nid'];
      }
    }
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
    $content="";
    if($depth!=0){
      $content.= "<ol>";
    }
    foreach ($tree as $data) {
      $nid = $data['link']['nid'];
      if (empty($nodes[$nid])) {
        continue;
      }
      //print_r($nodes);
      if($depth!=0){
        $content.=   "<li>";
        $content.=   $nodes[$nid]->toLink()->toString();//
      }
      else
      {
        $content.="<h2>".$nodes[$nid]->toLink()->toString()."</h2>";
      }
      if ($data['below']) {
         $content.=$this->buildBlockContent($data['below'],$exclude,++$depth,$maxdepth);
      }
      if($depth!=0){
        $content.="</li>";
      }
    }
    if($depth!=0){
      $content.= "</ol>";
    }
    return $content;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['my_block_settings'] = $form_state->getValue('my_block_settings');
  }
}