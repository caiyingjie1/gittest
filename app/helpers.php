<?php

use Symfony\Component\HttpFoundation\Response;

if (!function_exists('geohash2number')) {
    if (function_exists('gmp_strval')) {
        function geohash2number($geohash)
        {
            $hex = "0x" . bin2hex(substr($geohash, 0, 8));
            return gmp_strval(gmp_sub($hex, gmp_pow(2, 63)));
        }
    } else {
        function geohash2number($geohash)
        {
            $hex = bin2hex(substr($geohash, 0, 8));
            return bcsub(bchex2dec($hex), bcpow(2, 63));
        }
    }
}

if (!function_exists('bchex2dec')) {
    function bchex2dec($hex)
    {
        if (strlen($hex) == 1) {
            return hexdec($hex);
        } else {
            $remain = substr($hex, 0, -1);
            $last = substr($hex, -1);
            return bcadd(bcmul(16, bchex2dec($remain)), hexdec($last));
        }
    }
}

if (!function_exists('status_code_2_name')) {
    function status_code_2_name($code)
    {
        static $statusNames = array(
            100 => "HTTP_CONTINUE",
            101 => "HTTP_SWITCHING_PROTOCOLS",
            102 => "HTTP_PROCESSING",            // RFC2518
            200 => "HTTP_OK",
            201 => "HTTP_CREATED",
            202 => "HTTP_ACCEPTED",
            203 => "HTTP_NON_AUTHORITATIVE_INFORMATION",
            204 => "HTTP_NO_CONTENT",
            205 => "HTTP_RESET_CONTENT",
            206 => "HTTP_PARTIAL_CONTENT",
            207 => "HTTP_MULTI_STATUS",          // RFC4918
            208 => "HTTP_ALREADY_REPORTED",      // RFC5842
            226 => "HTTP_IM_USED",               // RFC3229
            300 => "HTTP_MULTIPLE_CHOICES",
            301 => "HTTP_MOVED_PERMANENTLY",
            302 => "HTTP_FOUND",
            303 => "HTTP_SEE_OTHER",
            304 => "HTTP_NOT_MODIFIED",
            305 => "HTTP_USE_PROXY",
            306 => "HTTP_RESERVED",
            307 => "HTTP_TEMPORARY_REDIRECT",
            308 => "HTTP_PERMANENTLY_REDIRECT",  // RFC7238
            400 => "HTTP_BAD_REQUEST",
            401 => "HTTP_UNAUTHORIZED",
            402 => "HTTP_PAYMENT_REQUIRED",
            403 => "HTTP_FORBIDDEN",
            404 => "HTTP_NOT_FOUND",
            405 => "HTTP_METHOD_NOT_ALLOWED",
            406 => "HTTP_NOT_ACCEPTABLE",
            407 => "HTTP_PROXY_AUTHENTICATION_REQUIRED",
            408 => "HTTP_REQUEST_TIMEOUT",
            409 => "HTTP_CONFLICT",
            410 => "HTTP_GONE",
            411 => "HTTP_LENGTH_REQUIRED",
            412 => "HTTP_PRECONDITION_FAILED",
            413 => "HTTP_REQUEST_ENTITY_TOO_LARGE",
            414 => "HTTP_REQUEST_URI_TOO_LONG",
            415 => "HTTP_UNSUPPORTED_MEDIA_TYPE",
            416 => "HTTP_REQUESTED_RANGE_NOT_SATISFIABLE",
            417 => "HTTP_EXPECTATION_FAILED",
            418 => "HTTP_I_AM_A_TEAPOT",                                               // RFC2324
            422 => "HTTP_UNPROCESSABLE_ENTITY",                                        // RFC4918
            423 => "HTTP_LOCKED",                                                      // RFC4918
            424 => "HTTP_FAILED_DEPENDENCY",                                           // RFC4918
            425 => "HTTP_RESERVED_FOR_WEBDAV_ADVANCED_COLLECTIONS_EXPIRED_PROPOSAL",   // RFC2817
            426 => "HTTP_UPGRADE_REQUIRED",                                            // RFC2817
            428 => "HTTP_PRECONDITION_REQUIRED",                                       // RFC6585
            429 => "HTTP_TOO_MANY_REQUESTS",                                           // RFC6585
            431 => "HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE",                             // RFC6585
            500 => "HTTP_INTERNAL_SERVER_ERROR",
            501 => "HTTP_NOT_IMPLEMENTED",
            502 => "HTTP_BAD_GATEWAY",
            503 => "HTTP_SERVICE_UNAVAILABLE",
            504 => "HTTP_GATEWAY_TIMEOUT",
            505 => "HTTP_VERSION_NOT_SUPPORTED",
            506 => "HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL",                        // RFC2295
            507 => "HTTP_INSUFFICIENT_STORAGE",                                        // RFC4918
            508 => "HTTP_LOOP_DETECTED",                                               // RFC5842
            510 => "HTTP_NOT_EXTENDED",                                                // RFC2774
            511 => "HTTP_NETWORK_AUTHENTICATION_REQUIRED",                             // RFC6585
        );
        return array_key_exists($code, $statusNames) ? $statusNames[$code] : "HTTP_UNKNOWN";
    }
}

if (!function_exists('status_code_2_text')) {
    function status_code_2_text($code)
    {
        $statusTexts = Response::$statusTexts;
        return array_key_exists($code, $statusTexts) ? $statusTexts[$code] : "Unknown";
    }
}
