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
use Json_Schema\Rfc3339;
use Json_Schema\Tool\Validator\Relative_Reference_Validator;
use Json_Schema\Tool\Validator\Uri_Validator;
/**
 * Validates against the "format" property
 *
 * @author Justin Rainbow <justin.rainbow@gmail.com>
 *
 * @see   http://tools.ietf.org/html/draft-zyp-json-schema-03#section-5.23
 */
class Format_Constraint extends Constraint
{
    /**
     * {@inheritdoc}
     */
    public function check(&$element, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        if (!isset($schema->format) || $this->factory->get_config(self::CHECK_MODE_DISABLE_FORMAT)) {
            return;
        }
        switch ($schema->format) {
            case 'date':
                if (is_string($element) && !$date = $this->validate_date_time($element, 'Y-m-d')) {
                    $this->add_error(Constraint_Error::FORMAT_DATE(), $path, ['date' => $element, 'format' => $schema->format]);
                }
                break;
            case 'time':
                if (is_string($element) && !$this->validate_date_time($element, 'H:i:s')) {
                    $this->add_error(Constraint_Error::FORMAT_TIME(), $path, ['time' => json_encode($element), 'format' => $schema->format]);
                }
                break;
            case 'date-time':
                if (is_string($element) && null === Rfc3339::create_from_string($element)) {
                    $this->add_error(Constraint_Error::FORMAT_DATE_TIME(), $path, ['dateTime' => json_encode($element), 'format' => $schema->format]);
                }
                break;
            case 'utc-millisec':
                if (!$this->validate_date_time($element, 'U')) {
                    $this->add_error(Constraint_Error::FORMAT_DATE_UTC(), $path, ['value' => $element, 'format' => $schema->format]);
                }
                break;
            case 'regex':
                if (!$this->validate_regex($element)) {
                    $this->add_error(Constraint_Error::FORMAT_REGEX(), $path, ['value' => $element, 'format' => $schema->format]);
                }
                break;
            case 'color':
                if (!$this->validate_color($element)) {
                    $this->add_error(Constraint_Error::FORMAT_COLOR(), $path, ['format' => $schema->format]);
                }
                break;
            case 'style':
                if (!$this->validate_style($element)) {
                    $this->add_error(Constraint_Error::FORMAT_STYLE(), $path, ['format' => $schema->format]);
                }
                break;
            case 'phone':
                if (!$this->validate_phone($element)) {
                    $this->add_error(Constraint_Error::FORMAT_PHONE(), $path, ['format' => $schema->format]);
                }
                break;
            case 'uri':
                if (is_string($element) && !Uri_Validator::is_valid($element)) {
                    $this->add_error(Constraint_Error::FORMAT_URL(), $path, ['format' => $schema->format]);
                }
                break;
            case 'uriref':
            case 'uri-reference':
                if (is_string($element) && !(Uri_Validator::is_valid($element) || Relative_Reference_Validator::is_valid($element))) {
                    $this->add_error(Constraint_Error::FORMAT_URL(), $path, ['format' => $schema->format]);
                }
                break;
            case 'email':
                if (is_string($element) && null === filter_var($element, FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE | FILTER_FLAG_EMAIL_UNICODE)) {
                    $this->add_error(Constraint_Error::FORMAT_EMAIL(), $path, ['format' => $schema->format]);
                }
                break;
            case 'ip-address':
            case 'ipv4':
                if (is_string($element) && null === filter_var($element, FILTER_VALIDATE_IP, FILTER_NULL_ON_FAILURE | FILTER_FLAG_IPV4)) {
                    $this->add_error(Constraint_Error::FORMAT_IP(), $path, ['format' => $schema->format]);
                }
                break;
            case 'ipv6':
                if (is_string($element) && null === filter_var($element, FILTER_VALIDATE_IP, FILTER_NULL_ON_FAILURE | FILTER_FLAG_IPV6)) {
                    $this->add_error(Constraint_Error::FORMAT_IP(), $path, ['format' => $schema->format]);
                }
                break;
            case 'host-name':
            case 'hostname':
                if (!$this->validate_hostname($element)) {
                    $this->add_error(Constraint_Error::FORMAT_HOSTNAME(), $path, ['format' => $schema->format]);
                }
                break;
            default:
                // Empty as it should be:
                // The value of this keyword is called a format attribute. It MUST be a string.
                // A format attribute can generally only validate a given set of instance types.
                // If the type of the instance to validate is not in this set, validation for
                // this format attribute and instance SHOULD succeed.
                // http://json-schema.org/latest/json-schema-validation.html#anchor105
                break;
        }
    }
    protected function validate_date_time($datetime, $format): bool
    {
        $dt = \DateTime::create_from_format($format, (string) $datetime);
        if (!$dt) {
            return false;
        }
        if ($datetime === $dt->format($format)) {
            return true;
        }
        return false;
    }
    protected function validate_regex($regex)
    {
        if (!is_string($regex)) {
            return true;
        }
        return false !== @preg_match(self::json_pattern_to_php_regex($regex), '');
    }
    protected function validate_color($color)
    {
        if (!is_string($color)) {
            return true;
        }
        if (in_array(strtolower($color), ['aqua', 'black', 'blue', 'fuchsia', 'gray', 'green', 'lime', 'maroon', 'navy', 'olive', 'orange', 'purple', 'red', 'silver', 'teal', 'white', 'yellow'])) {
            return true;
        }
        return preg_match('/^#([a-f0-9]{3}|[a-f0-9]{6})$/i', $color);
    }
    protected function validate_style($style): bool
    {
        $properties = explode(';', rtrim($style, ';'));
        $invalid_entries = preg_grep('/^\s*[-a-z]+\s*:\s*.+$/i', $properties, PREG_GREP_INVERT);
        return empty($invalid_entries);
    }
    protected function validate_phone($phone)
    {
        return preg_match('/^\+?(\(\d{3}\)|\d{3}) \d{3} \d{4}$/', $phone);
    }
    protected function validate_hostname($host)
    {
        if (!is_string($host)) {
            return true;
        }
        // RFC 1035: labels are max 63 chars (1 start + 0-61 middle + 1 end)
        $hostname_regex = '/^(?!-)(?!.*?[^A-Za-z0-9\-\.])(?:(?!-)[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?\.)*(?!-)[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?$/';
        return preg_match($hostname_regex, $host);
    }
}