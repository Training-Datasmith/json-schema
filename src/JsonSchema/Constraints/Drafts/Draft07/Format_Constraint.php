<?php

declare (strict_types=1);
namespace Json_Schema\Constraints\Drafts\Draft07;

use DateTimeZone;
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
                if (!$this->validate_date_time($value, 'H:i:sp') && !$this->validate_date_time($value, 'H:i:s.up')) {
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
            case 'idn-hostname':
                if (!$this->validate_internationalized_hostname($value)) {
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
        $datetime = strtoupper($datetime);
        // Cleanup for lowercase z
        $is_leap = substr($datetime, 6, 2) === '60';
        $input = $datetime;
        // Correct for leap second
        if ($is_leap) {
            $input = sprintf('%s59%s', substr($datetime, 0, 6), substr($datetime, 8));
        }
        $dt = \DateTimeImmutable::create_from_format($format, $input);
        if (!$dt) {
            return false;
        }
        // Handle invalid timezone offsets
        $timezone_offset = $dt->get_timezone()->get_offset($dt);
        if ($timezone_offset >= 86400 || $timezone_offset <= -86400) {
            return false;
        }
        $expected = $dt->format($format);
        // Correct for trailing zeros on microseconds
        if ($format === 'H:i:s.up') {
            $expected = sprintf('%s%s', rtrim($dt->format('H:i:s.u'), '0'), $dt->format('p'));
        }
        // Correct back for leap seconds
        if ($is_leap) {
            // Only when 23:59:59 in UTC
            $utc_dt = $dt->set_timezone(new DateTimeZone('UTC'));
            if ($utc_dt->format('H:i:s') !== '23:59:59') {
                return false;
            }
            $expected = sprintf('%s60%s', substr($expected, 0, 6), substr($expected, 8));
        }
        return $datetime === $expected;
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
    private function validate_internationalized_hostname(string $host): bool
    {
        if ($host === '') {
            return false;
        }
        $host = rtrim($host, '.');
        $labels = explode('.', $host);
        $ascii_labels = [];
        if ($labels === false) {
            return false;
        }
        foreach ($labels as $label) {
            if ($label === '') {
                return false;
            }
            // CONTEXTJ / CONTEXTO checks
            if (preg_match('/\x{0375}/u', $label) && !preg_match('/\x{0375}[\x{0370}-\x{03FF}]/u', $label)) {
                return false;
            }
            // Hebrew GERESH / GERSHAYIM U+05F3 / U+05F4
            if (preg_match('/[\x{05F3}\x{05F4}]/u', $label) && !preg_match('/[\x{0590}-\x{05FF}][\x{05F3}\x{05F4}]/u', $label)) {
                return false;
            }
            // Katakana middle dot U+30FB
            if (str_contains($label, "・") && !preg_match('/[\x{30A0}-\x{30FF}]/u', $label)) {
                return false;
            }
            // Arabic digit mixing
            $has_arabic_indic = preg_match('/[\x{0660}-\x{0669}]/u', $label);
            $has_ext_arabic_indic = preg_match('/[\x{06F0}-\x{06F9}]/u', $label);
            if ($has_arabic_indic && $has_ext_arabic_indic) {
                return false;
            }
            // Devanagari danda U+0964 / U+0965
            if (preg_match('/[\x{0964}\x{0965}]/u', $label) && !preg_match('/[\x{0900}-\x{097F}]/u', $label)) {
                return false;
            }
            // ZWNJ / ZWJ U+200C / U+200D
            if (preg_match('/[\x{200C}\x{200D}]/u', $label)) {
                return false;
            }
            $ascii = idn_to_ascii($label, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($ascii === false) {
                return false;
            }
            // DNS label length
            if (strlen($ascii) > 63) {
                return false;
            }
            // LDH rule (after IDNA)
            if (!preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/i', $ascii)) {
                return false;
            }
            $ascii_labels[] = $ascii;
        }
        // Total hostname length (ASCII)
        $ascii_host = implode('.', $ascii_labels);
        return strlen($ascii_host) <= 253;
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