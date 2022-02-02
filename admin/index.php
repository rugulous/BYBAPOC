<?php

require_once('../_lib/DB.php');

if(isset($_POST['name']) && trim($_POST['name']) != '' && isset($_POST['id']) && trim($_POST['id']) != ''){
    DB::executeQuery("INSERT INTO Organisation (Name, Identifier) VALUES (?, ?)", "ss", $_POST['name'], $_POST['id']);
    file_put_contents('../sites-required', $_POST['name'] . "\n" . $_POST['id'] . "\n", FILE_APPEND);
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
