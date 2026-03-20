<?php

declare (strict_types=1);
/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Json_Schema;

use Json_Schema\Constraints\Base_Constraint;
use Json_Schema\Constraints\Constraint;
use Json_Schema\Constraints\Type_Check\Loose_Type_Check;
/**
 * A JsonSchema Constraint
 *
 * @author Robert Schönthal <seroscho@googlemail.com>
 * @author Bruno Prieto Reis <bruno.p.reis@gmail.com>
 *
 * @see    README.md
 */
class Validator extends Base_Constraint
{
    public const SCHEMA_MEDIA_TYPE = 'application/schema+json';
    public const ERROR_NONE = 0;
    public const ERROR_ALL = -1;
    public const ERROR_DOCUMENT_VALIDATION = 1;
    public const ERROR_SCHEMA_VALIDATION = 2;
    /**
     * Validates the given data against the schema and returns an object containing the results
     * Both the php object and the schema are supposed to be a result of a json_decode call.
     * The validation works as defined by the schema proposal in http://json-schema.org.
     *
     * Note that the first argument is passed by reference, so you must pass in a variable.
     *
     * @param mixed $value
     * @param mixed $schema
     *
     * @phpstan-param int-mask-of<Constraint::CHECK_MODE_*> $checkMode
     * @phpstan-return int-mask-of<Validator::ERROR_*>
     */
    public function validate(&$value, $schema = null, ?int $check_mode = null): int
    {
        // reset errors prior to validation
        $this->reset();
        // set checkMode
        $initial_check_mode = $this->factory->get_config();
        if ($check_mode !== null) {
            $this->factory->set_config($check_mode);
        }
        // add provided schema to SchemaStorage with internal URI to allow internal $ref resolution
        $schema_uri = Schema_Storage::INTERNAL_PROVIDED_SCHEMA_URI;
        if (Loose_Type_Check::property_exists($schema, 'id')) {
            $schema_uri = Loose_Type_Check::property_get($schema, 'id');
        }
        if (Loose_Type_Check::property_exists($schema, '$id')) {
            $schema_uri = Loose_Type_Check::property_get($schema, '$id');
        }
        $this->factory->get_schema_storage()->add_schema($schema_uri, $schema);
        $validator = $this->factory->create_instance_for('schema');
        $schema = $this->factory->get_schema_storage()->get_schema($schema_uri);
        // Boolean schema requires no further validation
        if (is_bool($schema)) {
            if ($schema === false) {
                $this->add_error(Constraint_Error::FALSE());
            }
            return $this->get_error_mask();
        }
        if ($this->factory->get_config(Constraint::CHECK_MODE_STRICT)) {
            $dialect = $this->factory->get_default_dialect();
            if (property_exists($schema, '$schema')) {
                $dialect = $schema->{'$schema'};
            }
            $validator = $this->factory->create_instance_for(Draft_Identifiers::by_value($dialect)->to_constraint_name());
        }
        $validator->check($value, $schema);
        $this->factory->set_config($initial_check_mode);
        $this->add_errors(array_unique($validator->get_errors(), SORT_REGULAR));
        return $validator->get_error_mask();
    }
    /**
     * Alias to validate(), to maintain backwards-compatibility with the previous API
     *
     * @deprecated since 6.0.0, use Validator::validate() instead, to be removed in 7.0
     *
     * @param mixed $value
     * @param mixed $schema
     *
     * @phpstan-return int-mask-of<Validator::ERROR_*>
     */
    public function check($value, $schema): int
    {
        return $this->validate($value, $schema);
    }
    /**
     * Alias to validate(), to maintain backwards-compatibility with the previous API
     *
     * @deprecated since 6.0.0, use Validator::validate() instead, to be removed in 7.0
     *
     * @param mixed $value
     * @param mixed $schema
     *
     * @phpstan-return int-mask-of<Validator::ERROR_*>
     */
    public function coerce(&$value, $schema): int
    {
        return $this->validate($value, $schema, Constraint::CHECK_MODE_COERCE_TYPES);
    }
}