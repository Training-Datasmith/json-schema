<?php

declare (strict_types=1);
/**
 * JsonSchema
 *
 * @filesource
 */
namespace Json_Schema\Uri\Retrievers;

/**
 * AbstractRetriever implements the default shared behavior
 * that all descendant Retrievers should inherit
 *
 * @author Steven Garcia <webwhammy@gmail.com>
 */
abstract class Abstract_Retriever implements Uri_Retriever_Interface
{
    /**
     * Media content type
     *
     * @var string
     */
    protected $content_type;
    /**
     * {@inheritdoc}
     *
     * @see \JsonSchema\Uri\Retrievers\UriRetrieverInterface::getContentType()
     */
    public function get_content_type()
    {
        return $this->content_type;
    }
}