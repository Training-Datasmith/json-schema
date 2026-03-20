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
 * The ConstConstraint Constraints, validates an element against a constant value
 *
 * @author Martin Helmich <martin@helmich.me>
 */
class Const_Constraint extends Constraint
{
    /**
     * {@inheritdoc}
     */
    public function check(&$element, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        // Only validate const if the attribute exists
        if ($element instanceof Undefined_Constraint && (!isset($schema->required) || !$schema->required)) {
            return;
        }
        $const = $schema->const;
        $type = gettype($element);
        $const_type = gettype($const);
        if ($this->factory->get_config(self::CHECK_MODE_TYPE_CAST) && $type === 'array' && $const_type === 'object') {
            if (Deep_Comparer::is_equal((object) $element, $const)) {
                return;
            }
        }
        if (Deep_Comparer::is_equal($element, $const)) {
            return;
        }
        $this->add_error(Constraint_Error::CONSTANT(), $path, ['const' => $schema->const]);
    }
}