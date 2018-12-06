<?php

namespace Drupal\breema\Plugin\Validation\Constraint;

use Drupal\user\Plugin\Validation\Constraint\UserNameConstraintValidator;
use Symfony\Component\Validator\Constraint;

/**
 * BreemaUserName constraint validator.
 *
 * Custom override of UserName constraint to allow empty entity labels to
 * validate before the automatic label is set.
 */
class BreemaUserNameConstraintValidator extends UserNameConstraintValidator {
  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    // @todo: Figure out how to get the parent validation to fire on the pending
    // username, not the current value.
    if (1 || !isset($items) || !$items->value) {
      return;
    }
    parent::validate($items, $constraint);
  }

}
