<?php
/**
 * EnvLoader - Simple .env file parser
 * 
 * Loads environment variables from a .env file into $_ENV, $_SERVER, and getenv().
 * Handles comments, whitespace, and quoted values.
 */

class EnvLoader
{
    /**
     * Load environment variables from a .env file
     *
     * @param string $path Path to the directory containing .env
     * @param string $fileName default .env
     * @return void
     */
    public static function load($path, $fileName = '.env')
    {
        if (!file_exists($path . '/' . $fileName)) {
            return;
        }

        $lines = file($path . '/' . $fileName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Remove comments (starting with #)
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Split by the first equals sign
            list($name, $value) = explode('=', $line, 2);

            $name = trim($name);
            $value = trim($value);

            // Remove quotes if present
            if (preg_match('/^"(.*)"$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                $value = $matches[1];
            }

            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}
