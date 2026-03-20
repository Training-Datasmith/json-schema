<?php

declare (strict_types=1);
/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Json_Schema\Constraints;

use Json_Schema\Constraint_Error;
use Json_Schema\Entity\Json_Pointer;
use Json_Schema\Tool\Deep_Comparer;
/**
 * The EnumConstraint Constraints, validates an element against a given set of possibilities
 *
 * @author Robert Schönthal <seroscho@googlemail.com>
 * @author Bruno Prieto Reis <bruno.p.reis@gmail.com>
 */
class Enum_Constraint extends Constraint
{
    /**
     * {@inheritdoc}
     */
    public function check(&$element, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        // Only validate enum if the attribute exists
        if ($element instanceof Undefined_Constraint && (!isset($schema->required) || !$schema->required)) {
            return;
        }
        $type = gettype($element);
        foreach ($schema->enum as $enum) {
            $enum_type = gettype($enum);
            if ($enum_type === 'object' && $type === 'array' && $this->factory->get_config(self::CHECK_MODE_TYPE_CAST) && Deep_Comparer::is_equal((object) $element, $enum)) {
                return;
            }
            if ($type === $enum_type && Deep_Comparer::is_equal($element, $enum)) {
                return;
            }
            if (is_numeric($element) && is_numeric($enum) && Deep_Comparer::is_equal((float) $element, (float) $enum)) {
                return;
            }
        }
        $this->add_error(Constraint_Error::ENUM(), $path, ['enum' => $schema->enum]);
    }
}