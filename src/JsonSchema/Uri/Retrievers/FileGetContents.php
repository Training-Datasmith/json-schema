<?php

declare (strict_types=1);
/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Json_Schema\Uri\Retrievers;

use Json_Schema\Exception\Resource_Not_Found_Exception;
/**
 * Tries to retrieve JSON schemas from a URI using file_get_contents()
 *
 * @author Sander Coolen <sander@jibber.nl>
 */
class File_Get_Contents extends Abstract_Retriever
{
    protected $message_body;
    /**
     * {@inheritdoc}
     *
     * @see \JsonSchema\Uri\Retrievers\UriRetrieverInterface::retrieve()
     */
    public function retrieve($uri): string
    {
        if (function_exists('http_clear_last_response_headers')) {
            http_clear_last_response_headers();
        }
        $error_message = null;
        set_error_handler(function ($errno, $errstr) use (&$error_message): void {
            $error_message = $errstr;
        });
        $response = file_get_contents($uri);
        restore_error_handler();
        if ($error_message) {
            throw new Resource_Not_Found_Exception($error_message);
        }
        if (false === $response) {
            throw new Resource_Not_Found_Exception('JSON schema not found at ' . $uri);
        }
        if ($response == '' && substr($uri, 0, 7) == 'file://' && substr($uri, -1) == '/') {
            throw new Resource_Not_Found_Exception('JSON schema not found at ' . $uri);
        }
        $this->message_body = $response;
        if (function_exists('http_get_last_response_headers')) {
            // Use http_get_last_response_headers() for compatibility with PHP 8.5+
            // where $http_response_header is deprecated.
            $http_response_headers = http_get_last_response_headers();
        } else {
            /** @phpstan-ignore nullCoalesce.variable ($http_response_header can non-existing when no http request was done) */
            $http_response_headers = $http_response_header ?? [];
        }
        if (!empty($http_response_headers)) {
            $this->fetch_content_type($http_response_headers);
        } else {
            $this->content_type = null;
        }
        return $this->message_body;
    }
    /**
     * @param array $headers HTTP Response Headers
     *
     * @return bool Whether the Content-Type header was found or not
     */
    private function fetch_content_type(array $headers): bool
    {
        foreach (array_reverse($headers) as $header) {
            if ($this->content_type = self::get_content_type_match_in_header($header)) {
                return true;
            }
        }
        return false;
    }
    /**
     * @param string $header
     */
    protected static function get_content_type_match_in_header($header): ?string
    {
        if (0 < preg_match("/Content-Type:(\\V*)/ims", $header, $match)) {
            return trim($match[1]);
        }
        return null;
    }
}