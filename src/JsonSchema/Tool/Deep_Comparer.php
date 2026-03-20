<?php

declare (strict_types=1);
namespace Json_Schema\Tool;

class Deep_Comparer
{
    /**
     * @param mixed $left
     * @param mixed $right
     */
    public static function is_equal($left, $right): bool
    {
        if ($left === null && $right === null) {
            return true;
        }
        $is_left_scalar = is_scalar($left);
        $is_left_number = is_int($left) || is_float($left);
        $is_right_scalar = is_scalar($right);
        $is_right_number = is_int($right) || is_float($right);
        if ($is_left_scalar && $is_right_scalar) {
            /*
             * In Json-Schema mathematically equal numbers are compared equal
             */
            if ($is_left_number && $is_right_number && (float) $left === (float) $right) {
                return true;
            }
            return $left === $right;
        }
        if ($is_left_scalar !== $is_right_scalar) {
            return false;
        }
        if (is_array($left) && is_array($right)) {
            return self::is_array_equal($left, $right);
        }
        if ($left instanceof \stdClass && $right instanceof \stdClass) {
            return self::is_array_equal((array) $left, (array) $right);
        }
        return false;
    }
    /**
     * @param array<string|int, mixed> $left
     * @param array<string|int, mixed> $right
     */
    private static function is_array_equal(array $left, array $right): bool
    {
        if (count($left) !== count($right)) {
            return false;
        }
        foreach ($left as $key => $value) {
            if (!array_key_exists($key, $right)) {
                return false;
            }
            if (!self::is_equal($value, $right[$key])) {
                return false;
            }
        }
        return true;
    }
}