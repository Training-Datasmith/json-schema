<?php

declare (strict_types=1);
namespace Json_Schema\Constraints\Drafts\Draft06;

use Json_Schema\Constraints\Constraint_Interface;
use Json_Schema\Entity\Error_Bag_Proxy;
use Json_Schema\Entity\Json_Pointer;
class Pattern_Properties_Constraint implements Constraint_Interface
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
        if (!property_exists($schema, 'patternProperties')) {
            return;
        }
        if (!is_object($value)) {
            return;
        }
        $properties = get_object_vars($value);
        foreach ($properties as $property_name => $property_value) {
            foreach ($schema->pattern_properties as $pattern_property_regex => $pattern_property_schema) {
                $match_pattern = $this->create_preg_match_pattern($pattern_property_regex);
                if (preg_match($match_pattern, (string) $property_name)) {
                    $schema_constraint = $this->factory->create_instance_for('schema');
                    $schema_constraint->check($property_value, $pattern_property_schema, $path, $i);
                    if ($schema_constraint->is_valid()) {
                        continue;
                    }
                    $this->add_errors($schema_constraint->get_errors());
                }
            }
        }
    }
    private function create_preg_match_pattern(string $pattern): string
    {
        $replacements = [
            //            '\D' => '[^0-9]',
            '\d' => '[0-9]',
            '\p{digit}' => '[0-9]',
            //            '\w' => '[A-Za-z0-9_]',
            //            '\W' => '[^A-Za-z0-9_]',
            //            '\s' => '[\s\x{200B}]' // Explicitly include zero width white space
            '\p{Letter}' => '\p{L}',
        ];
        $pattern = str_replace(array_keys($replacements), array_values($replacements), $pattern);
        return '/' . str_replace('/', '\/', $pattern) . '/u';
    }
}