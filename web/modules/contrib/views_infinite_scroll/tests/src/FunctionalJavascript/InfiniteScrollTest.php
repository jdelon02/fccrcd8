<?php

namespace Drupal\Tests\views_infinite_scroll\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\views\Entity\View;

/**
 * Test views infinite scroll.
 *
 * @group views_infinite_scroll
 */
class InfiniteScrollTest extends WebDriverTestBase {

  use NodeCreationTrait;
  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'views',
    'views_ui',
    'views_infinite_scroll',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->createContentType([
      'type' => 'page',
    ]);
    // Create 11 nodes.
    for ($i = 1; $i <= 11; $i++) {
      $this->createNode([
        'status' => TRUE,
        'type' => 'page',
      ]);
    }
  }

  /**
   * Test infinite scrolling under different conditions.
   */
  public function testInfiniteScroll() {
    // Test manually clicking a view.
    $this->createView('click-to-load', [
      'button_text' => 'Load More',
      'automatically_load_content' => FALSE,
    ]);
    $this->drupalGet('click-to-load');
    $this->assertTotalNodes(3);
    $this->getSession()->getPage()->clickLink('Load More');
    $this->assertSession()->waitForElement('css', '.node--type-page:nth-child(4)');
    $this->assertTotalNodes(6);

    // Test the view automatically loading.
    $this->createView('automatic-load', [
      'button_text' => 'Load More',
      'automatically_load_content' => TRUE,
    ]);
    $this->getSession()->resizeWindow(1200, 200);
    $this->drupalGet('automatic-load');
    $this->assertTotalNodes(3);
    $this->scrollTo(500);
    $this->assertSession()->waitForElement('css', '.node--type-page:nth-child(4)');
    $this->assertTotalNodes(6);

    // Test @next_page_count and @total token.
    $this->createView('next-page-count', [
      'button_text' => 'Load @next_page_count more of @total',
      'automatically_load_content' => FALSE,
    ], 6);
    $this->drupalGet('next-page-count');
    $this->getSession()->getPage()->clickLink('Load 5 more of 11');
    $this->assertSession()->waitForElement('css', '.node--type-page:nth-child(7)');
    $this->assertTotalNodes(11);
  }

  /**
   * Assert how many nodes appear on the page.
   *
   * @param int $total
   *   The total nodes on the page.
   */
  protected function assertTotalNodes($total) {
    $this->assertEquals($total, count($this->getSession()->getPage()->findAll('css', '.node--type-page')));
  }

  /**
   * Scroll to a pixel offset.
   *
   * @param int $pixels
   *   The pixel offset to scroll to.
   */
  protected function scrollTo($pixels) {
    $this->getSession()->getDriver()->executeScript("window.scrollTo(null, $pixels);");
  }

  /**
   * Create a view setup for testing views_infinite_scroll.
   *
   * @param string $path
   *   The path for the view.
   * @param array $settings
   *   The VIS settings.
   * @param int $items_per_page
   *   The number of items per page to display.
   */
  protected function createView($path, $settings, $items_per_page = 3) {
    View::create([
      'label' => 'VIS Test',
      'id' => $this->randomMachineName(),
      'base_table' => 'node_field_data',
      'display' => [
        'default' => [
          'display_plugin' => 'default',
          'id' => 'default',
          'display_options' => [
            'row' => [
              'type' => 'entity:node',
              'options' => [
                'view_mode' => 'teaser',
              ],
            ],
            'pager' => [
              'type' => 'infinite_scroll',
              'options' => [
                'items_per_page' => $items_per_page,
                'offset' => 0,
                'views_infinite_scroll' => $settings,
              ],
            ],
            'use_ajax' => TRUE,
          ],
        ],
        'page_1' => [
          'display_plugin' => 'page',
          'id' => 'page_1',
          'display_options' => [
            'path' => $path,
          ],
        ],
      ],
    ])->save();
    \Drupal::service('router.builder')->rebuild();
  }

}
