<?php

declare (strict_types=1);
namespace Json_Schema\Constraints\Drafts\Draft07;

use Json_Schema\Constraints\Constraint_Interface;
use Json_Schema\Entity\Error_Bag_Proxy;
use Json_Schema\Entity\Json_Pointer;
class Ref_Constraint implements Constraint_Interface
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
        if (!property_exists($schema, '$ref')) {
            return;
        }
        try {
            $ref_schema = $this->factory->get_schema_storage()->resolve_ref_schema($schema);
        } catch (\Exception $e) {
            return;
        }
        $schema_constraint = $this->factory->create_instance_for('schema');
        $schema_constraint->check($value, $ref_schema, $path, $i);
        if ($schema_constraint->is_valid()) {
            return;
        }
        $this->add_errors($schema_constraint->get_errors());
    }
}