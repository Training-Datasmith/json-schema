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
use Json_Schema\Tool\Deep_Comparer;
/**
 * The CollectionConstraint Constraints, validates an array against a given schema
 *
 * @author Robert Schönthal <seroscho@googlemail.com>
 * @author Bruno Prieto Reis <bruno.p.reis@gmail.com>
 */
class Collection_Constraint extends Constraint
{
    /**
     * {@inheritdoc}
     */
    public function check(&$value, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        // Verify minItems
        if (isset($schema->min_items) && count($value) < $schema->min_items) {
            $this->add_error(Constraint_Error::MIN_ITEMS(), $path, ['minItems' => $schema->min_items, 'found' => count($value)]);
        }
        // Verify maxItems
        if (isset($schema->max_items) && count($value) > $schema->max_items) {
            $this->add_error(Constraint_Error::MAX_ITEMS(), $path, ['maxItems' => $schema->max_items, 'found' => count($value)]);
        }
        // Verify uniqueItems
        if (isset($schema->unique_items) && $schema->unique_items) {
            $count = count($value);
            for ($x = 0; $x < $count - 1; $x++) {
                for ($y = $x + 1; $y < $count; $y++) {
                    if (Deep_Comparer::is_equal($value[$x], $value[$y])) {
                        $this->add_error(Constraint_Error::UNIQUE_ITEMS(), $path);
                        break 2;
                    }
                }
            }
        }
        $this->validate_items($value, $schema, $path, $i);
    }
    /**
     * Validates the items
     *
     * @param array     $value
     * @param \stdClass $schema
     * @param string    $i
     */
    protected function validate_items(&$value, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        if (\is_null($schema) || !isset($schema->items)) {
            return;
        }
        if ($schema->items === true) {
            return;
        }
        if (is_object($schema->items)) {
            // just one type definition for the whole array
            foreach ($value as $k => &$v) {
                $init_errors = $this->get_errors();
                // First check if its defined in "items"
                $this->check_undefined($v, $schema->items, $path, $k);
                // Recheck with "additionalItems" if the first test fails
                if (count($init_errors) < count($this->get_errors()) && (isset($schema->additional_items) && $schema->additional_items !== false)) {
                    $second_errors = $this->get_errors();
                    $this->check_undefined($v, $schema->additional_items, $path, $k);
                }
                // Reset errors if needed
                if (isset($second_errors) && count($second_errors) < count($this->get_errors())) {
                    $this->errors = $second_errors;
                } elseif (isset($second_errors) && count($second_errors) === count($this->get_errors())) {
                    $this->errors = $init_errors;
                }
            }
            unset($v);
            /* remove dangling reference to prevent any future bugs
             * caused by accidentally using $v elsewhere */
        } else {
            // Defined item type definitions
            foreach ($value as $k => &$v) {
                if (array_key_exists($k, $schema->items)) {
                    $this->check_undefined($v, $schema->items[$k], $path, $k);
                } else if (property_exists($schema, 'additionalItems')) {
                    if ($schema->additional_items !== false) {
                        $this->check_undefined($v, $schema->additional_items, $path, $k);
                    } else {
                        $this->add_error(Constraint_Error::ADDITIONAL_ITEMS(), $path, ['item' => $i, 'property' => $k, 'additionalItems' => $schema->additional_items]);
                    }
                } else {
                    // Should be valid against an empty schema
                    $this->check_undefined($v, new \stdClass(), $path, $k);
                }
            }
            unset($v);
            /* remove dangling reference to prevent any future bugs
             * caused by accidentally using $v elsewhere */
            // Treat when we have more schema definitions than values, not for empty arrays
            if (count($value) > 0) {
                for ($k = count($value); $k < count($schema->items); $k++) {
                    $undefined_instance = $this->factory->create_instance_for('undefined');
                    $this->check_undefined($undefined_instance, $schema->items[$k], $path, $k);
                }
            }
        }
    }
}