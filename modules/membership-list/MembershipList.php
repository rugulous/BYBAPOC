<?php

//equivalent of service in .NET?
class MembershipList{
    
    private $_db;
    
    public function __construct($db){
        $this->_db = $db;
    }
    
    public function getMembershipList($season){
        return $this->_db->executeQuery("SELECT b.* FROM BandMembership bm INNER JOIN Band b ON b.ID = bm.BandID WHERE bm.Season = ?", "i", $season);
    }
    
}
