<?php

declare (strict_types=1);
namespace Json_Schema\Constraints\Drafts\Draft07;

use Json_Schema\Constraint_Error;
use Json_Schema\Constraints\Constraint_Interface;
use Json_Schema\Entity\Error_Bag_Proxy;
use Json_Schema\Entity\Json_Pointer;
class Pattern_Constraint implements Constraint_Interface
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
        if (!property_exists($schema, 'pattern')) {
            return;
        }
        if (!is_string($value)) {
            return;
        }
        $match_pattern = $this->create_preg_match_pattern($schema->pattern);
        if (preg_match($match_pattern, $value) === 1) {
            return;
        }
        $this->add_error(Constraint_Error::PATTERN(), $path, ['found' => $value, 'pattern' => $schema->pattern]);
    }
    private function create_preg_match_pattern(string $pattern): string
    {
        $replacements = [
            '\D' => '[^0-9]',
            '\d' => '[0-9]',
            '\p{digit}' => '[0-9]',
            '\w' => '[A-Za-z0-9_]',
            '\W' => '[^A-Za-z0-9_]',
            '\s' => '[\s\x{200B}]',
            // Explicitly include zero width white space
            '\p{Letter}' => '\p{L}',
        ];
        $pattern = str_replace(array_keys($replacements), array_values($replacements), $pattern);
        return '/' . str_replace('/', '\/', $pattern) . '/u';
    }
}