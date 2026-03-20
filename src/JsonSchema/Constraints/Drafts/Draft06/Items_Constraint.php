<?php

declare (strict_types=1);
namespace Json_Schema\Constraints\Drafts\Draft06;

use Json_Schema\Constraints\Constraint_Interface;
use Json_Schema\Entity\Error_Bag_Proxy;
use Json_Schema\Entity\Json_Pointer;
class Items_Constraint implements Constraint_Interface
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
        if (!property_exists($schema, 'items')) {
            return;
        }
        if (!is_array($value)) {
            return;
        }
        foreach ($value as $property_name => $property_value) {
            $item_schema = $schema->items;
            if (is_array($item_schema)) {
                if (!array_key_exists($property_name, $item_schema)) {
                    continue;
                }
                $item_schema = $item_schema[$property_name];
            }
            $schema_constraint = $this->factory->create_instance_for('schema');
            $schema_constraint->check($property_value, $item_schema, $path, $i);
            if ($schema_constraint->is_valid()) {
                continue;
            }
            $this->add_errors($schema_constraint->get_errors());
        }
    }
}