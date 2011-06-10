<?php

// This is an example of a script that can be used to receive the config generated by
// NConf, and then deployed by HTTP(s)-POST to a remote server.

$targetdir  = "/var/www/NConf_dev_new";
$exec_command    = "service restart nagios";
//$exec_command    = "/etc/init.d/sshd status";
$tar      = 'tar';
$gunzip   = 'gunzip -f';

$execute = ( !empty($_POST["remote_execute"]) ) ? $_POST["remote_execute"] : '';
$action  = ( !empty($_POST["remote_action"])  ) ? $_POST["remote_action"]  : '';

// Look for upload error
if ($_FILES['file']['error'] === UPLOAD_ERR_OK){
    // upload ok
}else{
    echo file_upload_error_message($_FILES['file']['error']);
}


if(is_uploaded_file($_FILES["file"]["tmp_name"])) {

    // do more with the file here
    if ( empty($action) ){
        echo '<b>Error</b> Action is not defined, read documentation for details.';
    }elseif ($action == "extract"){
        $target_file_tgz = $targetdir.basename($_FILES["file"]["name"]);
        $target_file_tar = $targetdir.basename($_FILES["file"]["name"], ".tgz").'.tar';

        // copy
        $status = copy($_FILES["file"]["tmp_name"], $target_file_tgz);
		if (!$status){
			echo '<b>PHP copy</b> temporary copy('.$_FILES["file"]["name"].', '.$target_file_tgz.')';
		}
        // gunzip
        $command = $gunzip.' '.$target_file_tgz;
        $status = exec(escapeshellcmd($command), $output, $retval);

        // tar
        $tar_command = $tar;
        if (!empty($_FILES["options"]) ){
            $tar_command .= ' '.$_FILES["options"];
        }else{
            $tar_command .= ' -xf';
        }
        $tar_command .= ' '.$target_file_tar;
        $tar_command .= ' -C '.$targetdir;

        exec(escapeshellcmd($tar_command), $output, $retval);
        if ($retval == 0){
            //echo "successfully extracted files";
        }else{
            echo "upload to remote server failed";
			echo '<br>'.$tar_command;
			var_dump($output);
        }

        // remove gunzip'ed file
        $status_unlink = unlink($target_file_tar);
		if (!$status_unlink){
				echo '<b>PHP unlink</b> remove temporary file('.$target_file_tar.')';
		}

    }elseif ($action == "copy"){
        if (!empty($_FILES["file"]["tmp_name"]) && !empty($targetdir)){

            // copy single file
            $status = copy($_FILES["file"]["tmp_name"], $targetdir."/".$_FILES["file"]["name"]);
			if (!$status){
            	echo '<b>PHP copy</b> copy('.$_FILES["file"]["name"].', '.$targetdir.')';
	    	}
        }else{
            echo "upload to remote server failed";
            echo "<br>".$targetdir."/".$_FILES["file"]["name"];
        }

    }elseif ($action == "move"){
        if (!empty($_FILES["file"]["tmp_name"]) && !empty($targetdir)){

            // rename file or directory
            $status = rename($_FILES["file"]["tmp_name"], $targetdir."/".$_FILES["file"]["name"]);
	    	if (!$status){
            	echo '<b>PHP rename</b> rename('.$_FILES["file"]["tmp_name"].', '.$targetdir.')';
	    	}
        }else{
            echo "upload to remote server failed";
            echo "<br>".$targetdir."/".$_FILES["file"]["name"];
        }
    }else{
		echo 'Unknown "remote_action" ('.$action.'), please read documentationaction.';
	}
    if ($execute == 1 OR $execute == "TRUE"){
        exec(escapeshellcmd($exec_command), $output, $retval);
        if($retval == 0){
            //echo "<br>restarted Nagios";
        }else{
            echo "failed executing command:<br>";
			echo $exec_command;
			echo "<br>";
			if ( !empty($output) ){
				var_dump($output);
			}
        }
    }

    // give feedback
    if ($status == true){
        echo "OK";
    }else{
	echo $status;
    }

   
}else{
    echo "trying to send invalid file";
}





// file error function

function file_upload_error_message($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
        case UPLOAD_ERR_FORM_SIZE:
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
        case UPLOAD_ERR_PARTIAL:
            return 'The uploaded file was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing a temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'File upload stopped by extension';
        default:
            return 'Unknown upload error';
    }
}






?>