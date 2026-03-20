<?php

declare (strict_types=1);
namespace Json_Schema\Constraints\Drafts\Draft06;

use Json_Schema\Constraint_Error;
use Json_Schema\Constraints\Constraint_Interface;
use Json_Schema\Constraints\Factory;
use Json_Schema\Entity\Error_Bag_Proxy;
use Json_Schema\Entity\Json_Pointer;
class Required_Constraint implements Constraint_Interface
{
    use Error_Bag_Proxy;
    public function __construct(?Factory $factory = null)
    {
        $this->initialise_error_bag($factory ?: new Factory());
    }
    public function check(&$value, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        if (!property_exists($schema, 'required')) {
            return;
        }
        if (!is_object($value)) {
            return;
        }
        foreach ($schema->required as $required) {
            if (property_exists($value, $required)) {
                continue;
            }
            $this->add_error(Constraint_Error::REQUIRED(), $this->increment_path($path, $required), ['property' => $required]);
        }
    }
    /**
     * @todo refactor as this was only copied from UndefinedConstraint
     * Bubble down the path
     *
     * @param JsonPointer|null $path Current path
     * @param mixed            $i    What to append to the path
     */
    protected function increment_path(?Json_Pointer $path, $i): Json_Pointer
    {
        $path = $path ?? new Json_Pointer('');
        if ($i === null || $i === '') {
            return $path;
        }
        return $path->with_property_paths(array_merge($path->get_property_paths(), [$i]));
    }
}