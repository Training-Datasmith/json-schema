<?php

declare (strict_types=1);
namespace Json_Schema\Constraints\Drafts\Draft07;

use Json_Schema\Constraint_Error;
use Json_Schema\Constraints\Constraint_Interface;
use Json_Schema\Constraints\Factory;
use Json_Schema\Entity\Error_Bag_Proxy;
use Json_Schema\Entity\Json_Pointer;
class Content_Constraint implements Constraint_Interface
{
    use Error_Bag_Proxy;
    public function __construct(?Factory $factory = null)
    {
        $this->initialise_error_bag($factory ?: new Factory());
    }
    public function check(&$value, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        if (!property_exists($schema, 'contentMediaType') && !property_exists($schema, 'contentEncoding')) {
            return;
        }
        if (!is_string($value)) {
            return;
        }
        $decoded_value = $value;
        if (property_exists($schema, 'contentEncoding')) {
            if ($schema->content_encoding === 'base64') {
                if (!preg_match('/^[A-Za-z0-9+\/=]+$/', $decoded_value)) {
                    $this->add_error(Constraint_Error::CONTENT_ENCODING(), $path, ['contentEncoding' => $schema->content_encoding]);
                    return;
                }
                $decoded_value = base64_decode($decoded_value);
            }
        }
        if (property_exists($schema, 'contentMediaType')) {
            if ($schema->content_media_type === 'application/json') {
                json_decode($decoded_value, false);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return;
                }
            }
            $this->add_error(Constraint_Error::CONTENT_MEDIA_TYPE(), $path, ['contentMediaType' => $schema->content_media_type]);
        }
    }
}