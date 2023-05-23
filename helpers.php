<?php

use Twig\Extension\DebugExtension;

require_once 'vendor/autoload.php';

function loadTemp($viewPath, $viewFile, $var = []): void
{
    $loader = new \Twig\Loader\FilesystemLoader($viewPath);
    $twig = new \Twig\Environment($loader, [
        'debug' => true,
    ]);

    $twig->addGlobal('session', $_SESSION);
    $twig->addGlobal('post', $_POST);
    $twig->addExtension(new DebugExtension());

    $twig->display($viewFile, $var);
}

function error($code, $message)
{
    http_response_code($code);
    loadTemp('../views', 'error.twig', ['code' => $code, 'message' => $message]);
    exit();
}

function displayError($message, $type) 
{
    if ($type == 'error') {
        ?>
        <div class="bg-zinc-200 p-4 m-4 rounded mx-auto text-center w-96">
            <div class="text-lg font-bold text-red-600">
                <?php echo "Error: " . $message ?>
            </div>
        </div>
        <?php
    } else {
        ?>
        <div class="bg-zinc-200 p-4 m-4 rounded mx-auto text-center w-96">
            <div class="text-lg font-bold text-black">
                <?php echo $message ?>
            </div>
        </div>
        <?php
    }
}