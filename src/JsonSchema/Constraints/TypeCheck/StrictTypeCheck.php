<?php

declare (strict_types=1);
namespace Json_Schema\Constraints\Type_Check;

class Strict_Type_Check implements Type_Check_Interface
{
    public static function is_object($value): bool
    {
        return is_object($value);
    }
    public static function is_array($value): bool
    {
        return is_array($value);
    }
    public static function property_get($value, $property)
    {
        return $value->{$property};
    }
    public static function property_set(&$value, $property, $data): void
    {
        $value->{$property} = $data;
    }
    public static function property_exists($value, $property): bool
    {
        return property_exists($value, $property);
    }
    public static function property_count($value): int
    {
        if (!is_object($value)) {
            return 0;
        }
        return count(get_object_vars($value));
    }
}