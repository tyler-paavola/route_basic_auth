<?php

namespace Drupal\route_basic_auth\EventSubscriber;

use Drupal\Core\Access\AccessResultForbidden;
use Drupal\route_basic_auth\Config\ConfigManager;
use Drupal\route_basic_auth\Routing\RouteHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscribes to kernel response events.
 *
 * @package Drupal\route_basic_auth\EventSubscriber\Response
 */
class ResponseSubscriber implements EventSubscriberInterface {

  /**
   * The config manager service.
   *
   * @var \Drupal\route_basic_auth\Config\ConfigManager
   */
  private $configManager;

  /**
   * The route helper service.
   *
   * @var \Drupal\route_basic_auth\Routing\RouteHelper
   */
  private $routeHelper;

  /**
   * ResponseSubscriber constructor.
   *
   * @param \Drupal\route_basic_auth\Config\ConfigManager $configManager
   *   The config factory.
   * @param \Drupal\route_basic_auth\Routing\RouteHelper $routeHelper
   *   The route helper service.
   */
  public function __construct(ConfigManager $configManager, RouteHelper $routeHelper) {
    $this->configManager = $configManager;
    $this->routeHelper = $routeHelper;
  }

  /**
   * Use a low priority to ensure this is executed last.
   *
   * @return array
   *   The events to subscribe to.
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => ['onResponseEvent', -1000],
    ];
  }

  /**
   * Sets the status code to 401 and adds basic auth header.
   *
   * If the current route is configured as protected and the access is denied.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $responseEvent
   *   The response event.
   */
  public function onResponseEvent(FilterResponseEvent $responseEvent) {
    $request = $responseEvent->getRequest();
    $response = $responseEvent->getResponse();

    $routeName = $this->routeHelper->getRouteNameFromRequest($request);
    if ($routeName === NULL) {
      // Abort if the current request does not match a route.
      return;
    }

    $accessResult = $request->attributes->get('_access_result');

    if ($this->configManager->shouldRouteBeProtected($routeName) && $accessResult instanceof AccessResultForbidden) {
      $response->headers->add([
        'WWW-Authenticate' => 'Basic realm="Restricted Page"',
      ]);
      $response->setStatusCode(401);
    }
  }

}
