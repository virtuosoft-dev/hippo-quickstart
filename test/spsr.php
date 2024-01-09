<?php
/**
 * SQL PHP Serialized Replacement (SPSR) tool for safely replacing strings
 * in SQL 'quick' dumps that contain PHP serialized data.
 */
// Get the file, search string, replace string from the command line
$filePath = $argv[1];
$searchString = $argv[2];
$replaceString = $argv[3];
if (!$filePath || !$searchString || !$replaceString) {
    echo 'Please provide a file path, search string, and replace string.';
    exit(1);
}
// Load the file and replace the strings
$handle = fopen($filePath, 'r');
$tempFilePath = dirname($filePath) . '/output.sql';
$writeStream = fopen($tempFilePath, 'w');
$regex1 = "/('.*?'|[^',\s]+)(?=\s*,|\s*;|\s*$)/";
global $regex2;
$regex2 = "/s:(\d+):\"(.*?)\";/ms";
while (($line = fgets($handle)) !== false) {
    $origLine = trim($line);
    $line = $origLine;
    if (strpos($line, $searchString) !== false &&
        strpos($line, "(") === 0 &&
        (strpos($line, "),") !== false || strpos($line, ");") !== false)) {
        $startLine = substr($line, 0, 1);
        $endLine = substr($line, -2);
        $line = substr($line, 1, -2);
        $line = str_replace("\\0", "~0Placeholder", $line);
        $matches = [];
        preg_match_all($regex1, $line, $matches);
        $items = $matches[0];
        $line = implode( '', [$startLine, implode(",", array_map(function ($item) use ($searchString, $replaceString) {
            if (strpos($item, "'") === 0 && strrpos($item, "'") === strlen($item) - 1) {
                $item = substr($item, 1, -1);
                $item = str_replace($searchString, $replaceString, $item);
                if (isSerialized($item)) {
                    // Recalculate the length of the serialized strings
                    $item = json_decode(json_encode($item));
                    $item = str_replace("\\", "", $item);
                    $item = str_replace("~0Placeholder", "\0", $item);
                    global $regex2;
                    $item = preg_replace_callback($regex2, function ($matches) {
                        return 's:' . strlen($matches[2]) . ':"' . $matches[2] . '";';
                    }, $item);
                    $item = addslashes($item);
                }
                return implode('', ["'" , $item , "'"]);
            } else if ($item === 'null') {
                return null;
            } else if (is_numeric($item)) {
                return (float)$item;
            } else {
                return $item;
            }
        }, $items)), $endLine]);
    }
    fwrite($writeStream, $line . "\n");
}
fclose($handle);
fclose($writeStream);
rename($tempFilePath, $filePath);

function isSerialized($data, $strict = true) {
    if ($data[1] !== ':') {
        return false;
    }
    if (strlen($data) < 4) {
        return false;
    }
    if ($data === 'N;') {
        return true;
    }
    if ($strict) {
        $lastc = $data[strlen($data) - 1];
        if ($lastc !== ';' && $lastc !== '}') {
            return false;
        }
    } else {
        $semicolon = strpos($data, ';');
        $brace = strpos($data, '}');
        // Either ; or } must exist.
        if ($semicolon === false && $brace === false) {
            return false;
        }
        // But neither must be in the first X characters.
        if ($semicolon !== false && $semicolon < 3) {
            return false;
        }
        if ($brace !== false && $brace < 4) {
            return false;
        }
    }
    $token = $data[0];
    switch ($token) {
        case 's':
            if ($strict) {
                if ($data[strlen($data) - 2] !== '"') {
                    return false;
                }
            } else if (!strpos($data, '"')) {
                return false;
            }
            // Or else fall through.
        case 'a':
        case 'O':
        case 'E':
            return (bool)preg_match("/^" . $token . ":[0-9]+:/", $data);
        case 'b':
        case 'i':
        case 'd':
            $end = $strict ? '$' : '';
            return (bool)preg_match("/^" . $token . ":[0-9.E+-]+;" . $end . "/", $data);
    }
    return false;
}
