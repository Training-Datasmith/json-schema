<?php

declare (strict_types=1);
namespace Json_Schema\Tool\Validator;

class Uri_Validator
{
    public static function is_valid(string $uri): bool
    {
        // RFC 3986: Hierarchical URIs (http, https, ftp, etc.)
        $hierarchical_pattern = '/^
            ([a-z][a-z0-9+\-.]*):\/\/                # Scheme (http, https, ftp, etc.)
            (?:([^:@\/?#]+)(?::([^@\/?#]*))?@)?      # Optional userinfo (user:pass@)
            ([a-z0-9._~-]+|\[[a-f0-9:.]+\])          # Hostname or IPv6 in brackets
            (?::(\d{1,5}))?                          # Optional port
            (\/[a-zA-Z0-9._~!$&\'()*+,;=:@\/%-]*)*   # Path (valid characters only)
            (\?([^#]*))?                             # Optional query
            (\#(.*))?                                # Optional fragment
        $/ix';
        // RFC 3986: Non-Hierarchical URIs (mailto, data, urn, news)
        $non_hierarchical_pattern = '/^
                (mailto|data|urn|news|tel|file):     # Only allow known non-hierarchical schemes
                (.+)                                 # Must contain at least one character after scheme
        $/ix';
        // Validation for newsgroup name (alphanumeric + dots, no empty segments)
        $news_group_pattern = '/^[a-z0-9]+(\.[a-z0-9]+)*$/i';
        $tel_pattern = '/^\+?[0-9.\-() ]+$/';
        // Allows +, digits, separators
        // RFC 5322-compliant email validation for `mailto:` URIs
        $email_pattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
        // First, check if it's a valid hierarchical URI
        if (preg_match($hierarchical_pattern, $uri, $matches) === 1) {
            // Validate domain name (no double dots like example..com)
            if (!empty($matches[4]) && preg_match('/\.\./', $matches[4])) {
                return false;
            }
            // Validate port (should be between 1 and 65535 if specified)
            if (!empty($matches[5]) && ($matches[5] < 1 || $matches[5] > 65535)) {
                return false;
            }
            // Validate the path (reject illegal characters: < > { } | \ ^ `)
            if (!empty($matches[6]) && preg_match('/[<>{}|\\\\^`]/', $matches[6])) {
                return false;
            }
            return true;
        }
        // If not hierarchical, check non-hierarchical URIs
        if (preg_match($non_hierarchical_pattern, $uri, $matches) === 1) {
            $scheme = strtolower($matches[1]);
            // Extract the scheme
            // Special case: `mailto:` must contain a **valid email address**
            if ($scheme === 'mailto') {
                return preg_match($email_pattern, $matches[2]) === 1;
            }
            if ($scheme === 'news') {
                return preg_match($news_group_pattern, $matches[2]) === 1;
            }
            if ($scheme === 'tel') {
                return preg_match($tel_pattern, $matches[2]) === 1;
            }
            return true;
            // Valid non-hierarchical URI
        }
        return false;
    }
}