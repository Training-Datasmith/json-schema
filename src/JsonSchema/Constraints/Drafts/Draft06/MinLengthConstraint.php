<?php

declare (strict_types=1);
namespace Json_Schema\Constraints\Drafts\Draft06;

use Json_Schema\Constraint_Error;
use Json_Schema\Constraints\Constraint_Interface;
use Json_Schema\Constraints\Factory;
use Json_Schema\Entity\Error_Bag_Proxy;
use Json_Schema\Entity\Json_Pointer;
class Min_Length_Constraint implements Constraint_Interface
{
    use Error_Bag_Proxy;
    public function __construct(?Factory $factory = null)
    {
        $this->initialise_error_bag($factory ?: new Factory());
    }
    public function check(&$value, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        if (!property_exists($schema, 'minLength')) {
            return;
        }
        if (!is_string($value)) {
            return;
        }
        $length = mb_strlen($value);
        if ($length >= $schema->min_length) {
            return;
        }
        $this->add_error(Constraint_Error::LENGTH_MIN(), $path, ['minLength' => $schema->min_length, 'found' => $length]);
    }
}