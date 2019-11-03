<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0">
    <meta name="description" content="Etäohjaustyökalu Android ja iOS laitteille">
    <title>ERJA tilin vahvistaminen</title>
    <meta charset="UTF-8" />
    <link href="https://fonts.googleapis.com/css?family=Ubuntu:400,500,700" rel="stylesheet">
    <style>
    * {
        padding: 0;
        margin: 0;
        box-sizing: border-box
    }

    header {
        background-color: #B10DC9;
        width: 100%
    }

    header h1 {
        font-weight: 700;
        text-align: center;
        line-height: 200px;
        font-size: 80px;
    }

    header h1 span {
        color: #ffffff
    }

    main {
        display: grid;
        grid-gap: 20px;
        padding: 20px;
        font-family: "Ubuntu", sans-serif;
        max-width: 1200px;
        width: 100%;
        margin-left: auto;
        margin-right: auto
    }

    form {
        display: grid;
        grid-gap: 10px;
        font-family: "Ubuntu", sans-serif
    }

    form div label,
    form div input,
    form div textarea {
        display: block;
        width: 100%
    }

    form label {
        font-size: 20px;
        margin-bottom: 6px
    }

    form input,
    form textarea {
        padding: 10px 15px;
        font-size: 18px;
        font-weight: 400;
        border-radius: 5px;
        border: 1px solid #B10DC9
    }

    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        font-family: "Ubuntu", sans-serif
    }

    img {
        max-width: 100%
    }

    nav {
        background-color: #ffffff;
        padding: 15px
    }

    .download {
        display: grid;
        grid-gap: 10px;
        grid-template-columns: 1fr 1fr
    }
    </style>
</head>
<body>
    <nav></nav>
    <header>
        <h1>ER<span>JA</span></h1>
    </header>
    <main>

        <article>
            <section>
            <?php
        
        if(isset($_GET['email'])){
            $email = $_GET['email'];
        }         
        if(isset($_GET['token'])){
            $token = $_GET['token'];
        }
    
        if(isset($token) && isset($email)){
            require("db.php");
            if(isConfirmed($email, $token)){                      
                echo "<h1 style='text-align: center; font-size: 30px'>Tilisi on jo aktivoitu.</h1><h2 style='margin-top: 20px; text-align: center'>Tilisi tiedot löytyvät sähköpostistasi.</h2>";
            } else {               
                require("classes/user.php");
                $userClass = new user();
                $status = $userClass->confirmAccount($email, $token);            
                if($status == "confirmed"){
                    echo "<h1 style='text-align: center; font-size: 30px'>Tilisi on nyt aktivoitu.</h1><h2 style='margin-top: 20px; text-align: center'>Tilisi tiedot on lähetetty sähköpostiisi. Voit nyt sulkea tämän sivun ja kirjautua sovellukseesi mobiililaitteella.</h2>";
                }            
            }
        }    
    
        function isConfirmed($email, $token){        
            $db = initDb();
            $isConfirmed = $db->prepare("SELECT confirmed FROM users WHERE email = :email AND token = :token");
            $isConfirmed->bindParam(":email", $email, PDO::PARAM_STR);
            $isConfirmed->bindParam(":token", $token, PDO::PARAM_STR);
            $isConfirmed->execute();
            $status = $isConfirmed->fetchColumn();
            return $status == 1 ? true : false;
        }
    
    ?>
            </section>
        </article>
        <span style="text-align: center">Copyright Jaakko Uusitalo</span>
    </main>
</body>
</html>

