<?php

declare (strict_types=1);
namespace Json_Schema\Constraints\Type_Check;

interface Type_Check_Interface
{
    public static function is_object($value);
    public static function is_array($value);
    public static function property_get($value, $property);
    public static function property_set(&$value, $property, $data);
    public static function property_exists($value, $property);
    public static function property_count($value);
}