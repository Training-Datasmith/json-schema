<?php

declare (strict_types=1);
namespace Json_Schema\Entity;

use Json_Schema\Constraint_Error;
use Json_Schema\Constraints\Factory;
/**
 * @phpstan-import-type Error from ErrorBag
 * @phpstan-import-type ErrorList from ErrorBag
 */
trait Error_Bag_Proxy
{
    /** @var ?ErrorBag */
    protected $error_bag;
    /** @return ErrorList */
    public function get_errors(): array
    {
        return $this->error_bag()->get_errors();
    }
    /** @param ErrorList $errors */
    public function add_errors(array $errors): void
    {
        $this->error_bag()->add_errors($errors);
    }
    /**
     * @param array<string, mixed> $more more array elements to add to the error
     */
    public function add_error(Constraint_Error $constraint, ?Json_Pointer $path = null, array $more = []): void
    {
        $this->error_bag()->add_error($constraint, $path, $more);
    }
    public function is_valid(): bool
    {
        return $this->error_bag()->get_errors() === [];
    }
    protected function initialise_error_bag(Factory $factory): Error_Bag
    {
        if (is_null($this->error_bag)) {
            $this->error_bag = new Error_Bag($factory);
        }
        return $this->error_bag;
    }
    protected function error_bag(): Error_Bag
    {
        if (is_null($this->error_bag)) {
            throw new \RuntimeException('ErrorBag not initialized');
        }
        return $this->error_bag;
    }
    public function __clone()
    {
        $this->error_bag()->reset();
    }
}