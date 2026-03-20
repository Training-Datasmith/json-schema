<?php

declare (strict_types=1);
namespace Json_Schema\Tool;

use Json_Schema\Exception\Json_Decoding_Exception;
use Json_Schema\Exception\RuntimeException;
class Deep_Copy
{
    /**
     * @param mixed $input
     *
     * @return mixed
     */
    public static function copy_of($input)
    {
        $json = json_encode($input);
        if (JSON_ERROR_NONE < $error = json_last_error()) {
            throw new Json_Decoding_Exception($error);
        }
        if ($json === false) {
            throw new RuntimeException('Failed to encode input to JSON: ' . json_last_error_msg());
        }
        return json_decode($json, self::is_associative_array($input));
    }
    /**
     * @param mixed $input
     */
    private static function is_associative_array($input): bool
    {
        return is_array($input) && array_keys($input) !== range(0, count($input) - 1);
    }
}