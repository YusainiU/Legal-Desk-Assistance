<?php

namespace Simplydigital\LegalDeskLawResources\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

final class LegalHtmlSanitizer
{
    public static function clean(?string $html): string
    {
        static $purifier = null;
        if ($purifier === null) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', 'p,br,strong,b,em,i,u,s,strike,h1,h2,h3,h4,blockquote,ul,ol,li,a[href|title],span[style],div,table,thead,tbody,tr,th,td');
            $config->set('CSS.AllowedProperties', ['color', 'background-color', 'text-align']);
            $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);
            $config->set('URI.DisableResources', true);
            $config->set('URI.DisableExternalResources', true);
            $config->set('Cache.DefinitionImpl', null);
            $purifier = new HTMLPurifier($config);
        }

        return trim($purifier->purify($html ?? ''));
    }

    public static function hasText(string $html): bool
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return preg_match('/[^\s\p{Z}\p{Cf}\p{Cc}]/u', $text) === 1;
    }
}
