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
        //$keys = func_get_arg();

        $query = "CASE " . PHP_EOL;
        foreach($keys as $key => $value) {
            $query .= "WHEN `name` = '{$key}' THEN '{$value}'" . PHP_EOL;
        }
        $query .= "END";

        AppSetting::whereIn('name', array_keys($keys))
        ->update([
            'value' =>  \DB::raw($query)
        ]);
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
    
        // Define the possible operators and their corresponding PHP equivalents
        $operators = [
            '>=' => '>=',
            '<=' => '<=',
            '>'  => '>',
            '<'  => '<',
            '<>' => '!=',
            '='  => '=='
        ];
    
        // Loop through each line
        foreach ($lines as $line) {
            // Remove any extra spaces and ensure line is not empty
            $line = trim($line);
            if ($line == '') continue;
    
            // Find the operator in the line
            $found = false;
            foreach ($operators as $op => $phpOp) {
                $pos = strpos($line, $op);
                if ($pos !== false) {
                    $found = true;
                    // Split the line into key and value
                    $key = trim(substr($line, 0, $pos));
                    $value = trim(substr($line, $pos + strlen($op)));
    
                    // Validate key and value
                    if ($key === '' || !is_numeric($value)) {
                        return false; // Invalid format
                    }
    
                    // Check if the key exists in the status array
                    if (array_key_exists($key, $status)) {
                        // Evaluate the condition
                        $statusValue = $status[$key];
                        $result = eval("return $statusValue $phpOp $value;");
                        if (!$result) {
                            return false;
                        }
                    } else {
                        return false; // Key does not exist
                    }
                    break;
                }
            }
            if (!$found) {
                return false; // No valid operator found
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
        // Remove # if present
        $hex = ltrim($hex, '#');
        
        // Convert to RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Adjust brightness
        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));
        
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}