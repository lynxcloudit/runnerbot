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
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

class UploadCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'upload';

    /**
     * @var string
     */
    protected $description = 'Upload and save files';

    /**
     * @var string
     */
    protected $usage = '/upload';

    /**
     * @var string
     */
    protected $version = '0.2.0';

    /**
     * @var bool
     */
    protected $need_mysql = true;

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
        $chat    = $message->getChat();
        $chat_id = $chat->getId();
        $user_id = $message->getFrom()->getId();
        if(!in_array($chat_id, $auth))
        {
            $data_tlg = [
                'chat_id' => $chat_id,
                'text'    => "Non autorizzato",
            ];
        
            return Request::sendMessage($data_tlg);               
            die("Unauthorised");
        }
        // Make sure the Download path has been defined and exists
        $download_path = $this->telegram->getDownloadPath();
        if (!is_dir($download_path)) {
            return $this->replyToChat('Sembra esserci un problema di configurazione. Scrivi a quei pelandroni dell\'Area IT!');
        }

        // Initialise the data array for the response
        $data['chat_id'] =  $chat_id;

        if ($chat->isGroupChat() || $chat->isSuperGroup()) {
            // Reply to message id is applied by default
            $data['reply_to_message_id'] = $message->getMessageId();
            // Force reply is applied by default to work with privacy on
            $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
        }

        // Start conversation
        $conversation = new Conversation($user_id, $chat_id, $this->getName());
        $message_type = $message->getType();

        if (in_array($message_type, ['audio', 'document', 'photo', 'video', 'voice'], true)) {
            $doc = $message->{'get' . ucfirst($message_type)}();

            // For photos, get the best quality!
            ($message_type === 'photo') && $doc = end($doc);

            $file_id = $doc->getFileId();
            $file    = Request::getFile(['file_id' => $file_id]);
            if ($file->isOk() && Request::downloadFile($file->getResult())) {
                $result = $this->cdnupload($download_path . '/' . $file->getResult()->getFilePath(), $download_path, "TELEGRAMBOT_".$user_id);
                $data['text'] = $result;
            } else {
                $data['text'] = 'Impossibile scaricare la risorsa.';
            }

            $conversation->notes['file_id'] = $file_id;
            $conversation->update();
            $conversation->stop();
        } else {
            $data['text'] = 'Invia il file qui. ATTENZIONE: il file che caricherai sarà pubblicamente accessibile!';
        }

        return Request::sendMessage($data);
    }
    
    public function cdnupload($tempfile, $path, $user)
    {
        $config = require __DIR__ . '/../config.php';
        $cdnconf = $config['cdn'];
        
        $base = $cdnconf['path'];
        $cdnurl = $cdnconf['urlbase'];

        $temp = explode(".", $tempfile);
        $newfilename = strtoupper(uniqid()) . '.' . end($temp);
        if(rename($tempfile, $path."/".$newfilename))
        {
            //  if($clamav->scan("".$newfilename)) 
            //   {
                $mime = mime_content_type($path."/".$newfilename);
                $extp = explode("/", $mime);
                
                $ext = $extp[0];
                
                switch ($ext)
                {
                    case "image" :
                        $sfolder = "/img/";
                    break;
                    case "video" :
                        $sfolder = "/video/";
                    break;
                    case "application" :
                        $sfolder = "/docs/";
                    break;
                    case "text" :
                        $sfolder = "/docs/";
                    break;
                    default :
                        $sfolder = "/misc/";
                    break;
                }
                $folder = $base.$sfolder;
        
                rename($path."/".$newfilename, $folder.$newfilename);
                $path1 = $folder.$newfilename;
                $url = $cdnurl.$sfolder.$newfilename;
                //INSERT INTO `cdn`(`nome`, `descr`, `owner`, `ndl`, `link`, `path`, `mime`, `exp`, `creato`) VALUES ()        
                //mettere credenziali MySQL su config.php!
                $rundb = $config['rundb'];

                    $mysqli = new \mysqli($rundb['host'], $rundb['user'], $rundb['password'], $rundb['database']);
                    $query = $mysqli->prepare("INSERT INTO `cdn`(`uidf`,`nome`, `descr`, `owner`, `link`, `path`, `mime`, `creato`) VALUES (?,?,?,?,?,?,?,?)");
                    $query->bind_param('sssssssi',$newfilename,$newfilename,$newfilename,$user,$url,$path1,$mime,time());
                    $query->execute();
                //unlink("./tmp/".$newfilename);
                    unlink($path."/".$newfilename);

                return "File caricato correttamente.\n\nURL pubblico risorsa: ".$cdnurl.$sfolder.$newfilename;
             //   http_response_code(200);
                //   } 
              //  else
                //    {
                //        unlink("./tmp/".$newfilename);
                //        rpl::log($uid, "[WARN]", "cdn/upload", "upload file infetto bloccato: ".$clamav->getMessage());
                //        header("HTTP/1.1 415 "."File infetto: " . $clamav->getMessage());
                //    }    
        }
        else
        {
            return "Oh crap, something went south!";
            //rpl::log($uid, "[ERROR]", "cdn/upload", "upload file non riuscito: ".$_FILES['upfile']['name']);
          //  http_response_code(415);
        }
    }
    
}