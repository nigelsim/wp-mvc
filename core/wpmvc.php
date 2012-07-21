<?php

//Allow command via html form only for localhost.
//If you dev on a distant server you might want to add it here
$allow_html_cmd_for = array('localhost', '127.0.0.1');


$command = null;
if (isset($argv))
{ //Run from command line
  $command = $argv;
}
elseif (in_array($_SERVER['SERVER_ADDR'], $allow_html_cmd_for))
{ //No command line argument, use html form
  
  if (isset($_POST['cmd']))
  { //Process form command
    $command = array(__FILE__);
    $command = array_merge($command, explode(' ', $_POST['cmd']));
  }
  
  //Display html form
?>
  <h2>WP-MVC Command line</h2>
  <form action="" method="POST" name="command">
    wpmvc
    <input type="text" name="cmd" size="100" value="<?php echo isset($_POST['cmd']) ? $_POST['cmd'] : '' ?>"/>
    <button type="submit">Run command</button>
  </form>
  <script type="text/javascript">
    //Make the input field behave like a prompt
    input = document.forms['command'].elements['cmd'];
    input.focus();
    input.value = input.value;
  </script>
<?php
}

if ($command !== null)
{ //Run command
  $wordpress_path = getenv('WPMVC_WORDPRESS_PATH');
  $wordpress_path = $wordpress_path ? rtrim($wordpress_path, '/').'/' : dirname(__FILE__).'/../../../../';

  ob_start();
  require_once $wordpress_path.'wp-load.php';
  $shell = new MvcShellDispatcher($command);
  
  $lines = ob_get_contents();
  ob_end_clean();

  if (isset($argv))
    echo $lines."\n";
  else
    echo nl2br(preg_replace('/\[[0-9,;]*m/U', '', $lines));
}
?>