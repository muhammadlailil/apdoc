<?php

// for development only, remove this
if (!function_exists('getdoc_file')) {
    function getdoc_file($file)
    {
        $file = file_get_contents(storage_path(config('apdoc.output')).$file);
        return json_decode($file, true);
    }
}

