<?php

//essentially an interface to the database - so if we scrap mysql, it's just this file that needs changing!
class DB{
    
    protected static function getConnection(){
        $host = getenv("DB_HOST");
        $user = getenv("DB_USER");
        $pass = getenv("DB_PASS");
        $db = getenv("DB_DB");
        
        $con = mysqli_connect($host, $user, $pass, $db) or die("Could not connect to $db with $user");
        return $con;
    }
    
    protected static function closeConnection($con){
        mysqli_close($con);
    }
    
    public static function executeQuery($query, $types = null, ...$args){
        $data = [];
        $con = self::getConnection();
        
        if(count($args) == 0){
            $result = mysqli_query($con, $query);
        } else {
            $stmt = mysqli_prepare($con, $query);
            echo mysqli_error($con);
            mysqli_stmt_bind_param($stmt, $types, ...$args);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        }
        
        while($row = mysqli_fetch_assoc($result)){
            $data[] = $row;
        }
        
        self::closeConnection($con);
        
        return $data;
    }
    
}
