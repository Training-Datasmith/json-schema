<?php

declare (strict_types=1);
namespace Json_Schema\Constraints;

use Json_Schema\Constraint_Error;
use Json_Schema\Constraints\Type_Check\Loose_Type_Check;
use Json_Schema\Entity\Json_Pointer;
use Json_Schema\Exception\Validation_Exception;
use Json_Schema\Tool\Deep_Copy;
use Json_Schema\Uri\Uri_Resolver;
#[\Allow_Dynamic_Properties]
class Undefined_Constraint extends Constraint
{
    /**
     * @var list<string> List of properties to which a default value has been applied
     */
    protected $applied_defaults = [];
    /**
     * {@inheritdoc}
     * */
    public function check(&$value, $schema = null, ?Json_Pointer $path = null, $i = null, bool $from_default = false): void
    {
        if (is_null($schema) || !is_object($schema)) {
            return;
        }
        $path = $this->increment_path($path, $i);
        if ($from_default) {
            $path->set_from_default();
        }
        // check special properties
        $this->validate_common_properties($value, $schema, $path, $i);
        // check allOf, anyOf, and oneOf properties
        $this->validate_of_properties($value, $schema, $path, '');
        // check known types
        $this->validate_types($value, $schema, $path, $i);
    }
    /**
     * Validates the value against the types
     *
     * @param mixed       $value
     * @param mixed       $schema
     * @param string      $i
     */
    public function validate_types(&$value, $schema, Json_Pointer $path, $i = null): void
    {
        // check array
        if ($this->get_type_check()->is_array($value)) {
            $this->check_array($value, $schema, $path, $i);
        }
        // check object
        if (Loose_Type_Check::is_object($value)) {
            // object processing should always be run on assoc arrays,
            // so use LooseTypeCheck here even if CHECK_MODE_TYPE_CAST
            // is not set (i.e. don't use $this->getTypeCheck() here).
            $this->check_object($value, $schema, $path, $schema->properties ?? null, $schema->additional_properties ?? null, $schema->pattern_properties ?? null, $this->applied_defaults);
        }
        // check string
        if (is_string($value)) {
            $this->check_string($value, $schema, $path, $i);
        }
        // check numeric
        if (is_numeric($value)) {
            $this->check_number($value, $schema, $path, $i);
        }
        // check enum
        if (isset($schema->enum)) {
            $this->check_enum($value, $schema, $path, $i);
        }
        // check const
        if (isset($schema->const)) {
            $this->check_const($value, $schema, $path, $i);
        }
    }
    /**
     * Validates common properties
     *
     * @param mixed       $value
     * @param mixed       $schema
     * @param string      $i
     */
    protected function validate_common_properties(&$value, $schema, Json_Pointer $path, $i = '')
    {
        // if it extends another schema, it must pass that schema as well
        if (isset($schema->extends)) {
            if (is_string($schema->extends)) {
                $schema->extends = $this->validate_uri($schema, $schema->extends);
            }
            if (is_array($schema->extends)) {
                foreach ($schema->extends as $extends) {
                    $this->check_undefined($value, $extends, $path, $i);
                }
            } else {
                $this->check_undefined($value, $schema->extends, $path, $i);
            }
        }
        // Apply default values from schema
        if (!$path->from_default()) {
            $this->apply_default_values($value, $schema, $path);
        }
        // Verify required values
        if ($this->get_type_check()->is_object($value)) {
            if (!$value instanceof self && isset($schema->required) && is_array($schema->required)) {
                // Draft 4 - Required is an array of strings - e.g. "required": ["foo", ...]
                foreach ($schema->required as $required) {
                    if (!$this->get_type_check()->property_exists($value, $required)) {
                        $this->add_error(Constraint_Error::REQUIRED(), $this->increment_path($path, $required), ['property' => $required]);
                    }
                }
            } elseif (isset($schema->required) && !is_array($schema->required)) {
                // Draft 3 - Required attribute - e.g. "foo": {"type": "string", "required": true}
                if ($schema->required && $value instanceof self) {
                    $property_paths = $path->get_property_paths();
                    $property_name = end($property_paths);
                    $this->add_error(Constraint_Error::REQUIRED(), $path, ['property' => $property_name]);
                }
            } else if ($value instanceof self) {
                return;
            }
        }
        // Verify type
        if (!$value instanceof self) {
            $this->check_type($value, $schema, $path, $i);
        }
        // Verify disallowed items
        if (isset($schema->disallow)) {
            $init_errors = $this->get_errors();
            $type_schema = new \stdClass();
            $type_schema->type = $schema->disallow;
            $this->check_type($value, $type_schema, $path);
            // if no new errors were raised it must be a disallowed value
            if (count($this->get_errors()) == count($init_errors)) {
                $this->add_error(Constraint_Error::DISALLOW(), $path);
            } else {
                $this->errors = $init_errors;
            }
        }
        if (isset($schema->not)) {
            $init_errors = $this->get_errors();
            $this->check_undefined($value, $schema->not, $path, $i);
            // if no new errors were raised then the instance validated against the "not" schema
            if (count($this->get_errors()) == count($init_errors)) {
                $this->add_error(Constraint_Error::NOT(), $path);
            } else {
                $this->errors = $init_errors;
            }
        }
        // Verify that dependencies are met
        if (isset($schema->dependencies) && $this->get_type_check()->is_object($value)) {
            $this->validate_dependencies($value, $schema->dependencies, $path);
        }
    }
    /**
     * Check whether a default should be applied for this value
     *
     * @param mixed $schema
     * @param mixed $parentSchema
     *
     */
    private function should_apply_default_value(bool $required_only, $schema, $name = null, $parent_schema = null): bool
    {
        // required-only mode is off
        if (!$required_only) {
            return true;
        }
        // draft-04 required is set
        if ($name !== null && isset($parent_schema->required) && is_array($parent_schema->required) && in_array($name, $parent_schema->required)) {
            return true;
        }
        // draft-03 required is set
        if (isset($schema->required) && !is_array($schema->required) && $schema->required) {
            return true;
        }
        // default case
        return false;
    }
    /**
     * Apply default values
     *
     * @param mixed       $value
     * @param mixed       $schema
     * @param JsonPointer $path
     */
    protected function apply_default_values(array &$value, $schema, $path): void
    {
        // only apply defaults if feature is enabled
        if (!$this->factory->get_config(self::CHECK_MODE_APPLY_DEFAULTS)) {
            return;
        }
        if (is_bool($schema)) {
            return;
        }
        // apply defaults if appropriate
        $required_only = (bool) $this->factory->get_config(self::CHECK_MODE_ONLY_REQUIRED_DEFAULTS);
        if (isset($schema->properties) && Loose_Type_Check::is_object($value)) {
            // $value is an object or assoc array, and properties are defined - treat as an object
            foreach ($schema->properties as $current_property => $property_definition) {
                $property_definition = $this->factory->get_schema_storage()->resolve_ref_schema($property_definition);
                if (is_bool($property_definition)) {
                    continue;
                }
                if (!Loose_Type_Check::property_exists($value, $current_property) && property_exists($property_definition, 'default') && $this->should_apply_default_value($required_only, $property_definition, $current_property, $schema)) {
                    // assign default value
                    if (is_object($property_definition->default)) {
                        Loose_Type_Check::property_set($value, $current_property, clone $property_definition->default);
                    } else {
                        Loose_Type_Check::property_set($value, $current_property, $property_definition->default);
                    }
                    $this->applied_defaults[] = $current_property;
                }
            }
        } elseif (isset($schema->items) && Loose_Type_Check::is_array($value)) {
            $items = [];
            if (Loose_Type_Check::is_array($schema->items)) {
                $items = $schema->items;
            } elseif (isset($schema->min_items) && count($value) < $schema->min_items) {
                $items = array_fill(count($value), $schema->min_items - count($value), $schema->items);
            }
            // $value is an array, and items are defined - treat as plain array
            foreach ($items as $current_item => $item_definition) {
                $item_definition = $this->factory->get_schema_storage()->resolve_ref_schema($item_definition);
                if (is_bool($item_definition)) {
                    continue;
                }
                if (!array_key_exists($current_item, $value) && property_exists($item_definition, 'default') && $this->should_apply_default_value($required_only, $item_definition)) {
                    if (is_object($item_definition->default)) {
                        $value[$current_item] = clone $item_definition->default;
                    } else {
                        $value[$current_item] = $item_definition->default;
                    }
                }
                $path->set_from_default();
            }
        } elseif ($value instanceof self && property_exists($schema, 'default') && $this->should_apply_default_value($required_only, $schema)) {
            // $value is a leaf, not a container - apply the default directly
            $value = is_object($schema->default) ? clone $schema->default : $schema->default;
            $path->set_from_default();
        }
    }
    /**
     * Validate allOf, anyOf, and oneOf properties
     *
     * @param mixed       $value
     * @param mixed       $schema
     * @param string      $i
     */
    protected function validate_of_properties(&$value, $schema, Json_Pointer $path, $i = '')
    {
        // Verify type
        if ($value instanceof self) {
            return;
        }
        if (isset($schema->all_of)) {
            $is_valid = true;
            foreach ($schema->all_of as $all_of) {
                $init_errors = $this->get_errors();
                $this->check_undefined($value, $all_of, $path, $i);
                $is_valid = $is_valid && count($this->get_errors()) == count($init_errors);
            }
            if (!$is_valid) {
                $this->add_error(Constraint_Error::ALL_OF(), $path);
            }
        }
        if (isset($schema->any_of)) {
            $is_valid = false;
            $start_errors = $this->get_errors();
            $coerce_or_defaults = $this->factory->get_config(self::CHECK_MODE_COERCE_TYPES | self::CHECK_MODE_APPLY_DEFAULTS);
            foreach ($schema->any_of as $any_of) {
                $init_errors = $this->get_errors();
                try {
                    $any_of_value = $coerce_or_defaults ? Deep_Copy::copy_of($value) : $value;
                    $this->check_undefined($any_of_value, $any_of, $path, $i);
                    if ($is_valid = count($this->get_errors()) === count($init_errors)) {
                        $value = $any_of_value;
                        break;
                    }
                } catch (Validation_Exception $e) {
                    $is_valid = false;
                }
            }
            if (!$is_valid) {
                $this->add_error(Constraint_Error::ANY_OF(), $path);
            } else {
                $this->errors = $start_errors;
            }
        }
        if (isset($schema->one_of)) {
            $all_errors = [];
            $matched_schemas = [];
            $start_errors = $this->get_errors();
            $coerce_or_defaults = $this->factory->get_config(self::CHECK_MODE_COERCE_TYPES | self::CHECK_MODE_APPLY_DEFAULTS);
            foreach ($schema->one_of as $one_of) {
                try {
                    $this->errors = [];
                    $one_of_value = $coerce_or_defaults ? Deep_Copy::copy_of($value) : $value;
                    $this->check_undefined($one_of_value, $one_of, $path, $i);
                    if (count($this->get_errors()) === 0) {
                        $matched_schemas[] = ['schema' => $one_of, 'value' => $one_of_value];
                    }
                    $all_errors = array_merge($all_errors, array_values($this->get_errors()));
                } catch (Validation_Exception $e) {
                    // deliberately do nothing here - validation failed, but we want to check
                    // other schema options in the OneOf field.
                }
            }
            if (count($matched_schemas) !== 1) {
                $this->add_errors(array_merge($all_errors, $start_errors));
                $this->add_error(Constraint_Error::ONE_OF(), $path);
            } else {
                $this->errors = $start_errors;
                $value = $matched_schemas[0]['value'];
            }
        }
    }
    /**
     * Validate dependencies
     *
     * @param mixed       $value
     * @param mixed       $dependencies
     * @param string      $i
     */
    protected function validate_dependencies($value, $dependencies, Json_Pointer $path, $i = '')
    {
        foreach ($dependencies as $key => $dependency) {
            if ($this->get_type_check()->property_exists($value, $key)) {
                if (is_string($dependency)) {
                    // Draft 3 string is allowed - e.g. "dependencies": {"bar": "foo"}
                    if (!$this->get_type_check()->property_exists($value, $dependency)) {
                        $this->add_error(Constraint_Error::DEPENDENCIES(), $path, ['key' => $key, 'dependency' => $dependency]);
                    }
                } elseif (is_array($dependency)) {
                    // Draft 4 must be an array - e.g. "dependencies": {"bar": ["foo"]}
                    foreach ($dependency as $d) {
                        if (!$this->get_type_check()->property_exists($value, $d)) {
                            $this->add_error(Constraint_Error::DEPENDENCIES(), $path, ['key' => $key, 'dependency' => $dependency]);
                        }
                    }
                } elseif (is_object($dependency)) {
                    // Schema - e.g. "dependencies": {"bar": {"properties": {"foo": {...}}}}
                    $this->check_undefined($value, $dependency, $path, $i);
                }
            }
        }
    }
    protected function validate_uri($schema, $schema_uri = null)
    {
        $resolver = new Uri_Resolver();
        $retriever = $this->factory->get_uri_retriever();
        $json_schema = null;
        if ($resolver->is_valid($schema_uri)) {
            $schema_id = property_exists($schema, 'id') ? $schema->id : null;
            $json_schema = $retriever->retrieve($schema_id, $schema_uri);
        }
        return $json_schema;
    }
}