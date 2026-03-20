<?php

declare (strict_types=1);
/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Json_Schema\Uri\Retrievers;

/**
 * Interface for URI retrievers
 *
 * @author Sander Coolen <sander@jibber.nl>
 */
interface Uri_Retriever_Interface
{
    /**
     * Retrieve a schema from the specified URI
     *
     * @param string $uri URI that resolves to a JSON schema
     *
     * @throws \JsonSchema\Exception\ResourceNotFoundException
     *
     * @return mixed string|null
     */
    public function retrieve($uri);
    /**
     * Get media content type
     *
     * @return string
     */
    public function get_content_type();
}