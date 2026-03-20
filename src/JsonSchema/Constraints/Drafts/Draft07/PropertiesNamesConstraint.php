<?php

declare (strict_types=1);
namespace Json_Schema\Constraints\Drafts\Draft07;

use Json_Schema\Constraint_Error;
use Json_Schema\Constraints\Constraint_Interface;
use Json_Schema\Entity\Error_Bag_Proxy;
use Json_Schema\Entity\Json_Pointer;
class Properties_Names_Constraint implements Constraint_Interface
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
        if (!property_exists($schema, 'propertyNames')) {
            return;
        }
        if (!is_object($value)) {
            return;
        }
        if ($schema->property_names === true) {
            return;
        }
        $property_names = get_object_vars($value);
        if ($schema->property_names === false) {
            foreach ($property_names as $property_name => $_) {
                $this->add_error(Constraint_Error::PROPERTY_NAMES(), $path, ['propertyNames' => $schema->property_names, 'violating' => 'false', 'name' => $property_name]);
            }
            return;
        }
        if (property_exists($schema->property_names, 'maxLength')) {
            foreach ($property_names as $property_name => $_) {
                $length = mb_strlen($property_name);
                if ($length > $schema->property_names->max_length) {
                    $this->add_error(Constraint_Error::PROPERTY_NAMES(), $path, ['propertyNames' => $schema->property_names, 'violating' => 'maxLength', 'length' => $length, 'name' => $property_name]);
                }
            }
        }
        if (property_exists($schema->property_names, 'pattern')) {
            foreach ($property_names as $property_name => $_) {
                if (!preg_match('/' . str_replace('/', '\/', $schema->property_names->pattern) . '/', $property_name)) {
                    $this->add_error(Constraint_Error::PROPERTY_NAMES(), $path, ['propertyNames' => $schema->property_names, 'violating' => 'pattern', 'name' => $property_name]);
                }
            }
        }
    }
}