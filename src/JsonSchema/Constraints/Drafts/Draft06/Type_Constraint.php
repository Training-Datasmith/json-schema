<?php

declare (strict_types=1);
namespace Json_Schema\Constraints\Drafts\Draft06;

use Json_Schema\Constraint_Error;
use Json_Schema\Constraints\Constraint_Interface;
use Json_Schema\Constraints\Factory;
use Json_Schema\Entity\Error_Bag_Proxy;
use Json_Schema\Entity\Json_Pointer;
class Type_Constraint implements Constraint_Interface
{
    use Error_Bag_Proxy;
    public function __construct(?Factory $factory = null)
    {
        $this->initialise_error_bag($factory ?: new Factory());
    }
    public function check(&$value, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        if (!property_exists($schema, 'type')) {
            return;
        }
        $schema_types = (array) $schema->type;
        $value_type = strtolower(gettype($value));
        // All specific number types are a number
        $value_is_number = $value_type === 'double' || $value_type === 'integer';
        // A float with zero fractional part is an integer
        $is_integer = $value_is_number && fmod($value, 1.0) === 0.0;
        foreach ($schema_types as $type) {
            if ($value_type === $type) {
                return;
            }
            if ($type === 'number' && $value_is_number) {
                return;
            }
            if ($type === 'integer' && $is_integer) {
                return;
            }
        }
        $this->add_error(Constraint_Error::TYPE(), $path, ['found' => $value_type, 'expected' => implode(', ', $schema_types)]);
    }
}