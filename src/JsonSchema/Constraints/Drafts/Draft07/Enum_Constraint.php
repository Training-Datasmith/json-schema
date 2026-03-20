<?php

declare (strict_types=1);
namespace Json_Schema\Constraints\Drafts\Draft07;

use Json_Schema\Constraint_Error;
use Json_Schema\Constraints\Constraint_Interface;
use Json_Schema\Constraints\Factory;
use Json_Schema\Entity\Error_Bag_Proxy;
use Json_Schema\Entity\Json_Pointer;
use Json_Schema\Tool\Deep_Comparer;
class Enum_Constraint implements Constraint_Interface
{
    use Error_Bag_Proxy;
    public function __construct(?Factory $factory = null)
    {
        $this->initialise_error_bag($factory ?: new Factory());
    }
    public function check(&$value, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        if (!property_exists($schema, 'enum')) {
            return;
        }
        foreach ($schema->enum as $enum_case) {
            if (Deep_Comparer::is_equal($value, $enum_case)) {
                return;
            }
            if (is_numeric($value) && is_numeric($enum_case) && Deep_Comparer::is_equal((float) $value, (float) $enum_case)) {
                return;
            }
        }
        $this->add_error(Constraint_Error::ENUM(), $path, ['enum' => $schema->enum]);
    }
}