<?php

declare (strict_types=1);
/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Json_Schema\Uri\Retrievers;

use Json_Schema\Exception\RuntimeException;
use Json_Schema\Validator;
/**
 * Tries to retrieve JSON schemas from a URI using cURL library
 *
 * @author Sander Coolen <sander@jibber.nl>
 */
class Curl extends Abstract_Retriever
{
    protected $message_body;
    public function __construct()
    {
        if (!function_exists('curl_init')) {
            // Cannot test this, because curl_init is present on all test platforms plus mock
            throw new RuntimeException('cURL not installed');
            // @codeCoverageIgnore
        }
    }
    /**
     * {@inheritdoc}
     *
     * @see \JsonSchema\Uri\Retrievers\UriRetrieverInterface::retrieve()
     */
    public function retrieve($uri)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: ' . Validator::SCHEMA_MEDIA_TYPE]);
        $response = curl_exec($ch);
        if (false === $response) {
            throw new \Json_Schema\Exception\Resource_Not_Found_Exception('JSON schema not found');
        }
        $this->fetch_message_body($response);
        $this->fetch_content_type($response);
        if (PHP_VERSION_ID < 80000) {
            curl_close($ch);
        }
        return $this->message_body;
    }
    /**
     * @param string $response cURL HTTP response
     */
    private function fetch_message_body($response): void
    {
        preg_match("/(?:\r\n){2}(.*)\$/ms", $response, $match);
        $this->message_body = $match[1];
    }
    /**
     * @param string $response cURL HTTP response
     *
     * @return bool Whether the Content-Type header was found or not
     */
    protected function fetch_content_type($response): bool
    {
        if (0 < preg_match("/Content-Type:(\\V*)/ims", $response, $match)) {
            $this->content_type = trim($match[1]);
            return true;
        }
        return false;
    }
}