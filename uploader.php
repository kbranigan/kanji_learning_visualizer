<?php

if (isset($_FILES['userfile']))
{
  $pwd = dirname(__FILE__).'/';//"/Users/kevin/work/branigan.ca/prototypes/kanji_progress/";
  $uploaddir = $pwd . "upload_files/";

  $bname = md5_file($_FILES['userfile']['tmp_name']) . filesize($_FILES['userfile']['tmp_name']);
  $uploadfile = $uploaddir . $bname . ".anki2";

  if (file_exists($uploadfile))
  {
    echo "File previously uploaded.<br /><br />\n";
  }
  else if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile))
  {
    echo "File is valid, and was successfully uploaded.<br />\n";
  }

  $generatedfile = $uploaddir . $bname . ".js";

  // if (!is_file($generatedfile))
  {
    $descriptorspec = array(
      0 => array("pipe", "r"),  // stdin
      1 => array("pipe", "w"),  // stdout
      2 => array("pipe", "w"),  // stderr
    );

    $process = proc_open($pwd."kanji_lister $uploadfile", $descriptorspec, $pipes, $pwd, null);

    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    proc_close($process);

    if (!empty($stderr))
    {
      print "There was an error. (of course)<br />\n";
      print "<b>$stderr</b><br /><br />\n";
      print "I've solved all my errors but I haven't tried much with other people's databases, so this was always highly likely.<br />\n";
      print "Send me an email or message and I'll take a look.<br />\n";
      if (is_file($uploadfile.".msg.txt") && filesize($uploadfile.".msg.txt"))
      {
        print "<br />\"Fun Stuff\":<br /><pre>";
        readfile($uploadfile.".msg.txt");
      }
      die();
    }
    else if (empty($stdout))
    {
      print "There was no error but also to data. (thats double bad)<br />\n";
      print "<b>SORRY</b><br />\n";
      if (is_file($uploadfile.".msg.txt") && filesize($uploadfile.".msg.txt"))
      {
        print "<br />\"Fun Stuff\":<br /><pre>";
        readfile($uploadfile.".msg.txt");
      }
      die();
    }
    else
    {
      file_put_contents($generatedfile, $stdout);
    }

    if (filesize($generatedfile) < 50000)
    {
      print "<b>Generated file is pretty small ... I kinda doubt it worked but check anyway!</b><br />\n";
    }

    if (is_file($uploadfile.".msg.txt") && filesize($uploadfile.".msg.txt"))
    {
      print "<br />\"Fun Stuff\":<br /><pre>";
      readfile($uploadfile.".msg.txt");
    }
  }

  print "<br /><br /><b><a href='./?file=" . $bname . "'>View Results</a></b><br />\n";

  die();
}

?>
<head>
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-351485-5"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-351485-5');
</script>
<style>
        .myButton {
    -moz-box-shadow:inset 0px 1px 0px 0px #caefab;
    -webkit-box-shadow:inset 0px 1px 0px 0px #caefab;
    box-shadow:inset 0px 1px 0px 0px #caefab;
    background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #77d42a), color-stop(1, #5cb811));
    background:-moz-linear-gradient(top, #77d42a 5%, #5cb811 100%);
    background:-webkit-linear-gradient(top, #77d42a 5%, #5cb811 100%);
    background:-o-linear-gradient(top, #77d42a 5%, #5cb811 100%);
    background:-ms-linear-gradient(top, #77d42a 5%, #5cb811 100%);
    background:linear-gradient(to bottom, #77d42a 5%, #5cb811 100%);
    filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#77d42a', endColorstr='#5cb811',GradientType=0);
    background-color:#77d42a;
    -moz-border-radius:6px;
    -webkit-border-radius:6px;
    border-radius:6px;
    border:1px solid #268a16;
    display:inline-block;
    cursor:pointer;
    color:#306108;
    font-family:Arial;
    font-size:15px;
    font-weight:bold;
    padding:6px 24px;
    text-decoration:none;
    text-shadow:0px 1px 0px #aade7c;
}
.myButton:hover {
    background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #5cb811), color-stop(1, #77d42a));
    background:-moz-linear-gradient(top, #5cb811 5%, #77d42a 100%);
    background:-webkit-linear-gradient(top, #5cb811 5%, #77d42a 100%);
    background:-o-linear-gradient(top, #5cb811 5%, #77d42a 100%);
    background:-ms-linear-gradient(top, #5cb811 5%, #77d42a 100%);
    background:linear-gradient(to bottom, #5cb811 5%, #77d42a 100%);
    filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#5cb811', endColorstr='#77d42a',GradientType=0);
    background-color:#5cb811;
}
.myButton:active {
    position:relative;
    top:1px;
}
</style>
</head>
<center>
<div style='text-align: center;'>
<form enctype="multipart/form-data" method="POST">
  <a href="https://ankiweb.net">Anki</a> has a sqlite database detailing of all your reviews.<br />
  If you are learning Kanji and are reviewing a deck like <a href='https://ankiweb.net/shared/info/798002504'>this</a>,<br />
  then you can upload it here and see your progress visually.<br />
  It feels good to be proud of your effort, that's what I heard.<br />
  <br />
  <br />
  <input type="hidden" name="MAX_FILE_SIZE" value="50000000" />
  <input type="file" id="file" name="userfile" /><br /><br />
  <input type="submit" class="myButton" value='Upload Anki Database' /><br /><br /><br />
  <br />
  <br />
  Seemingly in all operating systems, this file sits buried in a folder that I would have a fairly hard time guessing.<br />
  It seems that on some systems the .anki2 file is a zip file.  Currently I don't support this but I hope to add support soon.<br />
  You could also unzip the file and send the .anki2 file that is inside.<br />
  <br />
  For me (Latest OSX), that's: <b>/Users/kevin/Library/Application Support/Anki2/Kevin/collection.anki2</b><br />
  and (Windows 8.1), that's: <b>C:\Users\kevin\Documents\Anki2\Kevin\collection.anki2</b><br />
  <br />
  I'm limiting the file upload to 50MB because mine is 30MB - if you need more, I donno man, send me an email about it.<br />
</form>
</div>
</center>
