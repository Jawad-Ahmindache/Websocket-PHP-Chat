<?php
namespace jChat\Room;

use jChatGlobal\jChatGlobal;
class RoomGroup extends AbstractRoom{
    function __construct($action,$from,$to,$message = "") {

        $this->result = array("type" => "error","message" => "Une erreur est survenue","data" => "");
        if($this->isGroupExist($to) && $this->isParticipant($to,$from)){               
                // In here, $to represent the roomID but in DM $to is the mate user ID
                if($action == "connect"){
                    $this->actionConnect($to,$to);
                }

                if($action == "message"){
                    $this->actionMessage($to,$from,$message);
                    
                }
            
        }else{
            $this->result = array("type" => "error","message" => "Vous n'avez pas le droit d'accéder à ce groupe","data" => "");

        }
      
    }

    private function isGroupExist($room_id){
        global $db;
        $sql = 'SELECT id FROM room WHERE id = :room_id AND type = "Group"';
        $req = $db->prepare($sql);
        $req->execute(array("room_id" => $room_id));

        if($req->rowCount() > 0){
            $req->closeCursor();
            return true;
        }
        else{
            return false;
        }
    }
}