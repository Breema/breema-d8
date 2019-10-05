<?php

namespace Drupal\breema\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Custom path processor for www.breema.com.
 *
 * As the route system does not allow arbitrary amount of parameters, to get a
 * wildcard route to redirect /deutsch/* to the front page, we have to process
 * the path and strip off anything else.
 */
class BreemaPathProcessor implements InboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if (strpos($path, '/deutsch') === 0) {
      return '/deutsch';
    }
    return $path;
  }

}
