<?php

declare (strict_types=1);
namespace Json_Schema;

interface Schema_Storage_Interface
{
    /**
     * Adds schema with given identifier
     *
     * @param object|bool $schema
     */
    public function add_schema(string $id, $schema = null): void;
    /**
     * Returns schema for given identifier, or null if it does not exist
     *
     * @return object|bool
     */
    public function get_schema(string $id);
    /**
     * Returns schema for given reference with all sub-references resolved
     *
     * @return object|bool
     */
    public function resolve_ref(string $ref);
    /**
     * Returns schema referenced by '$ref' property
     *
     * @param mixed $refSchema
     *
     * @return object|bool
     */
    public function resolve_ref_schema($ref_schema);
}