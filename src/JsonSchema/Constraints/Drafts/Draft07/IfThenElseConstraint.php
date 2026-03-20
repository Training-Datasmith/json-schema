<?php

declare (strict_types=1);
namespace Json_Schema\Constraints\Drafts\Draft07;

use Json_Schema\Constraints\Constraint_Interface;
use Json_Schema\Entity\Error_Bag_Proxy;
use Json_Schema\Entity\Json_Pointer;
class If_Then_Else_Constraint implements Constraint_Interface
{
    use Error_Bag_Proxy;
    /** @var Factory */
    private $factory;
    public function __construct(?Factory $factory = null)
    {
        $this->factory = $factory ?: new Factory();
        $this->initialise_error_bag($this->factory);
    }
    public function check(&$value, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        if (!property_exists($schema, 'if')) {
            return;
        }
        $schema_constraint = $this->factory->create_instance_for('schema');
        $if_schema = $schema->if;
        if (!is_bool($if_schema)) {
            $schema_constraint->check($value, $if_schema, $path, $i);
            $meets_if_conditions = $schema_constraint->is_valid();
            $schema_constraint->reset();
        } else {
            $meets_if_conditions = $if_schema;
        }
        if ($meets_if_conditions) {
            if (!property_exists($schema, 'then')) {
                return;
            }
            $schema_constraint->check($value, $schema->then, $path, $i);
            if ($schema_constraint->is_valid()) {
                return;
            }
            $this->add_errors($schema_constraint->get_errors());
            return;
        }
        if (!property_exists($schema, 'else')) {
            return;
        }
        $schema_constraint->check($value, $schema->else, $path, $i);
        if ($schema_constraint->is_valid()) {
            return;
        }
        $this->add_errors($schema_constraint->get_errors());
    }
}