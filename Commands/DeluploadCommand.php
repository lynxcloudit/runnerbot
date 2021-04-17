<?php

/**
 * This file is part of the PHP RunnerBOT project.
 * https://areait.runpolito.it
 *
 * (c) RUN Polito APS - ETS
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

class DeluploadCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'delupload';

    /**
     * @var string
     */
    protected $description = 'Delete files uploaded with /upload';

    /**
     * @var string
     */
    protected $usage = '/delupload <CDN url>';

    /**
     * @var string
     */
    protected $version = '1.2.0';

    /**
     * Main command execution
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        $config = require __DIR__ . '/../config.php';
        $auth = $config['authids'];
        
        $message = $this->getMessage();
      //  $from       = $message->getFrom();
      //  $user_id    = $from->getId();
        $chat_id = $message->getChat()->getId();
        if(!in_array($chat_id, $auth))
        {
            $data_tlg = [
                'chat_id' => $chat_id,
                'text'    => "Non autorizzato",
            ];
        
            return Request::sendMessage($data_tlg);               
            die("Unauthorised");
        }
        $text    = trim($message->getText(true));
        if ($text === 'help') {
            $replytext = 'Command usage: ' . $this->getUsage();
        }
        else
        {
            if($text != NULL)
            {
                $replytext = $this->delete($text);
                
            }
            else
            {
                $replytext = "Devi inserire un link da eliminare /delupload <link>. Scrivi /delupload help per ulteriori info";
            }
        }
        $data_tlg = [
            'chat_id' => $chat_id,
            'text'    => $replytext,
        ];
        
        return Request::sendMessage($data_tlg);        

    }
    
    
    private function delete($url)
    {
                $config = require __DIR__ . '/../config.php';

                $rundb = $config['rundb'];

                    $mysqli = new \mysqli($rundb['host'], $rundb['user'], $rundb['password'], $rundb['database']);
                    $query = $mysqli->prepare("SELECT * FROM `cdn` WHERE link = ?");

                    $query->bind_param('s',$url);
                    $query->execute();
                    $result = mysqli_stmt_get_result($query);
                    
                    $rowcount=mysqli_num_rows($result);
                    if($rowcount == 0)
                    {
                        return "Hai già eliminato questo file. ";
                    }
                    $row = $result->fetch_array(MYSQLI_ASSOC);
                    
                    $path = $row['path'];
                    $uidf = $row['uidf'];
                    unlink($path);
                    $query = $mysqli->prepare("DELETE FROM `cdn` WHERE uidf = ?");
                    $query->bind_param('s',$uidf);
                    
                    if($query->execute())
                    {
                        $query->close();
                        return "File eliminato correttamente.";
                    }
                    else
                    {
                        $query->close();
                        return "Oh no! Qualcosa è andato storto! :(";
                    }
                        
        }
}