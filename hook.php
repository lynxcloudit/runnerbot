<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

// Load composer
require __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config.php';

$bot_api_key  = $config['api_key'];
$bot_username = $config['bot_username'];
$commands_paths = [
    __DIR__ . '/Commands',
];

try {
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($bot_api_key, $bot_username);

// Add this line inside the try{}
$telegram->addCommandsPaths($commands_paths);
    $telegram->enableAdmins($config['admins']);
$telegram->enableMySql($config['mysql']);
     $telegram->setDownloadPath($config['paths']['download']);
     $telegram->setUploadPath($config['paths']['upload']);
     
          Longman\TelegramBot\TelegramLog::initialize(
        new Monolog\Logger('telegram_bot', [
            (new Monolog\Handler\StreamHandler($config['logging']['debug'], Monolog\Logger::DEBUG))->setFormatter(new Monolog\Formatter\LineFormatter(null, null, true)),
            (new Monolog\Handler\StreamHandler($config['logging']['error'], Monolog\Logger::ERROR))->setFormatter(new Monolog\Formatter\LineFormatter(null, null, true)),
        ]),
        new Monolog\Logger('telegram_bot_updates', [
            (new Monolog\Handler\StreamHandler($config['logging']['update'], Monolog\Logger::INFO))->setFormatter(new Monolog\Formatter\LineFormatter('%message%' . PHP_EOL)),
        ])
     );
    // Handle telegram webhook request
    $telegram->handle();
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // Silence is golden!
    // log telegram errors
    // echo $e->getMessage();
}