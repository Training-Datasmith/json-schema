<?php

declare (strict_types=1);
namespace Json_Schema\Constraints\Drafts\Draft06;

use Json_Schema\Constraint_Error;
use Json_Schema\Constraints\Constraint_Interface;
use Json_Schema\Constraints\Factory;
use Json_Schema\Entity\Error_Bag_Proxy;
use Json_Schema\Entity\Json_Pointer;
use Json_Schema\Rfc3339;
use Json_Schema\Tool\Validator\Relative_Reference_Validator;
use Json_Schema\Tool\Validator\Uri_Validator;
class Format_Constraint implements Constraint_Interface
{
    use Error_Bag_Proxy;
    public function __construct(?Factory $factory = null)
    {
        $this->initialise_error_bag($factory ?: new Factory());
    }
    public function check(&$value, $schema = null, ?Json_Pointer $path = null, $i = null): void
    {
        if (!property_exists($schema, 'format')) {
            return;
        }
        if (!is_string($value)) {
            return;
        }
        switch ($schema->format) {
            case 'date':
                if (!$this->validate_date_time($value, 'Y-m-d')) {
                    $this->add_error(Constraint_Error::FORMAT_DATE(), $path, ['date' => $value, 'format' => $schema->format]);
                }
                break;
            case 'time':
                if (!$this->validate_date_time($value, 'H:i:s')) {
                    $this->add_error(Constraint_Error::FORMAT_TIME(), $path, ['time' => $value, 'format' => $schema->format]);
                }
                break;
            case 'date-time':
                if (!$this->validate_rfc3339date_time($value)) {
                    $this->add_error(Constraint_Error::FORMAT_DATE_TIME(), $path, ['dateTime' => $value, 'format' => $schema->format]);
                }
                break;
            case 'utc-millisec':
                if (!$this->validate_date_time($value, 'U')) {
                    $this->add_error(Constraint_Error::FORMAT_DATE_UTC(), $path, ['value' => $value, 'format' => $schema->format]);
                }
                break;
            case 'regex':
                if (!$this->validate_regex($value)) {
                    $this->add_error(Constraint_Error::FORMAT_REGEX(), $path, ['value' => $value, 'format' => $schema->format]);
                }
                break;
            case 'ip-address':
            case 'ipv4':
                if (filter_var($value, FILTER_VALIDATE_IP, FILTER_NULL_ON_FAILURE | FILTER_FLAG_IPV4) === null) {
                    $this->add_error(Constraint_Error::FORMAT_IP(), $path, ['format' => $schema->format]);
                }
                break;
            case 'ipv6':
                if (filter_var($value, FILTER_VALIDATE_IP, FILTER_NULL_ON_FAILURE | FILTER_FLAG_IPV6) === null) {
                    $this->add_error(Constraint_Error::FORMAT_IP(), $path, ['format' => $schema->format]);
                }
                break;
            case 'color':
                if (!$this->validate_color($value)) {
                    $this->add_error(Constraint_Error::FORMAT_COLOR(), $path, ['format' => $schema->format]);
                }
                break;
            case 'style':
                if (!$this->validate_style($value)) {
                    $this->add_error(Constraint_Error::FORMAT_STYLE(), $path, ['format' => $schema->format]);
                }
                break;
            case 'phone':
                if (!$this->validate_phone($value)) {
                    $this->add_error(Constraint_Error::FORMAT_PHONE(), $path, ['format' => $schema->format]);
                }
                break;
            case 'uri':
                if (!Uri_Validator::is_valid($value)) {
                    $this->add_error(Constraint_Error::FORMAT_URL(), $path, ['format' => $schema->format]);
                }
                break;
            case 'uriref':
            case 'uri-reference':
                if (!(Uri_Validator::is_valid($value) || Relative_Reference_Validator::is_valid($value))) {
                    $this->add_error(Constraint_Error::FORMAT_URL(), $path, ['format' => $schema->format]);
                }
                break;
            case 'uri-template':
                if (!$this->validate_uri_template($value)) {
                    $this->add_error(Constraint_Error::FORMAT_URI_TEMPLATE(), $path, ['format' => $schema->format]);
                }
                break;
            case 'email':
                if (filter_var($value, FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE | FILTER_FLAG_EMAIL_UNICODE) === null) {
                    $this->add_error(Constraint_Error::FORMAT_EMAIL(), $path, ['format' => $schema->format]);
                }
                break;
            case 'host-name':
            case 'hostname':
                if (!$this->validate_hostname($value)) {
                    $this->add_error(Constraint_Error::FORMAT_HOSTNAME(), $path, ['format' => $schema->format]);
                }
                break;
            case 'json-pointer':
                if (!$this->validate_json_pointer($value)) {
                    $this->add_error(Constraint_Error::FORMAT_JSON_POINTER(), $path, ['format' => $schema->format]);
                }
                break;
            default:
                break;
        }
    }
    private function validate_date_time(string $datetime, string $format): bool
    {
        $dt = \DateTime::create_from_format($format, $datetime);
        if (!$dt) {
            return false;
        }
        return $datetime === $dt->format($format);
    }
    private function validate_regex(string $regex): bool
    {
        return preg_match(self::json_pattern_to_php_regex($regex), '') !== false;
    }
    /**
     * Transform a JSON pattern into a PCRE regex
     */
    private static function json_pattern_to_php_regex(string $pattern): string
    {
        return '~' . str_replace('~', '\~', $pattern) . '~u';
    }
    private function validate_color(string $color): bool
    {
        if (in_array(strtolower($color), ['aqua', 'black', 'blue', 'fuchsia', 'gray', 'green', 'lime', 'maroon', 'navy', 'olive', 'orange', 'purple', 'red', 'silver', 'teal', 'white', 'yellow'])) {
            return true;
        }
        return preg_match('/^#([a-f0-9]{3}|[a-f0-9]{6})$/i', $color) !== false;
    }
    private function validate_style(string $style): bool
    {
        $properties = explode(';', rtrim($style, ';'));
        $invalid_entries = preg_grep('/^\s*[-a-z]+\s*:\s*.+$/i', $properties, PREG_GREP_INVERT);
        return empty($invalid_entries);
    }
    private function validate_phone(string $phone): bool
    {
        return preg_match('/^\+?(\(\d{3}\)|\d{3}) \d{3} \d{4}$/', $phone) !== false;
    }
    private function validate_hostname(string $host): bool
    {
        $hostname_regex = '/^(?!-)(?!.*?[^A-Za-z0-9\-\.])(?:(?!-)[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?\.)*(?!-)[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?$/';
        return preg_match($hostname_regex, $host) === 1;
    }
    private function validate_json_pointer(string $value): bool
    {
        // Must be empty or start with a forward slash
        if ($value !== '' && $value[0] !== '/') {
            return false;
        }
        // Split into reference tokens and check for invalid escape sequences
        $tokens = explode('/', $value);
        array_shift($tokens);
        // remove leading empty part due to leading slash
        foreach ($tokens as $token) {
            // "~" must only be followed by "0" or "1"
            if (preg_match('/~(?![01])/', $token)) {
                return false;
            }
        }
        return true;
    }
    private function validate_rfc3339date_time(string $value): bool
    {
        $date_time = Rfc3339::create_from_string($value);
        if (is_null($date_time)) {
            return false;
        }
        // Compare value and date result to be equal
        return true;
    }
    private function validate_uri_template(string $value): bool
    {
        return preg_match('/^(?:[^\{\}]*|\{[a-zA-Z0-9_:%\/\.~\-\+\*]+\})*$/', $value) === 1;
    }
}