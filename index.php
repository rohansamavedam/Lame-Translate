<!-- Author: Krishna Rohan Samavedam -->
<?php
    require_once 'login.php';
    require_once 'fatalerror.php';
    require_once 'defaultmodel.php';

    error_reporting(0);
    ini_set('display_errors', 0);

    echo <<<_START
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <title>Lame Translate</title>
        </head>
        <body>
            <div>
                <h1 class="mainHeading">welcome to lame translate! English -> Spanish</h1>
            </div>
            <div>
                <div>
                    <h3 class="semiHeading">already a member? log in here <a href="auth.php">login</a></h3>
                </div> 
                <div class="center">
                    <h3>Sign Up Form</h3>
                    <form method='post' action='index.php' enctype='multipart/form-data' >
                        email:<br>
                        <input type="email" maxlength="70" name="email" required><br>
                        password: (atleast 8 characters or more)<br>
                        <input type="password" minlength="8" name="passwordone" required><br>
                        retype password:<br>
                        <input type="password" minlength="8" name="passwordtwo" required><br>
                        <br>
                        <input type='submit' value='sign up'>
                    </form>
                </div> 
                <br>
                <form method='post' action='index.php' enctype='multipart/form-data' id='usrform'>
                    <textarea name="englishcontent" form="usrform"></textarea>
                    <input type="submit" value='Translate' name='translate'>
                </form>   
            </div>
_START;
    //PHP Code goes here.

    if(isset($_POST['email']) && isset($_POST['passwordone']) && isset($_POST['passwordtwo'])){

        $email = filter_var(($_POST["email"]), FILTER_SANITIZE_EMAIL);
        $passOne = filter_var($_POST["passwordone"], FILTER_SANITIZE_STRING);
        $passTwo = filter_var($_POST["passwordtwo"], FILTER_SANITIZE_STRING);

        if(strcmp($passOne, $passTwo) == 0){

            $conn = new mysqli($hn, $un, $pw, $db);
            if ($conn->connect_error) die(mysql_fatal_error());

            $saltOne = generateSalt();
            $saltTwo = generateSalt();

            $token = hash('ripemd128', $saltOne.$passOne.$saltTwo);
            
            $query = "INSERT INTO Users (email, passwordtoken, saltone, salttwo) VALUES ('$email', '$token', '$saltOne', '$saltTwo')";

            $result = $conn->query($query);

            if (!$result) {
                // When the user with same email already exists in the db
                echo "Error: 500";
            }
            else{
                echo "Sign Up successful, you can now login";
            }
            $conn->close();

        }else{
            echo "Passwords do not match, please fill out all the fields and submit the form again.";
        }


    }

    // Implement default translation model.

    if(isset($_POST['translate'])){
        $englishcontent = $_POST['englishcontent'];
        if(strlen($englishcontent) > 0){
            echo "Converting in default model <br>";
            $answer = convertModel($englishcontent."   ", $defaultmodel);
            echo $answer;
        }
    }    

    
    function generateSalt() {
        $charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789/\\][{}\'";:?.>,<!@#$%^&*()-_=+|';
        $randStringLen = 8;
   
        $randString = "";
        for ($i = 0; $i < $randStringLen; $i++) {
            $randString .= $charset[mt_rand(0, strlen($charset) - 1)];
        }
   
        return $randString;
   }

   function convertModel($content, $model){
        $converted = "";

        while(TRUE){
            $indexspace = strpos($content, " ");

            if(isset($indexspace) && strlen($content) > 0){
                $english = substr($content, 0, $indexspace);

                if(!$model[$english]){
                    $spanish = $english;
                }else{
                    $spanish = $model[$english];
                }

                $converted = $converted . " " . $spanish;

                $content = substr($content, $indexspace + 1, strlen($content));
            }
            else
            {
                break;
            }

        }

        return $converted;
    }

    echo <<<_END
        </body>
        </html>
_END;
?>