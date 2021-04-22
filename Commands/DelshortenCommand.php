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

class DelshortenCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'delshorten';

    /**
     * @var string
     */
    protected $description = 'Show text';

    /**
     * @var string
     */
    protected $usage = '/delshorten <short url>';

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
        $from       = $message->getFrom();
        $user_id    = $from->getId();
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
                $url = strtolower($text);
                $result = json_decode($this->delshorten($url, $user_id), true);
                
                switch ($result['message']) {
                    case "success: deleted":
                        $replytext = "Shortened link ".$url." succesfully deleted.";
                        break;
                    case "error: not found":
                        $replytext = "Shortened link ".$url." doesn't exist: maybe it has already been deleted?";                        
                        break;
                        
                    default:
                        $replytext = "Whoops! An error occurred, ".$url." not deleted. Contact IT support (".$config['itsupport']."). Diag: ".$result['message'];
                        break;
                }
                
            }
            else
            {
                $replytext = "Devi inserire un link accorciato da eliminare /shorten <link>. Scrivi /shorten help per ulteriori info";
            }
        }
        $data_tlg = [
            'chat_id' => $chat_id,
            'text'    => $replytext,
        ];
        
        return Request::sendMessage($data_tlg);        

    }
    
    
    private function delshorten($url, $userid)
    {
        $config = require __DIR__ . '/../config.php';
        $yls = $config['yourls'];
        
        $timestamp = time();
        $signature = md5( $timestamp . $yls['token'] );

        $format  = 'json';                       // output format: 'json', 'xml' or 'simple'

        $api_url = $yls['apiurl'];

        // Init the CURL session
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $api_url );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );            // No header in the result
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true ); // Return, do not echo result
        curl_setopt( $ch, CURLOPT_POST, 1 );              // This is a POST request
        curl_setopt( $ch, CURLOPT_POSTFIELDS, array(      // Data to POST
		'shorturl'      => $url,
		'format'   => $format,
		'action'   => 'delete',
		'timestamp' => $timestamp,
		'signature' => $signature
	) );

        // Fetch and return content
        $data = curl_exec($ch);
        curl_close($ch);

        $jsr = json_decode($data, true);
        if($jsr['statusCode'] == "200")
        {
            $rundb = $config['rundb'];
            $mysqli = new \mysqli($rundb['host'], $rundb['user'], $rundb['password'], $rundb['database']);  
            $query = $mysqli->prepare("DELETE FROM `shortener` WHERE shorturl = ?");
            $query->bind_param('s',$url);
            $query->execute();
        }

        return $data;        
    }
}