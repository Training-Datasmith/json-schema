<?php

declare (strict_types=1);
namespace Json_Schema\Constraints\Drafts\Draft06;

use Json_Schema\Constraint_Error;
use Json_Schema\Constraints\Constraint_Interface;
use Json_Schema\Constraints\Factory;
use Json_Schema\Entity\Error_Bag_Proxy;
use Json_Schema\Entity\Json_Pointer;
use Json_Schema\Tool\Deep_Comparer;
class Const_Constraint implements Constraint_Interface
{
    use Error_Bag_Proxy;
    public function __construct(?Factory $factory = null)
    {
        $this->initialise_error_bag($factory ?: new Factory());
    }
    public function check(&$value, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        if (!property_exists($schema, 'const')) {
            return;
        }
        if (Deep_Comparer::is_equal($value, $schema->const)) {
            return;
        }
        $this->add_error(Constraint_Error::CONSTANT(), $path, ['const' => $schema->const]);
    }
}