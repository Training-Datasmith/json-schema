<?php

declare (strict_types=1);
namespace Json_Schema\Constraints\Type_Check;

class Loose_Type_Check implements Type_Check_Interface
{
    public static function is_object($value): bool
    {
        return is_object($value) || is_array($value) && (count($value) == 0 || self::is_associative_array($value));
    }
    public static function is_array($value): bool
    {
        return is_array($value) && (count($value) == 0 || !self::is_associative_array($value));
    }
    public static function property_get($value, $property)
    {
        if (is_object($value)) {
            return $value->{$property};
        }
        return $value[$property];
    }
    public static function property_set(&$value, $property, $data): void
    {
        if (is_object($value)) {
            $value->{$property} = $data;
        } else {
            $value[$property] = $data;
        }
    }
    public static function property_exists($value, $property): bool
    {
        if (is_object($value)) {
            return property_exists($value, $property);
        }
        return is_array($value) && array_key_exists($property, $value);
    }
    public static function property_count($value): int
    {
        if (is_object($value)) {
            return count(get_object_vars($value));
        }
        return count($value);
    }
    /**
     * Check if the provided array is associative or not
     *
     *
     */
    private static function is_associative_array(array $arr): bool
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}