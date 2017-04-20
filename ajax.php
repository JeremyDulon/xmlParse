<?php

if (isset($_POST['action'])) {
  switch ($_POST['action']) {
    case 'getInfos':
      download();
      break;
  }
}

function download() {
  $xmlstr = file_get_contents($_POST['url']);

  echo $xmlstr;
}

?>
