<?php

declare (strict_types=1);
namespace Json_Schema\Constraints\Drafts\Draft07;

use Json_Schema\Constraint_Error;
use Json_Schema\Constraints\Constraint_Interface;
use Json_Schema\Entity\Error_Bag_Proxy;
use Json_Schema\Entity\Json_Pointer;
class Additional_Properties_Constraint implements Constraint_Interface
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
        if (!property_exists($schema, 'additionalProperties')) {
            return;
        }
        if ($schema->additional_properties === true) {
            return;
        }
        if (!is_object($value)) {
            return;
        }
        $additional_properties = get_object_vars($value);
        if (isset($schema->properties)) {
            $additional_properties = array_diff_key($additional_properties, (array) $schema->properties);
        }
        if (isset($schema->pattern_properties)) {
            $patterns = array_keys(get_object_vars($schema->pattern_properties));
            foreach ($additional_properties as $key => $_) {
                foreach ($patterns as $pattern) {
                    if (preg_match($this->create_preg_match_pattern($pattern), (string) $key)) {
                        unset($additional_properties[$key]);
                        break;
                    }
                }
            }
        }
        if (is_object($schema->additional_properties)) {
            foreach ($additional_properties as $key => $additional_properties_value) {
                $schema_constraint = $this->factory->create_instance_for('schema');
                $schema_constraint->check($additional_properties_value, $schema->additional_properties, $path, $i);
                // @todo increment path
                if ($schema_constraint->is_valid()) {
                    unset($additional_properties[$key]);
                }
            }
        }
        foreach ($additional_properties as $additional_properties_value) {
            $this->add_error(Constraint_Error::ADDITIONAL_PROPERTIES(), $path, ['found' => $additional_properties_value]);
        }
    }
    private function create_preg_match_pattern(string $pattern): string
    {
        $replacements = [
            //            '\D' => '[^0-9]',
            //            '\d' => '[0-9]',
            '\p{digit}' => '\p{Nd}',
            //            '\w' => '[A-Za-z0-9_]',
            //            '\W' => '[^A-Za-z0-9_]',
            //            '\s' => '[\s\x{200B}]' // Explicitly include zero width white space,
            '\p{Letter}' => '\p{L}',
        ];
        $pattern = str_replace(array_keys($replacements), array_values($replacements), $pattern);
        return '/' . str_replace('/', '\/', $pattern) . '/u';
    }
}