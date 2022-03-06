<?php
namespace jChat\Room;


use jChatGlobal\jChatGlobal;
class RoomDM extends AbstractRoom{



    function __construct($action,$from,$to,$message = "") {

        $this->result = array("type" => "error","message" => "Une erreur est survenue","data" => "");
       
        if(jChatGlobal::isUserExist($from) && jChatGlobal::isUserExist($to)){

            $roomID = $this->checkQuery($from,$to); 
            if($roomID != false){
                
                if($action == "connect"){
                    $this->actionConnect($roomID,$to);
                }
                if($action == "message"){
                    $this->actionMessage($roomID,$from,$message);
                    
                }
            }

       

        }
      
    }

    // This function check if DM exist exist and return a value
    private function checkQuery($from,$to){
        if($this->isDMExist($from,$to)){
            $RoomID = $this->getRoomDmIDByParticipant($from,$to);
            
        }else{
            $RoomID = $this->setRoom("DM");
            $this->addParticipant($RoomID,$from,"DM");
            $this->addParticipant($RoomID,$to,"DM");

        }

        return $RoomID;
    }

    
    
    


    public function getRoomDmIDByParticipant($from,$to){
        global $db;
        $sql = 'SELECT room_id FROM participant WHERE participant_name = :participant_name AND type = "DM"';
        $req = $db->prepare($sql);
        $req->execute(array("participant_name" => $from));
        $room_id = false;
        
        if($req->rowCount() > 0){
            $dataFrom = $req->fetchAll();
            $req->closeCursor();
            $req = $db->prepare($sql);
            $req->execute(array("participant_name" => $to));
            $dataTo = $req->fetchAll();
         
            if($req->rowCount() > 0){
                
                    for($i = 0; $i < count($dataFrom);$i++){
                        for($a = 0; $a < count($dataTo); $a++){
                            if($dataFrom[$i]['room_id'] == $dataTo[$a]['room_id']){
                                $room_id = $dataFrom[$i]['room_id'];
                                break 2;
                            }
                        }
                    }
            }
        }
        return $room_id;
    }

    private function isDMExist($from,$to){
        global $db;
        $sql = 'SELECT room_id FROM participant WHERE type = "DM" AND participant_name = :participant_name';
        $isExist = false;
        
        //FROM
        $req = $db->prepare($sql);
        $req->execute(array('participant_name' => $from));
        $from = $req->fetchAll();
        $req->closeCursor();
        
        // TO
        $req = $db->prepare($sql);
        $req->execute(array('participant_name' => $to));
        $to = $req->fetchAll();
        $req->closeCursor();

        for($i = 0; $i < count($from);$i++){
            for($a = 0; $a < count($to);$a++){
                if($from[$i]['room_id'] == $to[$a]['room_id']){

                    $isExist = true;
                    break 2;
                }
            }
        }
        
        return $isExist;
    }

}