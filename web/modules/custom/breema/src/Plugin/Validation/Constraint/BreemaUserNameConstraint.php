<?php

namespace Drupal\breema\Plugin\Validation\Constraint;

use Drupal\user\Plugin\Validation\Constraint\UserNameConstraint;

/**
 * Class for Breema's custom UserName Constraint.
 *
 * Custom override of UserName constraint to allow empty entity labels to
 * validate before the automatic label is set.
 */
class BreemaUserNameConstraint extends UserNameConstraint {}
