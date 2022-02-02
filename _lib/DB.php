<?php

//essentially an interface to the database - so if we scrap mysql, it's just this file that needs changing!
class DB{
    
    public static $DB_NONE = "NONE";
    
    public static function getConnection($db = NULL){
        $user = getenv("DB_USER");
        $pass = getenv("DB_PASS");
        
        return self::getConnectionWithCredentials($user, $pass, $db);
    }
    
    public static function getConnectionWithCredentials($user, $pass, $db = NULL){
        $host = getenv("DB_HOST");
        
        if($db == NULL){
            $db = getenv("DB_DB");
        }
        
        if($db == self::$DB_NONE){
            $con = mysqli_connect($host, $user, $pass) or die("Connection failed");
        } else {
            $con = mysqli_connect($host, $user, $pass, $db) or die("Could not connect to $db with $user");
        }
        
        return $con;
    }
    
    public static function closeConnection($con){
        mysqli_close($con);
    }
    
    public static function executeQueryWithCon($con, $query, $types = null, ...$args){
        $data = [];
        
        if(count($args) == 0){
            $result = mysqli_query($con, $query);
        } else {
            $stmt = mysqli_prepare($con, $query);
            echo mysqli_error($con);
            mysqli_stmt_bind_param($stmt, $types, ...$args);
            mysqli_stmt_execute($stmt);
            echo mysqli_error($con);
            $result = mysqli_stmt_get_result($stmt);
        }
        
        while($row = mysqli_fetch_assoc($result)){
            $data[] = $row;
        }
        
        return $data;
    }
    
    public static function executeQuery($query, $types = null, ...$args){
        $con = self::getConnection();      
        $data = self::executeQueryWithCon($con, $query, $types, ...$args);
        self::closeConnection($con);
        
        return $data;
    }
    
    public static function executeNonQueryWithCon($con, $query, $types = null, ...$args){
        if(count($args) == 0){
            mysqli_query($con, $query);
        } else {
            $stmt = mysqli_prepare($con, $query);
            mysqli_stmt_bind_param($stmt, $types, ...$args);
            mysqli_stmt_execute($stmt);
        }
    }
    
    public static function executeNonQuery($query, $types = null, ...$args){
        $con = self::getConnection();
        self::executeNonQueryWithCon($con, $query, $types, ...$args);
        self::closeConnection($con);
    }
}
