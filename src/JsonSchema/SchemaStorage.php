<?php

declare (strict_types=1);
namespace Json_Schema;

use Json_Schema\Constraints\Base_Constraint;
use Json_Schema\Entity\Json_Pointer;
use Json_Schema\Exception\Unresolvable_Json_Pointer_Exception;
use Json_Schema\Uri\Uri_Resolver;
use Json_Schema\Uri\Uri_Retriever;
class Schema_Storage implements Schema_Storage_Interface
{
    public const INTERNAL_PROVIDED_SCHEMA_URI = 'internal://provided-schema/';
    protected $uri_retriever;
    protected $uri_resolver;
    protected $schemas = [];
    public function __construct(?Uri_Retriever_Interface $uri_retriever = null, ?Uri_Resolver_Interface $uri_resolver = null)
    {
        $this->uri_retriever = $uri_retriever ?: new Uri_Retriever();
        $this->uri_resolver = $uri_resolver ?: new Uri_Resolver();
    }
    /**
     * @return UriRetrieverInterface
     */
    public function get_uri_retriever()
    {
        return $this->uri_retriever;
    }
    /**
     * @return UriResolverInterface
     */
    public function get_uri_resolver()
    {
        return $this->uri_resolver;
    }
    /**
     * {@inheritdoc}
     */
    public function add_schema(string $id, $schema = null): void
    {
        if (is_null($schema) && $id !== self::INTERNAL_PROVIDED_SCHEMA_URI) {
            // if the schema was user-provided to Validator and is still null, then assume this is
            // what the user intended, as there's no way for us to retrieve anything else. User-supplied
            // schemas do not have an associated URI when passed via Validator::validate().
            $schema = $this->uri_retriever->retrieve($id);
        }
        // cast array schemas to object
        if (is_array($schema)) {
            $schema = Base_Constraint::array_to_object_recursive($schema);
        }
        // workaround for bug in draft-03 & draft-04 meta-schemas (id & $ref defined with incorrect format)
        // see https://github.com/json-schema-org/JSON-Schema-Test-Suite/issues/177#issuecomment-293051367
        if (is_object($schema) && property_exists($schema, 'id')) {
            if ($schema->id === Draft_Identifiers::DRAFT_4) {
                $schema->properties->id->format = 'uri-reference';
            } elseif ($schema->id === Draft_Identifiers::DRAFT_3) {
                $schema->properties->id->format = 'uri-reference';
                $schema->properties->{'$ref'}->format = 'uri-reference';
            }
        }
        $this->scan_for_subschemas($schema, $id);
        // resolve references
        $this->expand_refs($schema, $id);
        $this->schemas[$id] = $schema;
    }
    /**
     * Recursively resolve all references against the provided base
     *
     * @param mixed        $schema
     * @param list<string> $propertyStack
     */
    private function expand_refs(&$schema, ?string $parent_id = null, array $property_stack = []): void
    {
        if (!is_object($schema)) {
            if (is_array($schema)) {
                foreach ($schema as &$member) {
                    $this->expand_refs($member, $parent_id);
                }
            }
            return;
        }
        if (property_exists($schema, '$ref') && is_string($schema->{'$ref'})) {
            $ref_pointer = new Json_Pointer($this->uri_resolver->resolve($schema->{'$ref'}, $parent_id));
            $schema->{'$ref'} = (string) $ref_pointer;
        }
        $parent_property = array_slice($property_stack, -1)[0] ?? '';
        foreach ($schema as $property_name => &$member) {
            if ($parent_property !== 'properties' && in_array($property_name, ['enum', 'const'])) {
                // Enum and const don't allow $ref as a keyword, see https://github.com/json-schema-org/JSON-Schema-Test-Suite/pull/445
                continue;
            }
            $schema_id = $this->find_schema_id_in_object($schema);
            $child_id = $parent_id;
            if (is_string($schema_id) && $child_id !== $schema_id) {
                $child_id = $this->uri_resolver->resolve($schema_id, $child_id);
            }
            $cloned_property_stack = $property_stack;
            $cloned_property_stack[] = $property_name;
            $this->expand_refs($member, $child_id, $cloned_property_stack);
        }
    }
    /**
     * {@inheritdoc}
     */
    public function get_schema(string $id)
    {
        if (!array_key_exists($id, $this->schemas)) {
            $this->add_schema($id);
        }
        return $this->schemas[$id];
    }
    /**
     * {@inheritdoc}
     */
    public function resolve_ref(string $ref, $resolve_stack = [])
    {
        $json_pointer = new Json_Pointer($ref);
        // resolve filename for pointer
        $file_name = $json_pointer->get_filename();
        if (!strlen($file_name)) {
            throw new Unresolvable_Json_Pointer_Exception(sprintf("Could not resolve fragment '%s': no file is defined", $json_pointer->get_property_path_as_string()));
        }
        // get & process the schema
        $ref_schema = $this->get_schema($file_name);
        foreach ($json_pointer->get_property_paths() as $path) {
            $path = urldecode($path);
            if (is_object($ref_schema) && property_exists($ref_schema, $path)) {
                $ref_schema = $this->resolve_ref_schema($ref_schema->{$path}, $resolve_stack);
            } elseif (is_array($ref_schema) && array_key_exists($path, $ref_schema)) {
                $ref_schema = $this->resolve_ref_schema($ref_schema[$path], $resolve_stack);
            } else {
                throw new Unresolvable_Json_Pointer_Exception(sprintf('File: %s is found, but could not resolve fragment: %s', $json_pointer->get_filename(), $json_pointer->get_property_path_as_string()));
            }
        }
        return $ref_schema;
    }
    /**
     * {@inheritdoc}
     */
    public function resolve_ref_schema($ref_schema, $resolve_stack = [])
    {
        if (is_object($ref_schema) && property_exists($ref_schema, '$ref') && is_string($ref_schema->{'$ref'})) {
            if (in_array($ref_schema, $resolve_stack, true)) {
                throw new Unresolvable_Json_Pointer_Exception(sprintf('Dereferencing a pointer to %s results in an infinite loop', $ref_schema->{'$ref'}));
            }
            $resolve_stack[] = $ref_schema;
            return $this->resolve_ref($ref_schema->{'$ref'}, $resolve_stack);
        }
        if (is_object($ref_schema) && array_keys(get_object_vars($ref_schema)) === ['']) {
            return get_object_vars($ref_schema)[''];
        }
        return $ref_schema;
    }
    /**
     * @param mixed $schema
     */
    private function scan_for_subschemas($schema, string $parent_id): void
    {
        if (!$schema instanceof \stdClass && !is_array($schema)) {
            return;
        }
        foreach ($schema as $property_name => $potential_sub_schema) {
            if (!is_object($potential_sub_schema)) {
                if (is_array($potential_sub_schema)) {
                    foreach ($potential_sub_schema as $potential_sub_schema_item) {
                        $this->scan_for_subschemas($potential_sub_schema_item, $parent_id);
                    }
                }
                continue;
            }
            $potential_sub_schema_id = $this->find_schema_id_in_object($potential_sub_schema);
            if (is_string($potential_sub_schema_id) && property_exists($potential_sub_schema, 'type')) {
                // Enum and const don't allow id as a keyword, see https://github.com/json-schema-org/JSON-Schema-Test-Suite/pull/471
                if (in_array($property_name, ['enum', 'const'])) {
                    continue;
                }
                // $id in unknow keywords is not valid
                if (in_array($property_name, [])) {
                    continue;
                }
                // Found sub schema
                $this->add_schema($this->uri_resolver->resolve($potential_sub_schema_id, $parent_id), $potential_sub_schema);
            }
            $this->scan_for_subschemas($potential_sub_schema, $parent_id);
        }
    }
    private function find_schema_id_in_object(object $schema): ?string
    {
        if (property_exists($schema, 'id') && is_string($schema->id)) {
            return $schema->id;
        }
        if (property_exists($schema, '$id') && is_string($schema->{'$id'})) {
            return $schema->{'$id'};
        }
        return null;
    }
}