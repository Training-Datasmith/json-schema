<?php

declare (strict_types=1);
namespace Json_Schema\Constraints\Drafts\Draft07;

use Json_Schema\Constraint_Error;
use Json_Schema\Constraints\Constraint_Interface;
use Json_Schema\Entity\Error_Bag_Proxy;
use Json_Schema\Entity\Json_Pointer;
class Additional_Items_Constraint implements Constraint_Interface
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
        if (!property_exists($schema, 'additionalItems')) {
            return;
        }
        if ($schema->additional_items === true) {
            return;
        }
        if ($schema->additional_items === false && !property_exists($schema, 'items')) {
            return;
        }
        if (!is_array($value)) {
            return;
        }
        if (!property_exists($schema, 'items')) {
            return;
        }
        if (property_exists($schema, 'items') && is_object($schema->items)) {
            return;
        }
        $additional_items = array_diff_key($value, property_exists($schema, 'items') ? $schema->items : []);
        foreach ($additional_items as $property_name => $property_value) {
            $schema_constraint = $this->factory->create_instance_for('schema');
            $schema_constraint->check($property_value, $schema->additional_items, $path, $i);
            if ($schema_constraint->is_valid()) {
                continue;
            }
            $this->add_error(Constraint_Error::ADDITIONAL_ITEMS(), $path, ['item' => $i, 'property' => $property_name, 'additionalItems' => $schema->additional_items]);
        }
    }
}