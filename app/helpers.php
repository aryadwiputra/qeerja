<?php

if (! function_exists('mask_phone')) {
    function mask_phone(?string $phone): string
    {
        if (! $phone || strlen($phone) < 6) {
            return '***';
        }

        return substr($phone, 0, 3).'****'.substr($phone, -3);
    }
}

if (! function_exists('format_whatsapp_message')) {
    function format_whatsapp_message(string $title, string $body, string $code, ?string $url = null): string
    {
        $msg = "{$title}\n{$body}\n{$code}";

        if ($url) {
            $msg .= "\n\n{$url}";
        }

        return $msg;
    }
}
