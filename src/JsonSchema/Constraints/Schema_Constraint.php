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
use Json_Schema\Draft_Identifiers;
use Json_Schema\Entity\Json_Pointer;
use Json_Schema\Exception\InvalidArgumentException;
use Json_Schema\Exception\Invalid_Schema_Exception;
use Json_Schema\Exception\RuntimeException;
use Json_Schema\Validator;
/**
 * The SchemaConstraint Constraints, validates an element against a given schema
 *
 * @author Robert Schönthal <seroscho@googlemail.com>
 * @author Bruno Prieto Reis <bruno.p.reis@gmail.com>
 */
class Schema_Constraint extends Constraint
{
    private const DEFAULT_SCHEMA_SPEC = Draft_Identifiers::DRAFT_4;
    /**
     * {@inheritdoc}
     */
    public function check(&$element, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        if ($schema !== null) {
            // passed schema
            $validation_schema = $schema;
        } elseif ($this->get_type_check()->property_exists($element, $this->inline_schema_property)) {
            // inline schema
            $validation_schema = $this->get_type_check()->property_get($element, $this->inline_schema_property);
        } else {
            throw new InvalidArgumentException('no schema found to verify against');
        }
        // cast array schemas to object
        if (is_array($validation_schema)) {
            $validation_schema = Base_Constraint::array_to_object_recursive($validation_schema);
        }
        // validate schema against whatever is defined in $validationSchema->$schema. If no
        // schema is defined, assume self::DEFAULT_SCHEMA_SPEC (currently draft-04).
        if ($this->factory->get_config(self::CHECK_MODE_VALIDATE_SCHEMA)) {
            if (!$this->get_type_check()->is_object($validation_schema)) {
                throw new RuntimeException('Cannot validate the schema of a non-object');
            }
            if ($this->get_type_check()->property_exists($validation_schema, '$schema')) {
                $schema_spec = $this->get_type_check()->property_get($validation_schema, '$schema');
            } else {
                $schema_spec = self::DEFAULT_SCHEMA_SPEC;
            }
            // get the spec schema
            $schema_storage = $this->factory->get_schema_storage();
            if (!$this->get_type_check()->is_object($schema_spec)) {
                $schema_spec = $schema_storage->get_schema($schema_spec);
            }
            // save error count, config & subtract CHECK_MODE_VALIDATE_SCHEMA
            $initial_error_count = $this->num_errors();
            $initial_config = $this->factory->get_config();
            $initial_context = $this->factory->get_error_context();
            $this->factory->remove_config(self::CHECK_MODE_VALIDATE_SCHEMA | self::CHECK_MODE_APPLY_DEFAULTS);
            $this->factory->add_config(self::CHECK_MODE_TYPE_CAST);
            $this->factory->set_error_context(Validator::ERROR_SCHEMA_VALIDATION);
            // validate schema
            try {
                $this->check($validation_schema, $schema_spec);
            } catch (\Exception $e) {
                if ($this->factory->get_config(self::CHECK_MODE_EXCEPTIONS)) {
                    throw new Invalid_Schema_Exception('Schema did not pass validation', 0, $e);
                }
            }
            if ($this->num_errors() > $initial_error_count) {
                $this->add_error(Constraint_Error::INVALID_SCHEMA(), $path);
            }
            // restore the initial config
            $this->factory->set_config($initial_config);
            $this->factory->set_error_context($initial_context);
        }
        // validate element against $validationSchema
        $this->check_undefined($element, $validation_schema, $path, $i);
    }
}