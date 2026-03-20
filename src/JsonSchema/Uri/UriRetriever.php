<?php

declare (strict_types=1);
/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Json_Schema\Uri;

use Json_Schema\Exception\Invalid_Schema_Media_Type_Exception;
use Json_Schema\Exception\Json_Decoding_Exception;
use Json_Schema\Exception\Resource_Not_Found_Exception;
use Json_Schema\Uri\Retrievers\File_Get_Contents;
use Json_Schema\Uri\Retrievers\Uri_Retriever_Interface;
use Json_Schema\Uri_Retriever_Interface as BaseUriRetrieverInterface;
use Json_Schema\Validator;
/**
 * Retrieves JSON Schema URIs
 *
 * @author Tyler Akins <fidian@rumkin.com>
 */
class Uri_Retriever implements Base_Uri_Retriever_Interface
{
    /**
     * @var array Map of URL translations
     */
    protected $translation_map = [
        // use local copies of the spec schemas
        '|^https?://json-schema.org/draft-(0[3467])/schema#?|' => 'package://dist/schema/json-schema-draft-$1.json',
    ];
    /**
     * @var array A list of endpoints for media type check exclusion
     */
    protected $allowed_invalid_content_type_endpoints = ['http://json-schema.org/', 'https://json-schema.org/'];
    /**
     * @var null|UriRetrieverInterface
     */
    protected $uri_retriever;
    /**
     * @var array|object[]
     *
     * @see loadSchema
     */
    private $schema_cache = [];
    /**
     * Adds an endpoint to the media type validation exclusion list
     *
     * @param string $endpoint
     */
    public function add_invalid_content_type_endpoint($endpoint): void
    {
        $this->allowed_invalid_content_type_endpoints[] = $endpoint;
    }
    /**
     * Guarantee the correct media type was encountered
     *
     * @param UriRetrieverInterface $uriRetriever
     * @param string                $uri
     *
     * @return bool|void
     */
    public function confirm_media_type($uri_retriever, $uri)
    {
        $content_type = $uri_retriever->get_content_type();
        if (is_null($content_type)) {
            // Well, we didn't get an invalid one
            return;
        }
        if (in_array($content_type, [Validator::SCHEMA_MEDIA_TYPE, 'application/json'])) {
            return;
        }
        foreach ($this->allowed_invalid_content_type_endpoints as $endpoint) {
            if (!\is_null($uri) && strpos($uri, $endpoint) === 0) {
                return true;
            }
        }
        throw new Invalid_Schema_Media_Type_Exception(sprintf('Media type %s expected, but %s given', Validator::SCHEMA_MEDIA_TYPE, $content_type));
    }
    /**
     * Get a URI Retriever
     *
     * If none is specified, sets a default FileGetContents retriever and
     * returns that object.
     *
     * @return UriRetrieverInterface
     */
    public function get_uri_retriever()
    {
        if (is_null($this->uri_retriever)) {
            $this->set_uri_retriever(new File_Get_Contents());
        }
        return $this->uri_retriever;
    }
    /**
     * Resolve a schema based on pointer
     *
     * URIs can have a fragment at the end in the format of
     * #/path/to/object and we are to look up the 'path' property of
     * the first object then the 'to' and 'object' properties.
     *
     * @param object $jsonSchema JSON Schema contents
     * @param string $uri        JSON Schema URI
     *
     * @throws ResourceNotFoundException
     *
     * @return object JSON Schema after walking down the fragment pieces
     */
    public function resolve_pointer($json_schema, string $uri)
    {
        $resolver = new Uri_Resolver();
        $parsed = $resolver->parse($uri);
        if (empty($parsed['fragment'])) {
            return $json_schema;
        }
        $path = explode('/', $parsed['fragment']);
        while ($path) {
            $path_element = array_shift($path);
            if (!empty($path_element)) {
                $path_element = str_replace('~1', '/', $path_element);
                $path_element = str_replace('~0', '~', $path_element);
                if (!empty($json_schema->{$path_element})) {
                    $json_schema = $json_schema->{$path_element};
                } else {
                    throw new Resource_Not_Found_Exception('Fragment "' . $parsed['fragment'] . '" not found' . ' in ' . $uri);
                }
                if (!is_object($json_schema)) {
                    throw new Resource_Not_Found_Exception('Fragment part "' . $path_element . '" is no object ' . ' in ' . $uri);
                }
            }
        }
        return $json_schema;
    }
    /**
     * {@inheritdoc}
     */
    public function retrieve($uri, $base_uri = null, $translate = true)
    {
        $resolver = new Uri_Resolver();
        $resolved_uri = $fetch_uri = $resolver->resolve($uri, $base_uri);
        //fetch URL without #fragment
        $ar_parts = $resolver->parse($resolved_uri);
        if (isset($ar_parts['fragment'])) {
            unset($ar_parts['fragment']);
            $fetch_uri = $resolver->generate($ar_parts);
        }
        // apply URI translations
        if ($translate) {
            $fetch_uri = $this->translate($fetch_uri);
        }
        $json_schema = $this->load_schema($fetch_uri);
        // Use the JSON pointer if specified
        $json_schema = $this->resolve_pointer($json_schema, $resolved_uri);
        if ($json_schema instanceof \stdClass) {
            $json_schema->id = $resolved_uri;
        }
        return $json_schema;
    }
    /**
     * Fetch a schema from the given URI, json-decode it and return it.
     * Caches schema objects.
     *
     * @param string $fetchUri Absolute URI
     *
     * @return object JSON schema object
     */
    protected function load_schema($fetch_uri)
    {
        if (isset($this->schema_cache[$fetch_uri])) {
            return $this->schema_cache[$fetch_uri];
        }
        $uri_retriever = $this->get_uri_retriever();
        $contents = $this->uri_retriever->retrieve($fetch_uri);
        $this->confirm_media_type($uri_retriever, $fetch_uri);
        $json_schema = json_decode($contents);
        if (JSON_ERROR_NONE < $error = json_last_error()) {
            throw new Json_Decoding_Exception($error);
        }
        $this->schema_cache[$fetch_uri] = $json_schema;
        return $json_schema;
    }
    /**
     * Set the URI Retriever
     *
     *
     * @return $this for chaining
     */
    public function set_uri_retriever(Uri_Retriever_Interface $uri_retriever): self
    {
        $this->uri_retriever = $uri_retriever;
        return $this;
    }
    /**
     * Parses a URI into five main components
     *
     * @param string $uri
     */
    public function parse($uri): array
    {
        preg_match('|^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?|', $uri, $match);
        $components = [];
        if (5 < count($match)) {
            $components = ['scheme' => $match[2], 'authority' => $match[4], 'path' => $match[5]];
        }
        if (7 < count($match)) {
            $components['query'] = $match[7];
        }
        if (9 < count($match)) {
            $components['fragment'] = $match[9];
        }
        return $components;
    }
    /**
     * Builds a URI based on n array with the main components
     *
     *
     */
    public function generate(array $components): string
    {
        $uri = $components['scheme'] . '://' . $components['authority'] . $components['path'];
        if (array_key_exists('query', $components)) {
            $uri .= $components['query'];
        }
        if (array_key_exists('fragment', $components)) {
            $uri .= $components['fragment'];
        }
        return $uri;
    }
    /**
     * Resolves a URI
     *
     * @param string $uri     Absolute or relative
     * @param string $baseUri Optional base URI
     *
     * @return string
     */
    public function resolve($uri, $base_uri = null)
    {
        $components = $this->parse($uri);
        $path = $components['path'];
        if (array_key_exists('scheme', $components) && 'http' === $components['scheme']) {
            return $uri;
        }
        $base_components = $this->parse($base_uri);
        $base_path = $base_components['path'];
        $base_components['path'] = Uri_Resolver::combine_relative_path_with_base_path($path, $base_path);
        return $this->generate($base_components);
    }
    /**
     * @param string $uri
     */
    public function is_valid($uri): bool
    {
        $components = $this->parse($uri);
        return !empty($components);
    }
    /**
     * Set a URL translation rule
     */
    public function set_translation($from, $to): void
    {
        $this->translation_map[$from] = $to;
    }
    /**
     * Apply URI translation rules
     */
    public function translate($uri)
    {
        foreach ($this->translation_map as $from => $to) {
            $uri = preg_replace($from, $to, $uri);
        }
        // translate references to local files within the json-schema package
        $uri = preg_replace('|^package://|', sprintf('file://%s/', realpath(__DIR__ . '/../../..')), $uri);
        return $uri;
    }
}