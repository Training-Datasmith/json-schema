<?php

declare (strict_types=1);
namespace Json_Schema\Constraints\Drafts\Draft07;

use Json_Schema\Constraint_Error;
use Json_Schema\Constraints\Constraint;
use Json_Schema\Entity\Json_Pointer;
use Json_Schema\Schema_Storage;
use Json_Schema\Uri\Uri_Retriever;
class Draft07Constraint extends Constraint
{
    public function __construct(?\Json_Schema\Constraints\Factory $factory = null)
    {
        parent::__construct(new Factory($factory ? $factory->get_schema_storage() : new Schema_Storage(), $factory ? $factory->get_uri_retriever() : new Uri_Retriever(), $factory ? $factory->get_config() : Constraint::CHECK_MODE_NORMAL));
    }
    public function check(&$value, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        if (is_bool($schema)) {
            if ($schema === false) {
                $this->add_error(Constraint_Error::FALSE(), $path, []);
            }
            return;
        }
        // Apply defaults
        $this->check_for_keyword('ref', $value, $schema, $path, $i);
        $this->check_for_keyword('required', $value, $schema, $path, $i);
        $this->check_for_keyword('contains', $value, $schema, $path, $i);
        $this->check_for_keyword('properties', $value, $schema, $path, $i);
        $this->check_for_keyword('propertyNames', $value, $schema, $path, $i);
        $this->check_for_keyword('patternProperties', $value, $schema, $path, $i);
        $this->check_for_keyword('type', $value, $schema, $path, $i);
        $this->check_for_keyword('not', $value, $schema, $path, $i);
        $this->check_for_keyword('dependencies', $value, $schema, $path, $i);
        $this->check_for_keyword('allOf', $value, $schema, $path, $i);
        $this->check_for_keyword('anyOf', $value, $schema, $path, $i);
        $this->check_for_keyword('oneOf', $value, $schema, $path, $i);
        $this->check_for_keyword('ifThenElse', $value, $schema, $path, $i);
        $this->check_for_keyword('additionalProperties', $value, $schema, $path, $i);
        $this->check_for_keyword('items', $value, $schema, $path, $i);
        $this->check_for_keyword('additionalItems', $value, $schema, $path, $i);
        $this->check_for_keyword('uniqueItems', $value, $schema, $path, $i);
        $this->check_for_keyword('minItems', $value, $schema, $path, $i);
        $this->check_for_keyword('minProperties', $value, $schema, $path, $i);
        $this->check_for_keyword('maxProperties', $value, $schema, $path, $i);
        $this->check_for_keyword('minimum', $value, $schema, $path, $i);
        $this->check_for_keyword('maximum', $value, $schema, $path, $i);
        $this->check_for_keyword('minLength', $value, $schema, $path, $i);
        $this->check_for_keyword('exclusiveMinimum', $value, $schema, $path, $i);
        $this->check_for_keyword('maxItems', $value, $schema, $path, $i);
        $this->check_for_keyword('maxLength', $value, $schema, $path, $i);
        $this->check_for_keyword('exclusiveMaximum', $value, $schema, $path, $i);
        $this->check_for_keyword('enum', $value, $schema, $path, $i);
        $this->check_for_keyword('const', $value, $schema, $path, $i);
        $this->check_for_keyword('multipleOf', $value, $schema, $path, $i);
        $this->check_for_keyword('format', $value, $schema, $path, $i);
        $this->check_for_keyword('pattern', $value, $schema, $path, $i);
        $this->check_for_keyword('content', $value, $schema, $path, $i);
    }
    /**
     * @param mixed $value
     * @param mixed $schema
     * @param mixed $i
     */
    protected function check_for_keyword(string $keyword, $value, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        $validator = $this->factory->create_instance_for($keyword);
        $validator->check($value, $schema, $path, $i);
        $this->add_errors($validator->get_errors());
    }
}