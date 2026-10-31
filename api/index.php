<?php
declare(strict_types=1);

// Entrada serverless da Vercel (runtime vercel-php): delega ao front controller.
require dirname(__DIR__) . '/public/index.php';
