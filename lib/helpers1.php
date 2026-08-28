<?php

function getLinkContent($url, $isXml = false)
{
    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
            CURLOPT_HTTPHEADER => [
                'Accept: ' . ($isXml ? 'application/rss+xml, application/xml, text/xml' : 'text/html')
            ]
        ]);
        $content = curl_exec($curl);
        curl_close($curl);

        if ($content !== false) {
            return $content;
        }
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 60,
            'header' => "User-Agent: Mozilla/5.0\r\nAccept: " . ($isXml ? 'application/rss+xml, application/xml, text/xml' : 'text/html') . "\r\n"
        ]
    ]);

    $content = @file_get_contents($url, false, $context);
    return $content === false ? '' : $content;
}

function cleanString($value)
{
    $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace('/\s+/u', ' ', $value));
}

function cleanStringV2($value)
{
    return cleanString($value);
}

function isCategoryValid_helper($category, $allowed = [])
{
    $category = strtolower((string) $category);
    $blocked = ['video', 'audio', 'radio', 'tv'];

    foreach ($blocked as $term) {
        if (strpos($category, $term) !== false) {
            return false;
        }
    }

    if ($allowed === []) {
        return true;
    }

    foreach ($allowed as $value) {
        if (stripos($category, $value) !== false) {
            return true;
        }
    }

    return false;
}

function isValidMinWordsLimit($content, $minimum)
{
    preg_match_all('/[\p{L}\p{N}]+/u', strip_tags((string) $content), $matches);
    return count($matches[0]) >= $minimum;
}
