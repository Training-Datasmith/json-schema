<?php

declare (strict_types=1);
namespace Json_Schema\Constraints;

use Json_Schema\Constraint_Error;
use Json_Schema\Entity\Json_Pointer;
class Object_Constraint extends Constraint
{
    /**
     * @var list<string> List of properties to which a default value has been applied
     */
    protected $applied_defaults = [];
    /**
     * {@inheritdoc}
     *
     * @param list<string> $appliedDefaults
     */
    public function check(&$element, $schema = null, ?Json_Pointer $path = null, $properties = null, $additional_prop = null, $pattern_properties = null, $applied_defaults = []): void
    {
        if ($element instanceof Undefined_Constraint) {
            return;
        }
        $this->applied_defaults = $applied_defaults;
        $matches = [];
        if ($pattern_properties) {
            // validate the element pattern properties
            $matches = $this->validate_pattern_properties($element, $path, $pattern_properties);
        }
        if ($properties) {
            // validate the element properties
            $this->validate_properties($element, $properties, $path);
        }
        // validate additional element properties & constraints
        $this->validate_element($element, $matches, $schema, $path, $properties, $additional_prop);
    }
    /**
     * @return mixed[]
     */
    public function validate_pattern_properties($element, ?Json_Pointer $path, $pattern_properties): array
    {
        $matches = [];
        foreach ($pattern_properties as $pregex => $schema) {
            $full_regex = self::json_pattern_to_php_regex($pregex);
            // Validate the pattern before using it to test for matches
            if (@preg_match($full_regex, '') === false) {
                $this->add_error(Constraint_Error::PREGEX_INVALID(), $path, ['pregex' => $pregex]);
                continue;
            }
            foreach ($element as $i => $value) {
                if (preg_match($full_regex, (string) $i)) {
                    $matches[] = $i;
                    $this->check_undefined($value, $schema ?: new \stdClass(), $path, $i, in_array($i, $this->applied_defaults));
                }
            }
        }
        return $matches;
    }
    /**
     * Validates the element properties
     *
     * @param \StdClass        $element        Element to validate
     * @param array            $matches        Matches from patternProperties (if any)
     * @param \StdClass        $schema         ObjectConstraint definition
     * @param JsonPointer|null $path           Current test path
     * @param \StdClass        $properties     Properties
     * @param mixed            $additionalProp Additional properties
     */
    public function validate_element($element, $matches, $schema = null, ?Json_Pointer $path = null, $properties = null, $additional_prop = null): void
    {
        $this->validate_min_max_constraint($element, $schema, $path);
        foreach ($element as $i => $value) {
            $definition = $this->get_property($properties, $i);
            // no additional properties allowed
            if (!in_array($i, $matches) && $additional_prop === false && $this->inline_schema_property !== $i && !$definition) {
                $this->add_error(Constraint_Error::ADDITIONAL_PROPERTIES(), $path, ['property' => $i]);
            }
            // additional properties defined
            if (!in_array($i, $matches) && $additional_prop && !$definition) {
                if ($additional_prop === true) {
                    $this->check_undefined($value, null, $path, $i, in_array($i, $this->applied_defaults));
                } else {
                    $this->check_undefined($value, $additional_prop, $path, $i, in_array($i, $this->applied_defaults));
                }
            }
            // property requires presence of another
            $require = $this->get_property($definition, 'requires');
            if ($require && !$this->get_property($element, $require)) {
                $this->add_error(Constraint_Error::REQUIRES(), $path, ['property' => $i, 'requiredProperty' => $require]);
            }
            $property = $this->get_property($element, $i, $this->factory->create_instance_for('undefined'));
            if (is_object($property)) {
                $this->validate_min_max_constraint(!$property instanceof Undefined_Constraint ? $property : $element, $definition, $path);
            }
        }
    }
    /**
     * Validates the definition properties
     *
     * @param \stdClass        $element    Element to validate
     * @param \stdClass        $properties Property definitions
     * @param JsonPointer|null $path       Path?
     */
    public function validate_properties(&$element, $properties = null, ?Json_Pointer $path = null): void
    {
        $undefined_constraint = $this->factory->create_instance_for('undefined');
        foreach ($properties as $i => $value) {
            $property =& $this->get_property($element, $i, $undefined_constraint);
            $definition = $this->get_property($properties, $i);
            if (is_object($definition)) {
                // Undefined constraint will check for is_object() and quit if is not - so why pass it?
                $this->check_undefined($property, $definition, $path, $i, in_array($i, $this->applied_defaults));
            }
        }
    }
    /**
     * retrieves a property from an object or array
     *
     * @param mixed  $element  Element to validate
     * @param string $property Property to retrieve
     * @param mixed  $fallback Default value if property is not found
     *
     * @return mixed
     */
    protected function &get_property(&$element, $property, $fallback = null)
    {
        if (is_array($element) && (isset($element[$property]) || array_key_exists($property, $element))) {
            return $element[$property];
        }
        if (is_object($element) && property_exists($element, (string) $property)) {
            return $element->{$property};
        }
        return $fallback;
    }
    /**
     * validating minimum and maximum property constraints (if present) against an element
     *
     * @param \stdClass        $element          Element to validate
     * @param \stdClass        $objectDefinition ObjectConstraint definition
     * @param JsonPointer|null $path             Path to test?
     */
    protected function validate_min_max_constraint($element, $object_definition, ?Json_Pointer $path = null)
    {
        if (!$this->get_type_check()::is_object($element)) {
            return;
        }
        // Verify minimum number of properties
        if (isset($object_definition->min_properties) && is_int($object_definition->min_properties)) {
            if ($this->get_type_check()->property_count($element) < max(0, $object_definition->min_properties)) {
                $this->add_error(Constraint_Error::PROPERTIES_MIN(), $path, ['minProperties' => $object_definition->min_properties]);
            }
        }
        // Verify maximum number of properties
        if (isset($object_definition->max_properties) && is_int($object_definition->max_properties)) {
            if ($this->get_type_check()->property_count($element) > max(0, $object_definition->max_properties)) {
                $this->add_error(Constraint_Error::PROPERTIES_MAX(), $path, ['maxProperties' => $object_definition->max_properties]);
            }
        }
    }
}