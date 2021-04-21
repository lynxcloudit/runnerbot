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

class ShortenCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'shorten';

    /**
     * @var string
     */
    protected $description = 'Show text';

    /**
     * @var string
     */
    protected $usage = '/shorten <custom url>';

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
            $reply  = $message->getReplyToMessage();
            if($reply != NULL)
            {
                $replytext1 = $reply->getText();
                $result = json_decode($this->shorten($replytext1, strtolower($text), $user_id), true);
                $replytext = $result['message']."\n\nShortened link: ".$result['shorturl']."\n\nQR Code: ".$result['shorturl'].".qr\n\nStats: ".$result['shorturl']."+";
                
            }
            else
            {
                $replytext = "Devi inserire un link da accorciare come risposta al comando /shorten. Scrivi /shorten help per ulteriori info";
            }
        }
        $data_tlg = [
            'chat_id' => $chat_id,
            'text'    => $replytext,
        ];
        
        return Request::sendMessage($data_tlg);        

    }
    
    
    private function shorten($url, $keyword, $userid)
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
		'url'      => $url,
		'keyword'  => $keyword,
		'title'    => "",
		'format'   => $format,
		'action'   => 'shorturl',
		'timestamp' => $timestamp,
		'signature' => $signature
	) );

        // Fetch and return content
        $data = curl_exec($ch);
        curl_close($ch);

        $jsr = json_decode($data, true);
        if($jsr['statusCode'] == "200")
        {
            $surl = $jsr['shorturl'];
            $timest = time();
            $owner = "TELEGRAMBOT_".$userid;
            $from = $_SERVER['SERVER_NAME'];
            $rundb = $config['rundb'];
            $mysqli = new \mysqli($rundb['host'], $rundb['user'], $rundb['password'], $rundb['database']);  
            $query = $mysqli->prepare("INSERT INTO `shortener`(`shorturl`, `owner`, `creato`, `from_serv`) VALUES (?,?,?,?)");
            $query->bind_param('ssis',$surl,$owner,$timest,$from);
            $query->execute();
        }

        return $data;        
    }
}
