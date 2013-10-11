<?php

/*

bWAPP or a buggy web application is a free and open source web application
build to allow security enthusiasts, students and developers to better secure web applications.
It is for educational purposes only.

Please feel free to grab the code and make any improvements you want.
Just say thanks.
https://twitter.com/MME_IT

© 2013 MME BVBA. All rights reserved.

*/

include("functions_external.php");
include("connect_i.php");

$message = "";

if(isset($_REQUEST["action"]))
{
    
    $login = $_REQUEST["login"];
    $password = $_REQUEST["password"];
    $password_conf = $_REQUEST["password_conf"];
    $email = $_REQUEST["email"];
    $secret = $_REQUEST["secret"];
    $mail_activation = isset($_POST["mail_activation"]) ? 1 : 0;
    
    if($login == "" or $email == "" or $password == "" or $secret == "")
    {
        
        $message = "<font color=\"red\">Please enter all the fields!</font>";       
        
    }
    
    else         
    {       
        
        /*

        /^[a-z\d_]{2,20}$/i
        ||||  | |||     |||
        ||||  | |||     ||i : case insensitive
        ||||  | |||     |/ : end of regex
        ||||  | |||     $ : end of text
        ||||  | ||{2,20} : repeated 2 to 20 times
        ||||  | |] : end character group
        ||||  | _ : underscore
        ||||  \d : any digit
        |||a-z: 'a' through 'z'
        ||[ : start character group
        |^ : beginning of text
        / : regex start

         */
        
        if(preg_match("/^[a-z\d_]{2,20}$/i", $login) == false)
        {
        
            $message = "<font color=\"red\">Please choose a valid login name!</font>";             
        
        }
    
        else
        {

            if(!filter_var($email, FILTER_VALIDATE_EMAIL))
            {

                $message = "<font color=\"red\">Please enter a valid e-mail address!</font>";     

            }

            else        
            {

                if($password != $password_conf)
                {

                    $message = "<font color=\"red\">The passwords don't match!</font>";       

                }

                else            
                {            

                    // Input validations
                    $login = mysqli_real_escape_string($link, $login);
                    $login = htmlspecialchars($login, ENT_QUOTES, "UTF-8");

                    $password = mysqli_real_escape_string($link, $password);
                    $password = hash("sha1", $password, false);

                    $email = mysqli_real_escape_string($link, $email);
                    $email = htmlspecialchars($email, ENT_QUOTES, "UTF-8");

                    $secret = mysqli_real_escape_string($link, $secret);
                    $secret = htmlspecialchars($secret, ENT_QUOTES, "UTF-8");

                    $sql = "SELECT * FROM users WHERE login = '" . $login . "' OR email = '" . $email . "'";

                    // Debugging
                    // echo $sql;

                    $recordset = $link->query($sql);             

                    if (!$recordset)
                    {

                        die("Error: " . $link->error);

                    }

                    // Debugging
                    // echo "<br />Affected rows: ";
                    // printf($link->affected_rows);

                    $row = $recordset->fetch_object();   

                    // If the user is not present
                    if (!$row)
                    {

                        // Debugging                
                        // echo "<br />Row: "; 
                        // print_r($row);                   
                                                
                        if ($mail_activation == false)
                        {
                        
                            $sql = "INSERT INTO users (login, password, email, secret, activated) VALUES ('" . $login . "','" . $password . "','" . $email .  "','" . $secret . "',1)"; 

                            // Debugging
                            // echo $sql;

                            $recordset = $link->query($sql);

                            if (!$recordset)
                            {

                                die("Error: " . $link->error);

                            }

                            // Debugging
                            // echo "<br />Affected rows: ";
                            // printf($link->affected_rows);

                            $message = "<font color=\"green\">User successfully created!</font>";
                        
                        }
                        
                        else
                        {

                            // 'Activation code' generation
                            $activation_code = random_string();         
                            $activation_code = hash("sha1", $activation_code, false);

                            // Debugging
                            // echo $activation_code;

                            if($_POST["server"] != "")
                            {

                                ini_set( "SMTP", $_POST["server"]);

                            //Debugging
                            // $debug = "true";     

                            }

                            // Sends an activation mail to the user                    
                            $subject = "bWAPP - New User";                    
                            $server = $_SERVER["HTTP_HOST"];                                        
                            $sender = "bwapp@mailinator.com";

                            $content = "Welcome " . ucwords($login) . ",\n\n";
                            $content.= "Click the link to activate your new user:\n\nhttp://" . $server . "/bWAPP/user_activation.php?user=" . $login . "&activation_code=" . $activation_code . "\n\n";
                            $content.= "Greets from bWAPP!";                 

                            $status = @mail($email, $subject, $content, "From: $sender");                   

                            if($status != true)
                            {

                                $message = "<font color=\"red\">User not successfully created! An e-mail could not be send...</font>";

                                // Debugging
                                // die("Error: mail was NOT send");
                                // echo "Mail was NOT send";

                            }

                            else
                            {

                                $sql = "INSERT INTO users (login, password, email, secret, activation_code) VALUES ('" . $login . "','" . $password . "','" . $email .  "','" . $secret . "','" . $activation_code . "')"; 

                                // Debugging
                                // echo $sql;      

                                $recordset = $link->query($sql);

                                if (!$recordset)
                                {

                                    die("Error: " . $link->error);

                                }

                                // Debugging                   
                                // echo "<br />Affected rows: ";                
                                // printf($link->affected_rows);                        

                                // Debugging
                                // echo "Mail was send";

                                $message = "<font color=\"green\">User successfully created! An e-mail with an activation code has been sent.</font>";

                            }

                        }

                    }

                    else
                    {

                        $message = "<font color=\"red\">The login or e-mail already exists!</font>";

                    }

                }

            }

        }

    }

}

?>
<!DOCTYPE html>
<html>
    
<head>
        
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Architects+Daughter">
<link rel="stylesheet" type="text/css" href="stylesheets/stylesheet.css" media="screen" />
<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />

<!--<script src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script>-->
<script src="js/html5.js"></script>

<title>bWAPP - New User</title>

</head>

<body>
    
<header>

<h1>bWAPP</h1>

<h2>an extremely buggy web application !</h2>

</header>    

<div id="menu">
      
    <table>
        
        <tr>
            
            <td><a href="login.php">Login</font></a></td>
            <td><font color="#ffb717">New User</font></td>
            <td><a href="info.php">Info</a></td>
            <td><a href="http://itsecgames.blogspot.com" target="_blank">Blog</a></td>
            <td><a href="http://www.mmeit.be/en/training.htm" target="_blank">ITSEC Training</a></td>
            
        </tr>
        
    </table>   
   
</div> 

<div id="main">

    <h1>New User</h1>

    <p>Create a new user.</p>

    <form action="<?php echo($_SERVER["SCRIPT_NAME"]);?>" method="POST">

        <table>

        <tr><td>
  
        <p><label for="login">Login:</label><br />
        <input type="text" id="login" name="login"></p>
        
        </td>
        
        <td width="5"></td>

        <td>

        <p><label for="email">E-mail:</label><br />
        <input type="text" id="email" name="email" size="30"></p>

        </td></tr>

        <tr><td>

        <p><label for="password">Password:</label><br />
        <input type="password" id="password" name="password"></p>

        </td>
        
        <td width="25"></td>

        <td>

        <p><label for="password_conf">Re-type password:</label><br />
        <input type="password" id="password_conf" name="password_conf"></p>

        </td></tr>

        <tr><td colspan="3">

        <p><label for="secret">Secret:</label><br />
        <input type="text" id="secret" name="secret" size="40"></p>

        </td></tr>
   
        <tr><td>

        <p><label for="mail_activation">E-mail activation:</label>
        <input type="checkbox" id="mail_activation" name="mail_activation" value="">

        </td>
        
        <td width="5"></td>

        <td>

        <label for="server">Mail server:</label>
        <input type="text" id="server" name="server" value=""></p>

        </td></tr>

        </table>

        <button type="submit" name="action" value="create">Create</button>

    </form>

    </br >
    <?php    

    echo $message;

    $link->close();

    ?>        
</div>
    
<div id="side">    
    
    <a href="http://itsecgames.blogspot.com" target="blank_" class="button"><img src="./images/blogger.png"></a>
    <a href="http://be.linkedin.com/in/malikmesellem" target="blank_" class="button"><img src="./images/linkedin.png"></a>
    <a href="http://twitter.com/MME_IT" target="blank_" class="button"><img src="./images/twitter.png"></a>
    <a href="http://www.facebook.com/pages/MME-IT-Audits-Security/104153019664877" target="blank_" class="button"><img src="./images/facebook.png"></a>

</div>     
    
<div id="disclaimer">
          
    <p>bWAPP or a buggy web application is for educational purposes only / © 2013 <b>MME BVBA</b>. All rights reserved.</p>
   
</div>
    
<div id="bee">
    
    <img src="./images/bee_1.png">
    
</div>
      
</body>
    
</html>