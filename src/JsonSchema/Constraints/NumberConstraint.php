<?php

declare (strict_types=1);
/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Json_Schema\Constraints;

use Json_Schema\Constraint_Error;
use Json_Schema\Entity\Json_Pointer;
/**
 * The NumberConstraint Constraints, validates an number against a given schema
 *
 * @author Robert Schönthal <seroscho@googlemail.com>
 * @author Bruno Prieto Reis <bruno.p.reis@gmail.com>
 */
class Number_Constraint extends Constraint
{
    /**
     * {@inheritdoc}
     */
    public function check(&$element, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        // Verify minimum
        if (isset($schema->exclusive_minimum)) {
            if (isset($schema->minimum)) {
                if ($schema->exclusive_minimum && $element <= $schema->minimum) {
                    $this->add_error(Constraint_Error::EXCLUSIVE_MINIMUM(), $path, ['minimum' => $schema->minimum]);
                } elseif ($element < $schema->minimum) {
                    $this->add_error(Constraint_Error::MINIMUM(), $path, ['minimum' => $schema->minimum]);
                }
            } else {
                $this->add_error(Constraint_Error::MISSING_MINIMUM(), $path);
            }
        } elseif (isset($schema->minimum) && $element < $schema->minimum) {
            $this->add_error(Constraint_Error::MINIMUM(), $path, ['minimum' => $schema->minimum]);
        }
        // Verify maximum
        if (isset($schema->exclusive_maximum)) {
            if (isset($schema->maximum)) {
                if ($schema->exclusive_maximum && $element >= $schema->maximum) {
                    $this->add_error(Constraint_Error::EXCLUSIVE_MAXIMUM(), $path, ['maximum' => $schema->maximum]);
                } elseif ($element > $schema->maximum) {
                    $this->add_error(Constraint_Error::MAXIMUM(), $path, ['maximum' => $schema->maximum]);
                }
            } else {
                $this->add_error(Constraint_Error::MISSING_MAXIMUM(), $path);
            }
        } elseif (isset($schema->maximum) && $element > $schema->maximum) {
            $this->add_error(Constraint_Error::MAXIMUM(), $path, ['maximum' => $schema->maximum]);
        }
        // Verify divisibleBy - Draft v3
        if (isset($schema->divisible_by) && $this->fmod($element, $schema->divisible_by) != 0) {
            $this->add_error(Constraint_Error::DIVISIBLE_BY(), $path, ['divisibleBy' => $schema->divisible_by]);
        }
        // Verify multipleOf - Draft v4
        if (isset($schema->multiple_of) && $this->fmod($element, $schema->multiple_of) != 0) {
            $this->add_error(Constraint_Error::MULTIPLE_OF(), $path, ['multipleOf' => $schema->multiple_of]);
        }
        $this->check_format($element, $schema, $path, $i);
    }
    private function fmod($number1, $number2): float
    {
        $modulus = $number1 - round($number1 / $number2) * $number2;
        $precision = 1.0E-10;
        if (-$precision < $modulus && $modulus < $precision) {
            return 0.0;
        }
        return $modulus;
    }
}