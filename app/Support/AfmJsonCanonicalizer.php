<?php

namespace App\Support;

class AfmJsonCanonicalizer
{
    public static function canonicalize(array $payload): string
    {
        // Remove signature if present
        if (array_key_exists('signature', $payload)) {
            unset($payload['signature']);
        }

        // Sort keys recursively for deterministic order
        ksort($payload);
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = self::sortArrayRecursive($value);
            }
        }

        return json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    protected static function sortArrayRecursive(array $array): array
    {
        // For associative arrays, sort by key; for numeric arrays, sort by index
        // Check if array is associative
        $isAssociative = array_keys($array) !== range(0, count($array) - 1);
        
        if ($isAssociative) {
            ksort($array);
        }

        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $array[$k] = self::sortArrayRecursive($v);
            }
        }

        return $array;
    }
}
