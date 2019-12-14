<?php
    require_once 'login.php';
    require_once 'fatalerror.php';
    require_once 'defaultmodel.php';

    error_reporting(0);
    ini_set('display_errors', 0);

    session_start();
    if (isset($_SESSION['email']) && $_SESSION['check'] == hash('ripemd128', $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']))
    {
        $email = $_SESSION['email'];

        session_regenerate_id();

        // Need to deal with session fixation.

        echo <<<_START
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <title>Spanish Translate</title>
        </head>
        <body>
            <form method='post' action='translate.php' enctype='multipart/form-data'>
                <input type='submit' value='logout' name='logoutbutton'>
            </form>
            <br>
            <form method='post' action='translate.php' enctype='multipart/form-data' >
                Select File: <input type='file' name='uploadfileinput' size='10' >
                <input type='submit' value='Upload you own model'>
            </form>
            <br>
            <form method='post' action='translate.php' enctype='multipart/form-data' id='usrform'>
                <textarea name="englishcontent" form="usrform"></textarea>
                <input type="submit" value='Translate' name='translate'>
            </form>
_START;

        if(isset($_POST['logoutbutton'])){
            destroy_session_and_data();
            header("Location: index.php");
        }

        if(isset($_POST['translate'])){
            $englishcontent = $_POST['englishcontent'];
            if(strlen($englishcontent) > 0){
                $conn = new mysqli($hn, $un, $pw, $db);
                if ($conn->connect_error) die(mysql_fatal_error());

                $query = "SELECT * FROM Translations WHERE email='$email'";
                $result = $conn->query($query);

                if(!$result) die(mysql_fatal_error());
   
                $rows = $result->num_rows;

                $model = [];

                if($rows > 0){
                    for($i = 0; $i < $rows; $i++){
                        $result->data_seek($i);
                        $row = $result->fetch_array(MYSQLI_ASSOC);
                        
                        $english = $row['english'];
                        $spanish = $row['spanish'];

                        $model[$english] = $spanish;
                    }

                    $answer = convertModel($englishcontent."   ", $model);

                    echo "Converting in your model <br>";

                    echo $answer;

                    $result->close();
                    $conn->close();
                }else{
                    echo "Converting in default model <br>";
                    $answer = convertModel($englishcontent."   ", $defaultmodel);
                    echo $answer;
                }
                

            }
        }

        if($_FILES){
            $inputtype = $_FILES['uploadfileinput']['type'];
            if($inputtype == "text/plain"){

                $name = $_FILES['uploadfileinput']['name'];

                $fh = fopen($name, 'r') or die("File Does not exist");

                $filedata = filter_var(file_get_contents($name), FILTER_SANITIZE_STRING);

                fclose($fh);

                $model = readContents($filedata);

                $ans = convertModel("hi my name is rohan ", $model);

                echo "<br><br> $ans <br><br>";

                $conn = new mysqli($hn, $un, $pw, $db);
                if ($conn->connect_error) die(mysql_fatal_error());
                
                foreach($model as $key => $value) {
                    $query = "INSERT INTO Translations (email, english, spanish) VALUES ('$email', '$key', '$value')";
                    $result = $conn->query($query);
                    if(!$result){
                        echo "Error: 500, Already Exists <br>";
                    }
                }

                $conn->close();

            }
        }
  
    }
    else{
        destroy_session_and_data();
        echo "Please <a href='auth.php'>click here</a> to log in.";
    } 


    function destroy_session_and_data()
	{
        $_SESSION = array();
        setcookie(session_name(), '', time() - 2592000, '/');
		session_destroy();
    }
    
    function readContents($content){
        $model = [];

        while(TRUE){
            $indexopen = strpos($content, "(");
            $indexclose = strpos($content, ")");
            
            if(isset($indexopen) && isset($indexclose) && strlen($content) > 0){

                $commaindex = strpos($content, ",");
                $english = substr($content, $indexopen + 1, $commaindex - $indexopen - 1);

                $spanish = substr($content, $commaindex + 1, $indexclose - $commaindex - 1);

                $model[$english] = $spanish;

                $content = substr($content, $indexclose+1, strlen($content));
            }
            else
            {
                break;
            }
        }

        return $model;
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


?>