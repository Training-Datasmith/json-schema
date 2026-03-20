<?php

declare (strict_types=1);
namespace Json_Schema\Entity;

use Json_Schema\Constraint_Error;
use Json_Schema\Constraints\Constraint;
use Json_Schema\Constraints\Factory;
use Json_Schema\Exception\Validation_Exception;
use Json_Schema\Validator;
/**
 * @phpstan-type Error array{
 *     "property": string,
 *     "pointer": string,
 *     "message": string,
 *     "constraint": array{"name": string, "params": array<string, mixed>},
 *     "context": int-mask-of<Validator::ERROR_*>
 * }
 * @phpstan-type ErrorList list<Error>
 */
class Error_Bag
{
    /** @var Factory */
    private $factory;
    /** @var ErrorList */
    private $errors = [];
    /**
     * @var int-mask-of<Validator::ERROR_*> All error types that have occurred
     */
    protected $error_mask = Validator::ERROR_NONE;
    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }
    public function reset(): void
    {
        $this->errors = [];
        $this->error_mask = Validator::ERROR_NONE;
    }
    /** @return ErrorList */
    public function get_errors(): array
    {
        return $this->errors;
    }
    /** @param array<string, mixed> $more */
    public function add_error(Constraint_Error $constraint, ?Json_Pointer $path = null, array $more = []): void
    {
        $message = $constraint->get_message();
        $name = $constraint->get_value();
        /** @var Error $error */
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
        /* @see https://github.com/phpstan/phpstan/issues/9384 */
        $this->error_mask |= $error['context'];
        // @phpstan-ignore assign.propertyType
    }
    /** @param ErrorList $errors */
    public function add_errors(array $errors): void
    {
        if (!$errors) {
            return;
        }
        $this->errors = array_merge($this->errors, $errors);
        $error_mask =& $this->error_mask;
        array_walk($errors, static function (array $error) use (&$error_mask): void {
            $error_mask |= $error['context'];
        });
    }
    private function convert_json_pointer_into_property_path(Json_Pointer $pointer): string
    {
        $result = array_map(static function (string $path): string {
            return sprintf(is_numeric($path) ? '[%d]' : '.%s', $path);
        }, $pointer->get_property_paths());
        return trim(implode('', $result), '.');
    }
}