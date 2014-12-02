<?php

spl_autoload_register(function ($class_name) {
    $class_name = ltrim($class_name, '\\');
    if ($lastNsPos = strrpos($class_name, '\\'))
    {
        $class_name = substr($class_name, $lastNsPos + 1);
    }
    $loadFolders = array('util', 'model'); //specify the folders to load
    foreach ($loadFolders as $folder) {
        $filePath = dirname(__FILE__) . "/$folder/$class_name.php";
        if (file_exists($filePath)) {
            require_once $filePath;
        }
    }
});