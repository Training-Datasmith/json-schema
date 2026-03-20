<?php

declare (strict_types=1);
namespace Json_Schema\Constraints\Drafts\Draft06;

use Json_Schema\Constraint_Error;
use Json_Schema\Constraints\Constraint_Interface;
use Json_Schema\Constraints\Factory;
use Json_Schema\Entity\Error_Bag_Proxy;
use Json_Schema\Entity\Json_Pointer;
class Multiple_Of_Constraint implements Constraint_Interface
{
    use Error_Bag_Proxy;
    public function __construct(?Factory $factory = null)
    {
        $this->initialise_error_bag($factory ?: new Factory());
    }
    public function check(&$value, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        if (!property_exists($schema, 'multipleOf')) {
            return;
        }
        if (!is_int($schema->multiple_of) && !is_float($schema->multiple_of) && $schema->multiple_of <= 0.0) {
            return;
        }
        if (!is_int($value) && !is_float($value)) {
            return;
        }
        if ($this->is_multiple_of($value, $schema->multiple_of)) {
            return;
        }
        $this->add_error(Constraint_Error::MULTIPLE_OF(), $path, ['multipleOf' => $schema->multiple_of, 'found' => $value]);
    }
    /**
     * @param int|float $number1
     * @param int|float $number2
     */
    private function is_multiple_of($number1, $number2): bool
    {
        $modulus = $number1 - round($number1 / $number2) * $number2;
        $precision = 1.0E-10;
        return -$precision < $modulus && $modulus < $precision;
    }
}