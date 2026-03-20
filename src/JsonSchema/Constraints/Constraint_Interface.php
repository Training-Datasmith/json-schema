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
/**
 * The Constraints Interface
 *
 * @author Robert Schönthal <seroscho@googlemail.com>
 */
interface Constraint_Interface
{
    /**
     * returns all collected errors
     */
    public function get_errors(): array;
    /**
     * adds errors to this validator
     */
    public function add_errors(array $errors): void;
    /**
     * adds an error
     *
     * @param ConstraintError $constraint the constraint/rule that is broken, e.g.: ConstraintErrors::LENGTH_MIN()
     * @param array           $more       more array elements to add to the error
     */
    public function add_error(Constraint_Error $constraint, ?Json_Pointer $path = null, array $more = []): void;
    /**
     * checks if the validator has not raised errors
     */
    public function is_valid(): bool;
    /**
     * invokes the validation of an element
     *
     * @abstract
     *
     * @param mixed $value
     * @param mixed $schema
     * @param mixed $i
     *
     * @throws \JsonSchema\Exception\ExceptionInterface
     */
    public function check(&$value, $schema = null, ?Json_Pointer $path = null, $i = null): void;
}