<?php
namespace jChat;
require "../db_connect.php";

require_once 'Abstract_Room.php';

require_once "Global.php";

require_once "Room_DM.php";
require_once "Room_Group.php";

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use jChatGlobal\jChatGlobal;
use jChat\Room\AbstractRoom;
use jChat\Room\RoomDM;
use jChat\Room\RoomGroup;

class Chat implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {

    }

    public function onMessage(ConnectionInterface $from, $msg) {   
       
        if(json_last_error() == JSON_ERROR_NONE	){
            $msg = json_decode($msg);
            $message = isset($msg->message) ? $msg->message : "";

            if(jChatGlobal::checkSocketKey($msg->fromUser,$msg->from) == true){

                
                // if user request a new connexion, the server will delete old connection if exist
                if($msg->action == "connect"){
                    $isClientExist = jChatGlobal::IsExistInClients($this->clients,$msg->from);
                    if($isClientExist["response"] == true){
                        $this->clients->detach($isClientExist['data']);
                    }
                }

                if($msg->type == "DM"){
                        $room = new RoomDM($msg->action,$msg->fromUser,$msg->to,$message);
                        

                }

                if($msg->type == "Group"){
                        $room = new RoomGroup($msg->action,$msg->fromUser,$msg->to,$message);

                }

                if(isset($room)){
                    $result = $room->getResult();
                        if($result['type'] == "connect-success"){
                            $from->socketSession = $msg->from;
                            $from->roomAttached = $result['data']['room_id'];
                            $this->clients->attach($from);
                            $from->send(json_encode($result));
                        }
                      
                        if($result['type'] == "message-success"){ 
                            
                            
                            foreach($this->clients as $client){
                                if($result['data']['room_id'] == $client->roomAttached){
                                        $client->send(json_encode($result));
                                }
                            }
                        }

                        if($result['type'] == "error"){
                            $from->send(json_encode($result));

                        }
                }

            }
            
            

        }

    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
            $conn->send(json_encode(array("type" => "error","message" => $e,"data" => array())));
            $this->clients->detach($conn);

    }

    
    
}
