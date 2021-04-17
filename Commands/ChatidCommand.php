<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;

use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;


/**
 * User "/echo" command
 *
 * Simply echo the input back to the user.
 */
class ChatidCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'chatid';

    /**
     * @var string
     */
    protected $description = 'Show chat ID';

    /**
     * @var string
     */
    protected $usage = '/chatid';

    /**
     * @var string
     */
    protected $version = '1.1.0';

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $text    = trim($message->getText(true));
        

        $data = [
            'chat_id' => $chat_id,
            'text'    => $chat_id,
        ];

        return Request::sendMessage($data);
    }
}
