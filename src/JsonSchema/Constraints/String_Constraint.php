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
 * The StringConstraint Constraints, validates an string against a given schema
 *
 * @author Robert Schönthal <seroscho@googlemail.com>
 * @author Bruno Prieto Reis <bruno.p.reis@gmail.com>
 */
class String_Constraint extends Constraint
{
    /**
     * {@inheritdoc}
     */
    public function check(&$element, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        // Verify maxLength
        if (isset($schema->max_length) && $this->strlen($element) > $schema->max_length) {
            $this->add_error(Constraint_Error::LENGTH_MAX(), $path, ['maxLength' => $schema->max_length]);
        }
        //verify minLength
        if (isset($schema->min_length) && $this->strlen($element) < $schema->min_length) {
            $this->add_error(Constraint_Error::LENGTH_MIN(), $path, ['minLength' => $schema->min_length]);
        }
        // Verify a regex pattern
        if (isset($schema->pattern) && !preg_match(self::json_pattern_to_php_regex($schema->pattern), $element)) {
            $this->add_error(Constraint_Error::PATTERN(), $path, ['pattern' => $schema->pattern]);
        }
        $this->check_format($element, $schema, $path, $i);
    }
    private function strlen($string): int
    {
        if (extension_loaded('mbstring')) {
            return mb_strlen($string, mb_detect_encoding($string));
        }
        // mbstring is present on all test platforms, so strlen() can be ignored for coverage
        return strlen($string);
        // @codeCoverageIgnore
    }
}