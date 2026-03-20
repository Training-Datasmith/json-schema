<?php

declare (strict_types=1);
namespace Json_Schema\Constraints;

use const JSON_ERROR_NONE;
use Json_Schema\Constraint_Error;
use Json_Schema\Entity\Json_Pointer;
use Json_Schema\Exception\InvalidArgumentException;
use Json_Schema\Exception\Validation_Exception;
use Json_Schema\Validator;
/**
 * A more basic constraint definition - used for the public
 * interface to avoid exposing library internals.
 */
class Base_Constraint
{
    /**
     * @var array Errors
     */
    protected $errors = [];
    /**
     * @var int All error types which have occurred
     * @phpstan-var int-mask-of<Validator::ERROR_*>
     */
    protected $error_mask = Validator::ERROR_NONE;
    /**
     * @var Factory
     */
    protected $factory;
    public function __construct(?Factory $factory = null)
    {
        $this->factory = $factory ?: new Factory();
    }
    public function add_error(Constraint_Error $constraint, ?Json_Pointer $path = null, array $more = []): void
    {
        $message = $constraint->get_message();
        $name = $constraint->get_value();
        $error = ['property' => $this->convert_json_pointer_into_property_path($path ?: new Json_Pointer('')), 'pointer' => ltrim((string) ($path ?: new Json_Pointer('')), '#'), 'message' => ucfirst(vsprintf($message, array_map(static function ($val) {
            if (is_scalar($val)) {
                return is_bool($val) ? var_export($val, true) : $val;
            }
            return json_encode($val);
        }, array_values($more)))), 'constraint' => ['name' => $name, 'params' => $more], 'context' => $this->factory->get_error_context()];
        if ($this->factory->get_config(Constraint::CHECK_MODE_EXCEPTIONS)) {
            throw new Validation_Exception(sprintf('Error validating %s: %s', $error['pointer'], $error['message']));
        }
        $this->errors[] = $error;
        $this->error_mask |= $error['context'];
    }
    public function add_errors(array $errors): void
    {
        if ($errors) {
            $this->errors = array_merge($this->errors, $errors);
            $error_mask =& $this->error_mask;
            array_walk($errors, static function (array $error) use (&$error_mask): void {
                if (isset($error['context'])) {
                    $error_mask |= $error['context'];
                }
            });
        }
    }
    /**
     * @phpstan-param int-mask-of<Validator::ERROR_*> $errorContext
     */
    public function get_errors(int $error_context = Validator::ERROR_ALL): array
    {
        if ($error_context === Validator::ERROR_ALL) {
            return $this->errors;
        }
        return array_filter($this->errors, static function (array $error) use ($error_context): bool {
            return (bool) ($error_context & $error['context']);
        });
    }
    /**
     * @phpstan-param int-mask-of<Validator::ERROR_*> $errorContext
     */
    public function num_errors(int $error_context = Validator::ERROR_ALL): int
    {
        if ($error_context === Validator::ERROR_ALL) {
            return count($this->errors);
        }
        return count($this->get_errors($error_context));
    }
    public function is_valid(): bool
    {
        return !$this->get_errors();
    }
    /**
     * Clears any reported errors. Should be used between
     * multiple validation checks.
     */
    public function reset(): void
    {
        $this->errors = [];
        $this->error_mask = Validator::ERROR_NONE;
    }
    /**
     * Get the error mask
     *
     * @phpstan-return int-mask-of<Validator::ERROR_*>
     */
    public function get_error_mask(): int
    {
        return $this->error_mask;
    }
    /**
     * Recursively cast an associative array to an object
     */
    public static function array_to_object_recursive(array $array): object
    {
        $json = json_encode($array);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $message = 'Unable to encode schema array as JSON';
            if (function_exists('json_last_error_msg')) {
                $message .= ': ' . json_last_error_msg();
            }
            throw new InvalidArgumentException($message);
        }
        return (object) json_decode($json, false);
    }
    /**
     * Transform a JSON pattern into a PCRE regex
     */
    public static function json_pattern_to_php_regex(string $pattern): string
    {
        return '~' . str_replace('~', '\~', $pattern) . '~u';
    }
    protected function convert_json_pointer_into_property_path(Json_Pointer $pointer): string
    {
        $result = array_map(static function (string $path): string {
            return sprintf(is_numeric($path) ? '[%d]' : '.%s', $path);
        }, $pointer->get_property_paths());
        return trim(implode('', $result), '.');
    }
}