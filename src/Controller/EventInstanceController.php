<?php

namespace Drupal\recurring_events\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\system\SystemManager;
use Drupal\recurring_events\EventInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;

/**
 * The EventInstanceController class.
 */
class EventInstanceController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * System Manager Service.
   *
   * @var \Drupal\system\SystemManager
   */
  protected $systemManager;

  /**
   * Constructs a EventInstanceController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\system\SystemManager $systemManager
   *   System manager service.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer, SystemManager $systemManager) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
    $this->systemManager = $systemManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('system.manager')
    );
  }

  /**
   * Displays an eventinstance revision.
   *
   * @param int $eventinstance_revision
   *   The Default entity  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($eventinstance_revision) {
    $eventinstance = $this->entityManager()->getStorage('eventinstance')->loadRevision($eventinstance_revision);
    $view_builder = $this->entityManager()->getViewBuilder('eventinstance');

    return $view_builder->view($eventinstance);
  }

  /**
   * Page title callback for an eventinstance revision.
   *
   * @param int $eventinstance_revision
   *   The Default entity  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($eventinstance_revision) {
    $eventinstance = $this->entityManager()->getStorage('eventinstance')->loadRevision($eventinstance_revision);
    return $this->t('Revision of %title from %date', ['%title' => $eventinstance->label(), '%date' => format_date($eventinstance->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of an eventinstance.
   *
   * @param \Drupal\recurring_events\EventInterface $eventinstance
   *   A Default entity  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(EventInterface $eventinstance) {
    $account = $this->currentUser();
    $langcode = $eventinstance->language()->getId();
    $langname = $eventinstance->language()->getName();
    $languages = $eventinstance->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $eventinstance_storage = $this->entityManager()->getStorage('eventinstance');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $eventinstance->label()]) : $this->t('Revisions for %title', ['%title' => $eventinstance->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all eventinstance revisions") || $account->hasPermission('administer eventinstance entities')));
    $delete_permission = (($account->hasPermission("delete all eventinstance revisions") || $account->hasPermission('administer eventinstance entities')));

    $rows = [];

    $vids = $eventinstance_storage->revisionIds($eventinstance);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\recurring_events\EventInterface $revision */
      $revision = $eventinstance_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $eventinstance->getRevisionId()) {
          $link = $this->l($date, new Url('entity.eventinstance.revision', ['eventinstance' => $eventinstance->id(), 'eventinstance_revision' => $vid]));
        }
        else {
          $link = $eventinstance->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        // @todo Simplify once https://www.drupal.org/node/2334319 lands.
        $this->renderer->addCacheableDependency($column['data'], $username);
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.eventinstance.translation_revert', [
                'eventinstance' => $eventinstance->id(),
                'eventinstance_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.eventinstance.revision_revert', [
                'eventinstance' => $eventinstance->id(),
                'eventinstance_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.eventinstance.revision_delete', ['eventinstance' => $eventinstance->id(), 'eventinstance_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['eventinstance_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
