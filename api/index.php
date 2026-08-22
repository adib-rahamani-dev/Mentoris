<?php

declare(strict_types=1);

// Vercel invokes PHP files from the /api directory. Keep the framework's
// canonical front controller in /public so local Apache/Laragon still works.
require dirname(__DIR__) . '/public/index.php';
