<?php

declare (strict_types=1);
namespace Json_Schema\Constraints\Drafts\Draft07;

use Json_Schema\Constraint_Error;
use Json_Schema\Constraints\Constraint_Interface;
use Json_Schema\Entity\Error_Bag_Proxy;
use Json_Schema\Entity\Json_Pointer;
class Dependencies_Constraint implements Constraint_Interface
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
        if (!property_exists($schema, 'dependencies')) {
            return;
        }
        if (!is_object($value)) {
            return;
        }
        foreach ($schema->dependencies as $dependant => $dependencies) {
            if (!property_exists($value, $dependant)) {
                continue;
            }
            if ($dependencies === true) {
                continue;
            }
            if ($dependencies === false) {
                $this->add_error(Constraint_Error::FALSE(), $path, ['dependant' => $dependant]);
                continue;
            }
            if (is_array($dependencies)) {
                foreach ($dependencies as $dependency) {
                    if (property_exists($value, $dependant) && !property_exists($value, $dependency)) {
                        $this->add_error(Constraint_Error::DEPENDENCIES(), $path, ['dependant' => $dependant, 'dependency' => $dependency]);
                    }
                }
            }
            if (is_object($dependencies)) {
                $schema_constraint = $this->factory->create_instance_for('schema');
                $schema_constraint->check($value, $dependencies, $path, $i);
                if (!$schema_constraint->is_valid()) {
                    $this->add_errors($schema_constraint->get_errors());
                }
            }
        }
    }
}