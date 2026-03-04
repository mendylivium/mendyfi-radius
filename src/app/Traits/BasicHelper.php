<?php

namespace App\Traits;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Process;

trait BasicHelper
{

    public function randomStr($length = 10, $characters = '0123456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ') {
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function getAppSetting()
    {
        $keys = func_get_args();

        if(count($keys) > 1) {
            return AppSetting::whereIn('name',$keys)
            ->pluck('value','name')
            ->toArray();
        } elseif(count($keys) == 1 ) {
            return AppSetting::where('name',$keys)
            ->first()
            ->value ?? null;
        }

        return [];
    }

    public function setAppSetting($keys)
    {
        // Build CASE WHEN with parameter bindings to prevent SQL injection.
        $bindings = [];
        $when     = '';
        foreach ($keys as $key => $value) {
            $when      .= "WHEN name = ? THEN ? ";
            $bindings[] = $key;
            $bindings[] = $value;
        }

        AppSetting::whereIn('name', array_keys($keys))
        ->update([
            'value' => \DB::raw("CASE {$when}END"),
        ], $bindings);
    }

    public function percent($value, $percentDeci)
    {
        return $value * ($percentDeci / 100);
    }

    public function showFlash($keys) {
        foreach($keys as $key => $value) {
            session()->flash($key, $value);
        }
    }

    public function analyzeConditions($input, $status) {
        // Split the input into lines
        $lines = explode("\n", trim($input));

        // Operators ordered longest-first so '>=' is matched before '>'.
        $operators = ['>=', '<=', '<>', '>', '<', '='];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line == '') continue;

            $found = false;
            foreach ($operators as $op) {
                $pos = strpos($line, $op);
                if ($pos !== false) {
                    $found = true;
                    $key   = trim(substr($line, 0, $pos));
                    $value = trim(substr($line, $pos + strlen($op)));

                    if ($key === '' || !is_numeric($value)) {
                        return false;
                    }

                    if (!array_key_exists($key, $status)) {
                        return false;
                    }

                    // Both sides are cast to float; no dynamic code execution.
                    $statusValue = (float) $status[$key];
                    $threshold   = (float) $value;

                    $result = match ($op) {
                        '>='  => $statusValue >= $threshold,
                        '<='  => $statusValue <= $threshold,
                        '>'   => $statusValue >  $threshold,
                        '<'   => $statusValue <  $threshold,
                        '<>'  => $statusValue != $threshold,
                        '='   => $statusValue == $threshold,
                        default => false,
                    };

                    if (!$result) {
                        return false;
                    }

                    break;
                }
            }

            if (!$found) {
                return false;
            }
        }
        return true;
    }

    public function parseAction($input) {
        // Split the input into lines
        $lines = explode("\n", trim($input));
        $result = [];

        // Loop through each line
        foreach ($lines as $line) {
            // Remove any extra spaces and ensure line is not empty
            $line = trim($line);
            if ($line == '') continue;

            // Split the line into key and value using '=' as the delimiter
            if (strpos($line, '=') === false) {
                return false; // Invalid format
            }

            list($key, $value) = explode('=', $line, 2);

            // Trim any whitespace from the key and value
            $key = trim($key);
            $value = trim($value);

            // Validate that the key is not empty and the value is numeric
            if ($key === '' || !is_numeric($value)) {
                return false; // Invalid format
            }

            // Add the key-value pair to the result array
            $result[$key] = $value;
        }

        return $result;
    }

    public function isConditionFormat($input) {
        // Split the input into lines
        $lines = explode("\n", trim($input));

        // Define the possible operators
        $operators = ['>=', '<=', '>', '<', '<>', '='];

        // Loop through each line
        foreach ($lines as $line) {
            // Remove any extra spaces and ensure line is not empty
            $line = trim($line);
            if ($line == '') continue;

            // Validate that the line contains one of the operators in the correct format
            $found = false;
            foreach ($operators as $op) {
                $pattern = '/^\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*' . preg_quote($op, '/') . '\s*([0-9]+(\.[0-9]+)?)\s*$/';
                if (preg_match($pattern, $line, $matches)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return false; // No valid operator found or invalid format
            }
        }
        return true;
    }


    public function isActionFormat($input) {
        // Split the input into lines
        $lines = explode("\n", trim($input));

        // Loop through each line
        foreach ($lines as $line) {
            // Remove any extra spaces and ensure line is not empty
            $line = trim($line);
            if ($line == '') continue;

            // Check if the line contains an '=' character
            if (strpos($line, '=') === false) {
                return false; // Invalid format
            }

            // Split the line into key and value using '=' as the delimiter
            list($key, $value) = explode('=', $line, 2);

            // Trim any whitespace from the key and value
            $key = trim($key);
            $value = trim($value);

            // Validate key and value
            if ($key === '' || !is_numeric($value)) {
                return false; // Invalid format
            }
        }

        return true;
    }

    public function getCurrentIp()
    {
        $command = "ip -o route get to 8.8.8.8 | sed -n 's/.*src \\([0-9.]*\\).*/\\1/p'";
        //$ip = exec($command);

        $ip = Process::run($command);


        return $ip->output();
    }

    function getPublicIp() {
        $response = file_get_contents("http://ipinfo.io/ip");
        return trim($response);
    }

    /**
     * Adjust the brightness of a hex color
     * @param string $hex Hex color code (e.g., #4e73df)
     * @param int $steps Positive for lighter, negative for darker
     * @return string Adjusted hex color
     */
    function adjustBrightness($hex, $steps) {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Convert hex color to oklch format for daisyUI 4 CSS variables.
     * Returns format: "L% C H" (e.g., "54.615% .215208 262.880917")
     */
    public static function hexToOklch(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) return '50% 0 0';

        // Hex to sRGB (0-1)
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        // sRGB to linear RGB (inverse gamma)
        $toLinear = fn($c) => $c <= 0.04045 ? $c / 12.92 : pow(($c + 0.055) / 1.055, 2.4);
        $r = $toLinear($r);
        $g = $toLinear($g);
        $b = $toLinear($b);

        // Linear RGB to LMS (M1 matrix)
        $l = 0.4122214708 * $r + 0.5363325363 * $g + 0.0514459929 * $b;
        $m = 0.2119034982 * $r + 0.6806995451 * $g + 0.1073969566 * $b;
        $s = 0.0883024619 * $r + 0.2817188376 * $g + 0.6299787005 * $b;

        // Cube root (safe for zero/negative)
        $cbrt = fn($x) => $x >= 0 ? pow($x, 1/3) : -pow(-$x, 1/3);
        $l = $cbrt($l);
        $m = $cbrt($m);
        $s = $cbrt($s);

        // LMS to OKLab (M2 matrix)
        $L = 0.2104542553 * $l + 0.7936177850 * $m - 0.0040720468 * $s;
        $a = 1.9779984951 * $l - 2.4285922050 * $m + 0.4505937099 * $s;
        $bVal = 0.0259040371 * $l + 0.7827717662 * $m - 0.8086757660 * $s;

        // OKLab to OKLCH
        $C = sqrt($a * $a + $bVal * $bVal);
        $H = ($C < 0.0001) ? 0 : atan2($bVal, $a) * 180 / M_PI;
        if ($H < 0) $H += 360;

        return sprintf('%.4f%% %.6f %.6f', $L * 100, $C, $H);
    }

    /**
     * Return oklch for text on a colored background (white or near-black).
     * Uses relative luminance to determine contrast.
     */
    public static function contrastOklch(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) return '100% 0 0';

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
        return $luminance > 0.55 ? '20% 0 0' : '100% 0 0';
    }
}
