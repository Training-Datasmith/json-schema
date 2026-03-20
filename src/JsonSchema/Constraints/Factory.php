<?php

declare (strict_types=1);
/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Json_Schema\Constraints;

use Json_Schema\Draft_Identifiers;
use Json_Schema\Exception\InvalidArgumentException;
use Json_Schema\Schema_Storage;
use Json_Schema\Schema_Storage_Interface;
use Json_Schema\Uri\Uri_Retriever;
use Json_Schema\Uri_Retriever_Interface;
use Json_Schema\Validator;
/**
 * Factory for centralize constraint initialization.
 */
class Factory
{
    /**
     * @var SchemaStorageInterface
     */
    protected $schema_storage;
    /**
     * @var UriRetriever
     */
    protected $uri_retriever;
    /**
     * @var int
     * @phpstan-var int-mask-of<Constraint::CHECK_MODE_*>
     */
    private $check_mode = Constraint::CHECK_MODE_NORMAL;
    /**
     * @var array<int, TypeCheck\TypeCheckInterface>
     * @phpstan-var array<int-mask-of<Constraint::CHECK_MODE_*>, TypeCheck\TypeCheckInterface>
     */
    private $type_check = [];
    /**
     * @var int-mask-of<Validator::ERROR_*> Validation context
     */
    protected $error_context = Validator::ERROR_DOCUMENT_VALIDATION;
    /**
     * The default dialect used for strict mode (Constraint::CHECK_MODE_STRICT) when the schema is without a schema property
     *
     * @var string
     */
    private $default_dialect = Draft_Identifiers::DRAFT_6;
    /**
     * @var array
     */
    protected $constraint_map = ['array' => \Json_Schema\Constraints\Collection_Constraint::class, 'collection' => \Json_Schema\Constraints\Collection_Constraint::class, 'object' => \Json_Schema\Constraints\Object_Constraint::class, 'type' => \Json_Schema\Constraints\Type_Constraint::class, 'undefined' => \Json_Schema\Constraints\Undefined_Constraint::class, 'string' => \Json_Schema\Constraints\String_Constraint::class, 'number' => \Json_Schema\Constraints\Number_Constraint::class, 'enum' => \Json_Schema\Constraints\Enum_Constraint::class, 'const' => \Json_Schema\Constraints\Const_Constraint::class, 'format' => \Json_Schema\Constraints\Format_Constraint::class, 'schema' => \Json_Schema\Constraints\Schema_Constraint::class, 'validator' => \Json_Schema\Validator::class, 'draft06' => Drafts\Draft06\Draft06Constraint::class, 'draft07' => Drafts\Draft07\Draft07Constraint::class];
    /**
     * @var array<ConstraintInterface>
     */
    private $instance_cache = [];
    /**
     * @phpstan-param int-mask-of<Constraint::CHECK_MODE_*> $checkMode
     */
    public function __construct(?Schema_Storage_Interface $schema_storage = null, ?Uri_Retriever_Interface $uri_retriever = null, int $check_mode = Constraint::CHECK_MODE_NORMAL)
    {
        // set provided config options
        $this->set_config($check_mode);
        $this->uri_retriever = $uri_retriever ?: new Uri_Retriever();
        $this->schema_storage = $schema_storage ?: new Schema_Storage($this->uri_retriever);
    }
    /**
     * Set config values
     *
     * @param int $checkMode Set checkMode options - does not preserve existing flags
     * @phpstan-param int-mask-of<Constraint::CHECK_MODE_*> $checkMode
     */
    public function set_config(int $check_mode = Constraint::CHECK_MODE_NORMAL): void
    {
        $this->check_mode = $check_mode;
    }
    /**
     * Enable checkMode flags
     *
     * @phpstan-param int-mask-of<Constraint::CHECK_MODE_*> $options
     */
    public function add_config(int $options): void
    {
        $this->check_mode |= $options;
    }
    /**
     * Disable checkMode flags
     *
     * @phpstan-param int-mask-of<Constraint::CHECK_MODE_*> $options
     */
    public function remove_config(int $options): void
    {
        $this->check_mode &= ~$options;
    }
    /**
     * Get checkMode option
     *
     * @param int|null $options Options to get, if null then return entire bitmask
     * @phpstan-param int-mask-of<Constraint::CHECK_MODE_*>|null $options Options to get, if null then return entire bitmask
     *
     * @phpstan-return int-mask-of<Constraint::CHECK_MODE_*>
     */
    public function get_config(?int $options = null): int
    {
        if ($options === null) {
            return $this->check_mode;
        }
        return $this->check_mode & $options;
    }
    public function get_uri_retriever(): Uri_Retriever_Interface
    {
        return $this->uri_retriever;
    }
    public function get_schema_storage(): Schema_Storage_Interface
    {
        return $this->schema_storage;
    }
    public function get_type_check(): Type_Check\Type_Check_Interface
    {
        if (!isset($this->type_check[$this->check_mode])) {
            $this->type_check[$this->check_mode] = $this->check_mode & Constraint::CHECK_MODE_TYPE_CAST ? new Type_Check\Loose_Type_Check() : new Type_Check\Strict_Type_Check();
        }
        return $this->type_check[$this->check_mode];
    }
    public function set_constraint_class(string $name, string $class): Factory
    {
        // Ensure class exists
        if (!class_exists($class)) {
            throw new InvalidArgumentException('Unknown constraint ' . $name);
        }
        // Ensure class is appropriate
        if (!in_array(\Json_Schema\Constraints\Constraint_Interface::class, class_implements($class))) {
            throw new InvalidArgumentException('Invalid class ' . $name);
        }
        $this->constraint_map[$name] = $class;
        return $this;
    }
    /**
     * Create a constraint instance for the given constraint name.
     *
     *
     * @throws InvalidArgumentException if is not possible create the constraint instance
     *
     * @return ConstraintInterface&BaseConstraint
     * @phpstan-return ConstraintInterface&BaseConstraint
     */
    public function create_instance_for(string $constraint_name)
    {
        if (!isset($this->constraint_map[$constraint_name])) {
            throw new InvalidArgumentException('Unknown constraint ' . $constraint_name);
        }
        if (!isset($this->instance_cache[$constraint_name])) {
            $this->instance_cache[$constraint_name] = new $this->constraint_map[$constraint_name]($this);
        }
        return clone $this->instance_cache[$constraint_name];
    }
    /**
     * Get the error context
     *
     * @return int-mask-of<Validator::ERROR_*>
     */
    public function get_error_context(): int
    {
        return $this->error_context;
    }
    /**
     * Set the error context
     *
     * @param int-mask-of<Validator::ERROR_*> $errorContext
     */
    public function set_error_context(int $error_context): void
    {
        $this->error_context = $error_context;
    }
    public function get_default_dialect(): string
    {
        return $this->default_dialect;
    }
    public function set_default_dialect(string $default_dialect): void
    {
        $this->default_dialect = $default_dialect;
    }
}