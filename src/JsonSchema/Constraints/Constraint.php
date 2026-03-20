<?php

declare (strict_types=1);
namespace Json_Schema\Constraints;

use Json_Schema\Entity\Json_Pointer;
abstract class Constraint extends Base_Constraint implements Constraint_Interface
{
    /** @var string */
    protected $inline_schema_property = '$schema';
    /** @deprecated CHECK_MODE_NONE is unused and will be removed in the next major release (7.0.0) */
    public const CHECK_MODE_NONE = 0x0;
    public const CHECK_MODE_NORMAL = 0x1;
    public const CHECK_MODE_TYPE_CAST = 0x2;
    public const CHECK_MODE_COERCE_TYPES = 0x4;
    public const CHECK_MODE_APPLY_DEFAULTS = 0x8;
    public const CHECK_MODE_EXCEPTIONS = 0x10;
    public const CHECK_MODE_DISABLE_FORMAT = 0x20;
    public const CHECK_MODE_EARLY_COERCE = 0x40;
    public const CHECK_MODE_ONLY_REQUIRED_DEFAULTS = 0x80;
    public const CHECK_MODE_VALIDATE_SCHEMA = 0x100;
    public const CHECK_MODE_STRICT = 0x200;
    /**
     * Bubble down the path
     *
     * @param JsonPointer|null $path Current path
     * @param mixed            $i    What to append to the path
     */
    protected function increment_path(?Json_Pointer $path, $i): Json_Pointer
    {
        $path = $path ?? new Json_Pointer('');
        if ($i === null || $i === '') {
            return $path;
        }
        return $path->with_property_paths(array_merge($path->get_property_paths(), [$i]));
    }
    /**
     * Validates an array
     *
     * @param mixed $value
     * @param mixed $schema
     * @param mixed $i
     */
    protected function check_array(&$value, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        $validator = $this->factory->create_instance_for('collection');
        $validator->check($value, $schema, $path, $i);
        $this->add_errors($validator->get_errors());
    }
    /**
     * Validates an object
     *
     * @param mixed         $value
     * @param mixed         $schema
     * @param mixed         $properties
     * @param mixed         $additionalProperties
     * @param mixed         $patternProperties
     * @param array<string> $appliedDefaults
     */
    protected function check_object(&$value, $schema = null, ?Json_Pointer $path = null, $properties = null, $additional_properties = null, $pattern_properties = null, array $applied_defaults = []): void
    {
        /** @var ObjectConstraint $validator */
        $validator = $this->factory->create_instance_for('object');
        $validator->check($value, $schema, $path, $properties, $additional_properties, $pattern_properties, $applied_defaults);
        $this->add_errors($validator->get_errors());
    }
    /**
     * Validates the type of the value
     *
     * @param mixed $value
     * @param mixed $schema
     * @param mixed $i
     */
    protected function check_type(&$value, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        $validator = $this->factory->create_instance_for('type');
        $validator->check($value, $schema, $path, $i);
        $this->add_errors($validator->get_errors());
    }
    /**
     * Checks a undefined element
     *
     * @param mixed $value
     * @param mixed $schema
     * @param mixed $i
     */
    protected function check_undefined(&$value, $schema = null, ?Json_Pointer $path = null, $i = null, bool $from_default = false): void
    {
        /** @var UndefinedConstraint $validator */
        $validator = $this->factory->create_instance_for('undefined');
        $validator->check($value, $this->factory->get_schema_storage()->resolve_ref_schema($schema), $path, $i, $from_default);
        $this->add_errors($validator->get_errors());
    }
    /**
     * Checks a string element
     *
     * @param mixed $value
     * @param mixed $schema
     * @param mixed $i
     */
    protected function check_string($value, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        $validator = $this->factory->create_instance_for('string');
        $validator->check($value, $schema, $path, $i);
        $this->add_errors($validator->get_errors());
    }
    /**
     * Checks a number element
     *
     * @param mixed $value
     * @param mixed $schema
     * @param mixed $i
     */
    protected function check_number($value, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        $validator = $this->factory->create_instance_for('number');
        $validator->check($value, $schema, $path, $i);
        $this->add_errors($validator->get_errors());
    }
    /**
     * Checks a enum element
     *
     * @param mixed $value
     * @param mixed $schema
     * @param mixed $i
     */
    protected function check_enum($value, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        $validator = $this->factory->create_instance_for('enum');
        $validator->check($value, $schema, $path, $i);
        $this->add_errors($validator->get_errors());
    }
    /**
     * Checks a const element
     *
     * @param mixed $value
     * @param mixed $schema
     * @param mixed $i
     */
    protected function check_const($value, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        $validator = $this->factory->create_instance_for('const');
        $validator->check($value, $schema, $path, $i);
        $this->add_errors($validator->get_errors());
    }
    /**
     * Checks format of an element
     *
     * @param mixed $value
     * @param mixed $schema
     * @param mixed $i
     */
    protected function check_format($value, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        $validator = $this->factory->create_instance_for('format');
        $validator->check($value, $schema, $path, $i);
        $this->add_errors($validator->get_errors());
    }
    /**
     * Get the type check based on the set check mode.
     */
    protected function get_type_check(): Type_Check\Type_Check_Interface
    {
        return $this->factory->get_type_check();
    }
}