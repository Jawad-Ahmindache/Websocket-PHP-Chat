<?php
namespace jChat\Room;
use jChatGlobal\jChatGlobal;

class AbstractRoom{
    //Put a JSON data when object use finished
    protected $result;
    

    // Create a room
    public function setRoom($type){
        global $db;
        $sql = "INSERT INTO room(type,lastmsg_date) VALUES(:type,:lastmsg_date)";
        $req = $db->prepare($sql);
        $req->execute(array(
            "type" => $type,
            "lastmsg_date" => jChatGlobal::getDateTime()
        ));
        $req->closeCursor();

        return $db->lastInsertId();
    }

    

    public function addParticipant($room_id,$userID,$type){
        global $db;
        $sql = "INSERT INTO participant(participant_name,room_id,type) VALUES(:userid,:room_id,:type)";
        $req = $db->prepare($sql);
        $req->execute(array(
            "userid" => $userID,
            "room_id" => $room_id,
            "type" => $type
        ));
        $req->closeCursor();

    }

    public function getAllRoomMessages($room_id){
        global $db;
        $sql = "SELECT date,participant_id,message FROM message WHERE room_id = :room_id ORDER BY date ASC";
        $req = $db->prepare($sql);
        $req->execute(array("room_id" => $room_id));

        if($req->rowCount() > 0){
            $data = $req->fetchAll(\PDO::FETCH_ASSOC);
            $req->closeCursor();

            return json_encode($data);
        }else{
            $req->closeCursor();
            return false;
        }


    }

    public function isParticipant($room_id,$user_id){
        global $db;
        $sql = "SELECT participant_name FROM participant WHERE room_id = :room_id AND participant_name = :userID";
        $req = $db->prepare($sql);
        $req->execute(array("room_id" => $room_id,"userID" => $user_id));
        if($req->rowCount() > 0){
            $req->closeCursor();
            return true;
        }
        else{
            return false;
        }
    }
    



    public function putMessageToRoom($room_id,$participant_id,$message){
        global $db;
        $sql = "INSERT INTO message(room_id,date,participant_id,message) VALUES(:room_id,:date,:participant_id,:message)";
        $req = $db->prepare($sql);
        $date = jChatGlobal::getDateTime();
        $req->execute(array(
            "room_id" => $room_id,
            "date" => $date,
            "participant_id" => $participant_id,
            "message" => $message,
        ));
        $req->closeCursor();

        $sql = "UPDATE room SET lastmsg_date = :lastmsg_date WHERE id = :room_id";
        $req = $db->prepare($sql);
        $req->execute(array(
            "room_id" => $room_id,
            "lastmsg_date" => $date
        ));
        $req->closeCursor();


        $result['participant_id'] = $participant_id;
        $result['date'] = $date;
        $result['message'] = $message;
        return $result;
    }

    public function formatSingleMSGinfo($date,$participant_id,$message){
        $result[0] = new \stdClass();
        $result[0]->date = $date;
        $result[0]->participant_id = $participant_id;
        $result[0]->message = $message;
        return $result;
    }
    public function getResult(){
        return $this->result;
    }

    public function actionConnect($roomID,$to){
        $listMessage = $this->getAllRoomMessages($roomID);        
        $listUserInfo = jChatGlobal::getAllUserInfos("",(array)json_decode($listMessage));
        $this->result = array("type" => "connect-success","message" => "Connexion réussie","data" => array("list_message" => $listMessage,"list_user" => $listUserInfo,"room_id" => $roomID,"to_id" => $to));
    }

    public function actionMessage($room_id,$participant_id,$message){

        if(strlen($message) > 0){

            $message = htmlspecialchars($message);
            $getMessage = $this->putMessageToRoom($room_id,$participant_id,$message);
            $getMessage = $this->formatSingleMSGinfo($getMessage['date'],$getMessage['participant_id'],$getMessage['message']);
            $userInfo = jChatGlobal::getAllUserInfos("",$getMessage);
            $this->result = array("type" => "message-success","message" => "Message envoyé","data" => array('list_message' => $getMessage,'list_user' => $userInfo,'room_id' => $room_id));

        }
        else{
            $this->result = array("type" => "error","message" => "Le message doit être supérieur à 0 caractères","data" => "");    
        }
    }

}