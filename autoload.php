<?php

spl_autoload_register(function ($class) {
    $parts = explode('\\', $class, 2);

    if (count($parts) < 2 || $parts[0] !== 'FastGit')
        return;

    $file = __DIR__ . '/src/' . $parts[1] . '.php';
    if (file_exists($file)) {
        require $file;
    }
});
