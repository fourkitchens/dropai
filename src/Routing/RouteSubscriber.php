<?php

namespace Drupal\dropai\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for DropAI routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $entity_types = $this->entityTypeManager->getDefinitions();
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if ($route = $this->getEntityInspectRoute($entity_type)) {
        // Create routes for entity types with a DropAI Inspect Link template.
        // See dropai_entity_type_build() for the setting of link templates.
        $collection->add("entity.$entity_type_id.inspect", $route);
      }
    }
  }

  /**
   * Gets the DropAI Inspect form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getEntityInspectRoute(EntityTypeInterface $entity_type) {
    // Exit early if this entity does not have a DropAI Inspect link template.
    $inspect_form = $entity_type->getLinkTemplate('dropai-inspect');
    if (!$inspect_form) {
      return NULL;
    }

    $entity_type_id = $entity_type->id();
    $route = new Route($inspect_form);
    $route
      ->addDefaults([
        '_form' => '\Drupal\dropai\Form\EntityInspectForm',
        '_title' => 'Inspect ' . $entity_type->getLabel(),
      ])
      ->addRequirements([
        '_permission' => 'access dropai inspector',
      ])
      ->setOption('_dropai_entity_type_id', $entity_type_id)
      ->setOption('_admin_route', TRUE)
      ->setOption('parameters', [
        $entity_type_id => ['type' => 'entity:' . $entity_type_id],
      ]);

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = 'onAlterRoutes';
    return $events;
  }

}
