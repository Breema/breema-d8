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
 *
 * This is also where we look for /images/uploads/pdf/* and try to send the
 * visitor to an appropriate new location.
 */
class BreemaPathProcessor implements InboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    // Wildcard route for /deutsch*
    if (strpos($path, '/deutsch') === 0) {
      return '/deutsch';
    }
    // Wildcard route for /classes-events/*
    if (strpos($path, '/classes-events') === 0) {
      return '/breema/legacy/classes-events';
    }
    // Wildcard route for /calendar/YYYY/MM*
    if (preg_match('@^/calendar/\d+/\d+@', $path) === 1) {
      return '/breema/legacy/calendar';
    }
    // Check for PDF URLs.
    if (strpos($path, '/images/uploads/pdf/') === 0) {
      // Handle anything we know how to redirect.
      if (stripos($path, 'intensive') !== FALSE) {
        return '/breema/legacy/pdf/intensive';
      }
      if (stripos($path, 'brochure') !== FALSE) {
        return '/breema/legacy/pdf/brochure';
      }
      if (stripos($path, 'class') !== FALSE) {
        return '/breema/legacy/pdf/schedule';
      }
      if (stripos($path, 'sched') !== FALSE) {
        return '/breema/legacy/pdf/schedule';
      }
    }
    return $path;
  }

}
