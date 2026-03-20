<?php

declare (strict_types=1);
namespace Json_Schema\Uri\Retrievers;

use Json_Schema\Validator;
/**
 * URI retrieved based on a predefined array of schemas
 *
 * @example
 *
 *      $retriever = new PredefinedArray(array(
 *          'http://acme.com/schemas/person#'  => '{ ... }',
 *          'http://acme.com/schemas/address#' => '{ ... }',
 *      ))
 *
 *      $schema = $retriever->retrieve('http://acme.com/schemas/person#');
 */
class Predefined_Array extends Abstract_Retriever
{
    /**
     * Contains schemas as URI => JSON
     *
     * @var array
     */
    private $schemas;
    /**
     * Constructor
     *
     * @param string $contentType
     */
    public function __construct(array $schemas, $content_type = Validator::SCHEMA_MEDIA_TYPE)
    {
        $this->schemas = $schemas;
        $this->content_type = $content_type;
    }
    /**
     * {@inheritdoc}
     *
     * @see \JsonSchema\Uri\Retrievers\UriRetrieverInterface::retrieve()
     */
    public function retrieve($uri)
    {
        if (!array_key_exists($uri, $this->schemas)) {
            throw new \Json_Schema\Exception\Resource_Not_Found_Exception(sprintf('The JSON schema "%s" was not found.', $uri));
        }
        return $this->schemas[$uri];
    }
}