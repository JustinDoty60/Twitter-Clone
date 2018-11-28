<!DOCTYPE html>
<?php
  require_once "pdo/pdo.php";

  session_start();//session_destroy();
  if( isset($_POST["login"]) && isset($_POST["username"]) && isset($_POST["password"]) ) {
      unset($_SESSION["username"]);
      $sql = "SELECT * FROM Twitter.users WHERE username=:username AND password=:password";
      $stmt = $pdo->prepare($sql);
      $stmt->execute(array(
          ':username' => htmlentities($_POST['username']),
          ':password' => htmlentities($_POST['password'])));
      if ($stmt->rowCount()==1) {
          $userinfo = $stmt->fetchAll(PDO::FETCH_ASSOC);
          foreach( $userinfo as $row) {
            $_SESSION["name"] = $row['name'];
            $_SESSION["user_id"] = $row['user_id'];
            $_SESSION["username"] = $row['username'];
          }
          $_SESSION["success"] = "Logged in.";
          header( 'Location: index.php' ) ;
          return;
      } else {
          $_SESSION["login_error"] = "Invalid login";
          header( 'Location: index.php' ) ;
          return;
      }
  }

  if( isset($_POST["logout"]) ) {
      session_destroy();
      header( 'Location: index.php' ) ;
      return;
  }

  if( isset($_POST["tweet_submit"])) {
    if(isset($_SESSION["success"]) && isset($_POST["tweet"]) && $_POST["tweet"] != "") {
      $sql = "INSERT INTO Twitter.messages (user_id, message)
                VALUES ($_SESSION[user_id], :tweet)";
      $stmt = $pdo->prepare($sql);
      $stmt->execute(array(
          ':tweet' => htmlentities($_POST['tweet'])));
    } else if(isset($_SESSION["success"]) && isset($_POST["tweet"]) && $_POST["tweet"] == "") {
      $_SESSION["tweet_error"] = "Invalid tweet";
      header( 'Location: index.php' ) ;
      return;
    } else {
      $_SESSION["tweet_error"] = "Please log in";
      header( 'Location: index.php' ) ;
      return;
    }
  }

  if( isset($_POST["like"]) && isset($_SESSION["success"])) {
    $sql = "SELECT * FROM Twitter.messagelikes
            WHERE :message_id = Twitter.messagelikes.message_id AND Twitter.messagelikes.user_id = $_SESSION[user_id]";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
        ':message_id' => htmlentities($_POST['like'])));
    $count = $stmt->rowCount();

    if($count == 0) {
      $sql = "INSERT INTO Twitter.messagelikes (message_id, user_id)
              VALUES (:message_id, $_SESSION[user_id])";
      $stmt = $pdo->prepare($sql);
      $stmt->execute(array(
          ':message_id' => htmlentities($_POST['like'])));
    } else if($count == 1) {
      $sql = "DELETE FROM Twitter.messagelikes
              WHERE message_id=:message_id AND user_id=$_SESSION[user_id]";
      $stmt = $pdo->prepare($sql);
      $stmt->execute(array(
          ':message_id' => htmlentities($_POST['like'])));
    }
  } else if( isset($_POST["like"]) && !isset($_SESSION["success"])) {
    $_SESSION["tweet_error"] = "Please log in";
    header( 'Location: index.php' ) ;
    return;
  }

  if( isset($_POST["signup"]) && isset($_POST["signup_name"]) && isset($_POST["signup_username"])
      && isset($_POST["signup_email"]) && isset($_POST["signup_password"] )
      && $_POST["signup_name"] != "" && $_POST["signup_username"] != "" && $_POST["signup_email"] != ""
      && $_POST["signup_password"] != "") {
        $sql = "INSERT INTO Twitter.users (username, name, email, password)
                  VALUES (:username, :name, :email, :password)";
        $stmt = $pdo->prepare($sql);
        $log = "SELECT * FROM Twitter.users WHERE username=:username AND password=:password";
        $logstmt = $pdo->prepare($log);

        $stmt->execute(array(
            ':name' => htmlentities($_POST['signup_name']),
            ':username' => htmlentities($_POST['signup_username']),
            ':email' => htmlentities($_POST['signup_email']),
            ':password' => htmlentities($_POST['signup_password'])));

        $logstmt->execute(array(
            ':username' => htmlentities($_POST['signup_username']),
            ':password' => htmlentities($_POST['signup_password'])));

        $userinfo = $logstmt->fetchAll(PDO::FETCH_ASSOC);
        foreach( $userinfo as $row) {
          $_SESSION["name"] = $row['name'];
          $_SESSION["user_id"] = $row['user_id'];
          $_SESSION["username"] = $row['username'];
        }

        $_SESSION["success"] = "Logged in.";
        header( 'Location: index.php' ) ;
        return;
  } else if (isset($_POST["signup"])) {
    header( 'Location: index.php' ) ;
    return;
  }
 ?>

<head>
  <title> Twitter </title>
  <link rel="shortcut icon" href="img/logo.ico" />
  <link rel="stylesheet" type="text/css" href="style.css">
  <script>
    function doValidate() {
      console.log('Validating...');
      try {
        pw = document.getElementById('pass').value;
        lg = document.getElementById('log').value;
        console.log("Validating pw="+pw);
        console.log("Validating lg="+lg);
        if (pw == null || pw == "" || lg == null || lg == "") {
          alert("Username and Password fields must be completed in order to log in");
          return false;
        }
        return true;
      } catch(e) {
        return false;
      }
      return false;
    }
  </script>
</head>
<body>
  <div id="container">
    <nav>
      <div id="nav_content">
        <ul>
          <li id="nav_logo"><img src="img/circle_logo.png" alt="circle_logo"></li>
          <li id="nav_home">Timeline</li>
          <?php
            if(isset($_SESSION["success"])) {
              echo('<div id="welcome">
                      <li id="nav_username">Hello, '.$_SESSION["name"].'</li>
                      <form method="post">
                        <input id="login" type="submit" value="Log out" name="logout">
                      </form>
                    </div>');
            }
            else {
              echo('<li id="nav_login">
                      <form method="post">
                        <div id="enter_username">
                          <input id="log" type="text" placeholder="Username" name="username"></input>
                        </div>
                        <div id="enter_password">
                          <input id="pass" type="password" placeholder="Password" name="password"></input>
                        </div>
                        <input id="login" type="submit" onclick="return doValidate();" value="Log in" name="login">
                      </form>
                    </li>');
            }
            if ( isset($_SESSION["login_error"]) ) {
                echo('<div id="login_error"><p  style="color:red">'.$_SESSION["login_error"]."</p></div>\n");
                unset($_SESSION["login_error"]);
            }
          ?>
        </ul>
      </div>
    </nav>
    <div id="content">
      <div id="user_info" class="content_border">
        <?php
              if(isset($_SESSION["success"])) {
                echo('<img id="user_info_twitter_background" src="img/twitter_blue.png" alt="twitter_blue_background">
                      <div id="user_info_name">
                        <div id="profile_pic">
                          <img src="img/orange_egg.png" alt="profile_pic">
                        </div>
                        <div id="username_info">
                          <h3>'.$_SESSION["name"].'</h3>
                          <h4>@'.$_SESSION["username"].'</h4>
                        </div>
                      </div>
                      <div id="user_info_stats">
                      </div>');
              } else {
                echo('<form method="post">
                        <div id="signup">
                          <div id="signup_text">
                            <h2>Create your account</h2>
                          </div>
                          <div id="signup_name">
                            <input type="text" placeholder="Name" name="signup_name"></input>
                          </div>
                          <div id="signup_username">
                            <input type="text" placeholder="Username" name="signup_username"></input>
                          </div>
                          <div id="signup_email">
                            <input type="text" placeholder="Email" name="signup_email"></input>
                          </div>
                          <div id="signup_password">
                            <input type="password" placeholder="Password" name="signup_password"></input>
                          </div>
                          <div id="signup_button">
                            <input id="login" type="submit" value="Sign up" name=signup>
                          </input>
                          </div>
                        </div>
                      </form>');
            }
            ?>
      </div>
      <div id="timeline" class="content_border">
        <div id="tweet_creation_content" class="bottom_border">
          <form method="post">
          <div id="tweet_creation">
            <div id="tweet_picture">
              <img src="img/orange_egg.png" alt="tweet_pic">
            </div>
            <form method="post">
              <div id="tweet_text_box">
                <textarea rows="5" cols="50" maxlength="280" placeholder="What's happening?" autofocus name="tweet"></textarea>
              </div>
              <div id="tweet_button">
                <input type="submit" value="Tweet" name="tweet_submit">
              </div>
              <?php
                if ( isset($_SESSION["tweet_error"]) ) {
                    echo('<div id="login_error"><p  style="color:red">'.$_SESSION["tweet_error"]."</p></div>\n");
                    unset($_SESSION["tweet_error"]);
                }
              ?>
            </form>
          </div>
        </form>
        </div>
        <?php
          $tweets = $pdo->query("SELECT Messages.message, Messages.message_id, Users.name, Users.username, Users.user_id FROM Twitter.messages, Twitter.users
                             WHERE Twitter.messages.user_id = Twitter.users.user_id ORDER BY message_id DESC");
          $tweet_rows = $tweets->fetchAll(PDO::FETCH_ASSOC);


          foreach( $tweet_rows as $tweet) {
            $likes = $pdo->query("SELECT * FROM Twitter.messages, Twitter.messagelikes
                                WHERE Twitter.Messagelikes.message_id = Twitter.Messages.message_id AND Twitter.Messages.message_id=$tweet[message_id]");
            $count=$likes->rowCount();
            echo('<div class="user_tweets">
              <div class="user_tweet_pic">
                <img src="img/orange_egg.png" alt="tweet_pic">
              </div>
              <div class="user_tweet_content">
                <div class="user_tweet_name">
                  <h3 id="name">'.$tweet['name'].'</h3>
                  <h3 id="username">@'.$tweet['username'].'</h3>
                </div>
                <div class="tweet_message">
                  <p>'.$tweet['message'].'</p>
                </div>
                <div class="tweet_details">
                  <form method="post">
                    <button value='.$tweet['message_id'].' name="like">'.
                      $count
                    .'</button>
                  </form>
                </div>
              </div>
            </div>');
          }
        ?>
      </div>
    </div>
  </div>
</body>
