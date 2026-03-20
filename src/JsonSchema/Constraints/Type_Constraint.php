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
use Json_Schema\Exception\InvalidArgumentException;
use UnexpectedValueException as StandardUnexpectedValueException;
/**
 * The TypeConstraint Constraints, validates an element against a given type
 *
 * @author Robert Schönthal <seroscho@googlemail.com>
 * @author Bruno Prieto Reis <bruno.p.reis@gmail.com>
 */
class Type_Constraint extends Constraint
{
    /**
     * @var array|string[] type wordings for validation error messages
     */
    public static $wording = [
        'integer' => 'an integer',
        'number' => 'a number',
        'boolean' => 'a boolean',
        'object' => 'an object',
        'array' => 'an array',
        'string' => 'a string',
        'null' => 'a null',
        'any' => null,
        // validation of 'any' is always true so is not needed in message wording
        0 => null,
    ];
    /**
     * {@inheritdoc}
     */
    public function check(&$value = null, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        $type = $schema->type ?? null;
        $is_valid = false;
        $coerce = $this->factory->get_config(self::CHECK_MODE_COERCE_TYPES);
        $early_coerce = $this->factory->get_config(self::CHECK_MODE_EARLY_COERCE);
        $wording = [];
        if (is_array($type)) {
            $this->validate_types_array($value, $type, $wording, $is_valid, $path, $coerce && $early_coerce);
            if (!$is_valid && $coerce && !$early_coerce) {
                $this->validate_types_array($value, $type, $wording, $is_valid, $path, true);
            }
        } elseif (is_object($type)) {
            $this->check_undefined($value, $type, $path);
            return;
        } else {
            $is_valid = $this->validate_type($value, $type, $coerce && $early_coerce);
            if (!$is_valid && $coerce && !$early_coerce) {
                $is_valid = $this->validate_type($value, $type, true);
            }
        }
        if ($is_valid === false) {
            if (!is_array($type)) {
                $this->validate_type_name_wording($type);
                $wording[] = self::$wording[$type];
            }
            $this->add_error(Constraint_Error::TYPE(), $path, ['found' => gettype($value), 'expected' => $this->implode_with($wording, ', ', 'or')]);
        }
    }
    /**
     * Validates the given $value against the array of types in $type. Sets the value
     * of $isValid to true, if at least one $type mateches the type of $value or the value
     * passed as $isValid is already true.
     *
     * @param mixed        $value             Value to validate
     * @param array        $type              TypeConstraints to check against
     * @param array        $validTypesWording An array of wordings of the valid types of the array $type
     * @param bool         $isValid           The current validation value
     * @param ?JsonPointer $path
     * @param bool         $coerce
     */
    protected function validate_types_array(&$value, array $type, &$valid_types_wording, &$is_valid, $path, $coerce = false)
    {
        foreach ($type as $tp) {
            // already valid, so no need to waste cycles looping over everything
            if ($is_valid) {
                return;
            }
            // $tp can be an object, if it's a schema instead of a simple type, validate it
            // with a new type constraint
            if (is_object($tp)) {
                if (!$is_valid) {
                    $validator = $this->factory->create_instance_for('type');
                    $sub_schema = new \stdClass();
                    $sub_schema->type = $tp;
                    $validator->check($value, $sub_schema, $path, null);
                    $error = $validator->get_errors();
                    $is_valid = !(bool) $error;
                    $valid_types_wording[] = self::$wording['object'];
                }
            } else {
                $this->validate_type_name_wording($tp);
                $valid_types_wording[] = self::$wording[$tp];
                if (!$is_valid) {
                    $is_valid = $this->validate_type($value, $tp, $coerce);
                }
            }
        }
    }
    /**
     * Implodes the given array like implode() with turned around parameters and with the
     * difference, that, if $listEnd isn't false, the last element delimiter is $listEnd instead of
     * $delimiter.
     *
     * @param array  $elements  The elements to implode
     * @param string $delimiter The delimiter to use
     * @param bool   $listEnd   The last delimiter to use (defaults to $delimiter)
     */
    protected function implode_with(array $elements, $delimiter = ', ', $list_end = false): string
    {
        if ($list_end === false || !isset($elements[1])) {
            return implode($delimiter, $elements);
        }
        $last_element = array_slice($elements, -1);
        $firs_elements = join($delimiter, array_slice($elements, 0, -1));
        $imploded_elements = array_merge([$firs_elements], $last_element);
        return join(" {$list_end} ", $imploded_elements);
    }
    /**
     * Validates the given $type, if there's an associated self::$wording. If not, throws an
     * exception.
     *
     * @param string $type The type to validate
     *
     * @throws StandardUnexpectedValueException
     */
    protected function validate_type_name_wording($type)
    {
        if (!array_key_exists($type, self::$wording)) {
            throw new Standard_Unexpected_Value_Exception(sprintf('No wording for %s available, expected wordings are: [%s]', var_export($type, true), implode(', ', array_filter(self::$wording))));
        }
    }
    /**
     * Verifies that a given value is of a certain type
     *
     * @param mixed  $value Value to validate
     * @param string $type  TypeConstraint to check against
     *
     * @throws InvalidArgumentException
     *
     * @return bool
     */
    protected function validate_type(&$value, ?string $type, $coerce = false)
    {
        //mostly the case for inline schema
        if (!$type) {
            return true;
        }
        if ('any' === $type) {
            return true;
        }
        if ('object' === $type) {
            return $this->get_type_check()->is_object($value);
        }
        if ('array' === $type) {
            if ($coerce) {
                $value = $this->to_array($value);
            }
            return $this->get_type_check()->is_array($value);
        }
        if ('integer' === $type) {
            if ($coerce) {
                $value = $this->to_integer($value);
            }
            return is_int($value);
        }
        if ('number' === $type) {
            if ($coerce) {
                $value = $this->to_number($value);
            }
            return is_numeric($value) && !is_string($value);
        }
        if ('boolean' === $type) {
            if ($coerce) {
                $value = $this->to_boolean($value);
            }
            return is_bool($value);
        }
        if ('string' === $type) {
            if ($coerce) {
                $value = $this->to_string($value);
            }
            return is_string($value);
        }
        if ('null' === $type) {
            if ($coerce) {
                $value = $this->to_null($value);
            }
            return is_null($value);
        }
        throw new InvalidArgumentException((is_object($value) ? 'object' : $value) . ' is an invalid type for ' . $type);
    }
    /**
     * Converts a value to boolean. For example, "true" becomes true.
     *
     * @param mixed $value The value to convert to boolean
     *
     * @return bool|mixed
     */
    protected function to_boolean($value)
    {
        if ($value === 1 || $value === 'true') {
            return true;
        }
        if (is_null($value) || $value === 0 || $value === 'false') {
            return false;
        }
        if ($this->get_type_check()->is_array($value) && count($value) === 1) {
            return $this->to_boolean(reset($value));
        }
        return $value;
    }
    /**
     * Converts a value to a number. For example, "4.5" becomes 4.5.
     *
     * @param mixed $value the value to convert to a number
     *
     * @return int|float|mixed
     */
    protected function to_number($value)
    {
        if (is_numeric($value)) {
            return $value + 0;
            // cast to number
        }
        if (is_bool($value) || is_null($value)) {
            return (int) $value;
        }
        if ($this->get_type_check()->is_array($value) && count($value) === 1) {
            return $this->to_number(reset($value));
        }
        return $value;
    }
    /**
     * Converts a value to an integer. For example, "4" becomes 4.
     *
     * @param mixed $value
     *
     * @return int|mixed
     */
    protected function to_integer($value)
    {
        $number_value = $this->to_number($value);
        if (is_numeric($number_value) && (int) $number_value == $number_value) {
            return (int) $number_value;
            // cast to number
        }
        return $value;
    }
    /**
     * Converts a value to an array containing that value. For example, [4] becomes 4.
     *
     * @param mixed $value
     *
     * @return array|mixed
     */
    protected function to_array($value)
    {
        if (is_scalar($value) || is_null($value)) {
            return [$value];
        }
        return $value;
    }
    /**
     * Convert a value to a string representation of that value. For example, null becomes "".
     *
     * @param mixed $value
     *
     * @return string|mixed
     */
    protected function to_string($value)
    {
        if (is_numeric($value)) {
            return "{$value}";
        }
        if ($value === true) {
            return 'true';
        }
        if ($value === false) {
            return 'false';
        }
        if (is_null($value)) {
            return '';
        }
        if ($this->get_type_check()->is_array($value) && count($value) === 1) {
            return $this->to_string(reset($value));
        }
        return $value;
    }
    /**
     * Convert a value to a null. For example, 0 becomes null.
     *
     * @param mixed $value
     *
     * @return null|mixed
     */
    protected function to_null($value)
    {
        if ($value === 0 || $value === false || $value === '') {
            return null;
        }
        if ($this->get_type_check()->is_array($value) && count($value) === 1) {
            return $this->to_null(reset($value));
        }
        return $value;
    }
}