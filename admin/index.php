<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../_lib/DB.php');

if(isset($_POST['name']) && trim($_POST['name']) != '' && isset($_POST['id']) && trim($_POST['id']) != ''){
    //DB::executeQuery("INSERT INTO Organisation (Name, Identifier, CreatedDate) VALUES (?, ?, NOW())", "ss", $_POST['name'], $_POST['id']);
    
    //1. Create new database for organisation
    $cpCon = DB::getConnection(DB::$DB_NONE);
    DB::executeNonQueryWithCon($cpCon, "CREATE DATABASE " . mysqli_real_escape_string($cpCon, $_POST['id']));
    
    //2. Create new user for new database
    $dbUser = $_POST['id'] . "_user";
    $pass = md5(bin2hex(openssl_random_pseudo_bytes(8)));
    echo "Creating user " . $dbUser . " with password '" . $pass . "' <br />";
    
    DB::executeNonQueryWithCon($cpCon, "CREATE USER '" . $dbUser . "'@'localhost' IDENTIFIED BY '" . $pass ."'");
    
    //3. Grant privileges on new database
    DB::executeNonQueryWithCon($cpCon, "GRANT SELECT,INSERT,UPDATE,DELETE ON " . $_POST['id'] . ".* TO '" . $dbUser . "'@'localhost'");
    DB::executeNonQueryWithCon($cpCon, "FLUSH PRIVILEGES");
    
    DB::closeConnection($cpCon);
    
    //4. List all tables
    $masterCon = DB::getConnection("master");
    $tables = DB::executeQueryWithCon($masterCon, "show tables");
    DB::closeConnection($masterCon);
    
    //5. Create tables in new DB
    $orgCon = DB::getConnection($_POST['id']);
    
    foreach($tables as $_table){
        $table = array_values($_table)[0];
        
        echo "Copying {$table}...<br />";
        
        DB::executeNonQueryWithCon($orgCon, "CREATE TABLE " . $table . " LIKE master." . $table);
    }
    
    DB::closeConnection($orgCon);
    
    //6. Create sites-required directory if not exists
    if (!file_exists('../sites-required/')) {
        mkdir('../sites-required/', 0777, true);
    }
    
    //7. Create config file ready for bash to process
    file_put_contents('../sites-required/' . $_POST['id'], "name='" . $_POST['name'] . "'\nid='" . $_POST['id']. "'\ndb_user='$dbUser'\ndb_pass='$pass'");
    
    //file_put_contents('../sites-required', $_POST['name'] . "\n" . $_POST['id'] . "\n" . $dbUser . "\n" . $pass . "\n", FILE_APPEND);
    header("Location: ?org=" . $_POST['name'] . "&success=1");
    
} else if(isset($_GET['id_check']) && trim($_GET['id_check']) != ""){
    $result = DB::executeQuery("SELECT * FROM Organisation WHERE Identifier = ?", "s", $_GET['id_check']);
    if(count($result) > 0){
        die(false);
    } else {
        die(true);
    }
}

?>
<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">

    <title>Bandmaster Admin</title>
</head>

<body>
    <div class="container" id="app">
        <h1 class="mb-3">Add New Organisation</h1>

        <div class="card card-body">
            <form method="post">
                <div class="form-group mb-3">
                    <label for="name">Organisation Name</label>
                    <input type="text" name="name" id="name" class="form-control" v-model="org" />
                </div>

                <div class="form-group mb-3">
                    <label for="name">Organisation ID</label>
                    <input type="text" name="id" id="id" class="form-control" :value="orgID" readonly />
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-lg btn-success" v-on:click="checkID">Create</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
    <script>
        let app = new Vue({
            el: '#app',
            data: {
                org: ''
            },
            computed: {
                orgID: function() {
                    if (this.org.indexOf(" ") < 0) {
                        return this.org.toUpperCase();
                    }

                    let words = this.org.split(" ");
                    let ID = '';
                    words.forEach(w => {
                        if (w.length > 0) {
                            ID += w[0];
                        }
                    });
                    return ID.toUpperCase();
                }
            },
            methods: {
                checkID: function(e) {
                    e.preventDefault();
                    fetch("?id_check=" + this.orgID)
                        .then(res => res.text())
                        .then(t => {
                            if (t == 1) {
                                e.target.form.submit();
                            } else {
                                let id = document.getElementById("id");
                                id.readOnly = false;
                                id.setCustomValidity("This ID is already in use!");
                                id.reportValidity();
                            }
                        });
                }
            }
        });

    </script>
</body>

</html>
