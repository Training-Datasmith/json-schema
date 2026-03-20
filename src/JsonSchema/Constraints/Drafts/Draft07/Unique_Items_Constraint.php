<?php

declare (strict_types=1);
namespace Json_Schema\Constraints\Drafts\Draft07;

use Json_Schema\Constraint_Error;
use Json_Schema\Constraints\Constraint_Interface;
use Json_Schema\Constraints\Factory;
use Json_Schema\Entity\Error_Bag_Proxy;
use Json_Schema\Entity\Json_Pointer;
use Json_Schema\Tool\Deep_Comparer;
class Unique_Items_Constraint implements Constraint_Interface
{
    use Error_Bag_Proxy;
    public function __construct(?Factory $factory = null)
    {
        $this->initialise_error_bag($factory ?: new Factory());
    }
    public function check(&$value, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        if (!property_exists($schema, 'uniqueItems')) {
            return;
        }
        if (!is_array($value)) {
            return;
        }
        if ($schema->unique_items !== true) {
            // If unique items not is true duplicates are allowed.
            return;
        }
        $count = count($value);
        for ($x = 0; $x < $count - 1; $x++) {
            for ($y = $x + 1; $y < $count; $y++) {
                if (Deep_Comparer::is_equal($value[$x], $value[$y])) {
                    $this->add_error(Constraint_Error::UNIQUE_ITEMS(), $path);
                    return;
                }
            }
        }
    }
}