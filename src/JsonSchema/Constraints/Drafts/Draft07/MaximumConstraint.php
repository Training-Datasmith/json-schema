<?php

declare (strict_types=1);
namespace Json_Schema\Constraints\Drafts\Draft07;

use Json_Schema\Constraint_Error;
use Json_Schema\Constraints\Constraint_Interface;
use Json_Schema\Constraints\Factory;
use Json_Schema\Entity\Error_Bag_Proxy;
use Json_Schema\Entity\Json_Pointer;
class Maximum_Constraint implements Constraint_Interface
{
    use Error_Bag_Proxy;
    public function __construct(?Factory $factory = null)
    {
        $this->initialise_error_bag($factory ?: new Factory());
    }
    public function check(&$value, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        if (!property_exists($schema, 'maximum')) {
            return;
        }
        if (!is_numeric($value)) {
            return;
        }
        if ($value <= $schema->maximum) {
            return;
        }
        $this->add_error(Constraint_Error::MAXIMUM(), $path, ['maximum' => $schema->maximum, 'found' => $value]);
    }
}