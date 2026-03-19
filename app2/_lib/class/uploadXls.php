<?php


/** 
 * U p l o a d 
 * Upload file to a custom directory & 
 * Validate file size, type 
 * 
 *
 * @author  BoSoWeb <bosoweb@gmail.com>
 * @version 1.0
 * @since 1.0
 * @access public
 
 * @package file_upload
 * 
 */
		
class UploadXls {

	/**
     * Maximum file size
     * @var integer
     * @access public
     */
	const MAX_FILE_SIZE = 5242880; //5 Mb
	const SAVE_PATH = '/var/www/upload/files/'; // The path were we will save the file (getcwd() may not be reliable and should be tested in your environment)

	/**
     * Allowed file types
     * @var integer
     * @access public
     */
	const ALLOWED_FILE_TYPES = 'xls,xlsx,csv,xlsm';
	const MAX_FILENAME_LENGTH = 150;
	const UPLOAD_FILE_NAME = 'fxls';
	const VALID_CHARS_REGEX = 'A-Za-z0-9_\-\.';// Characters allowed in the file name (in a Regular Expression format)
				
	static function uploadXlsUp($file_var = 'fxls', $isCsv = false) {// 
		// Allowed file extensions
		if($isCsv) $whitelist = array('csv');
		else $whitelist = array('xls', 'xlsx','xlsm');
		
    	$blacklist = array('php', 'php3', 'php4', 'php5', 'phtml','exe','bat','sh','csh'); // Restrict file extensions
    	
		$ret = [];
		$ret[] = false;
		
		//Check post_max_size (http://us3.php.net/manual/en/features.file-upload.php#73762)
    	$POST_MAX_SIZE = ini_get('post_max_size');
    	$unit = strtoupper(substr($POST_MAX_SIZE, -1));
    	$multiplier = ($unit == 'M' ? 1048576 : ($unit == 'K' ? 1024 : ($unit == 'G' ? 1073741824 : 1)));
 
    	if ((int)$_SERVER['CONTENT_LENGTH'] > $multiplier*(int)$POST_MAX_SIZE && $POST_MAX_SIZE) 
        	{ $ret[] = 'POST exceeded maximum allowed size.'; return $ret; }
          
		// Other variables
    	$file_name = '';
    	$uploadErrors = array(
        	0=>'There is no error, the file uploaded with success',
        	1=>'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        	2=>'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        	3=>'The uploaded file was only partially uploaded',
        	4=>'No file was uploaded',
        	6=>'Missing a temporary folder'
    	);
 
		// Validate the upload
    	if (!isset($_FILES[$file_var])) 
        	{ $ret[] = 'No upload found'; return $ret; }
    	else if (isset($_FILES[$file_var]['error']) && $_FILES[$file_var]['error'] != 0) 
        	{ $ret[] = $uploadErrors[$_FILES[$file_var]['error']]; return $ret; }
    	else if (!isset($_FILES[$file_var]['tmp_name']) || !is_uploaded_file($_FILES[$file_var]['tmp_name'])) 
        	{ $ret[] = 'Upload failed'; return $ret; }
    	else if (!isset($_FILES[$file_var]['name']))
        	{ $ret[] = 'File has no name'; return $ret; }
 
		// Validate the file size (Warning: the largest files supported by this code is 5MB)
    	$file_size = @filesize($_FILES[$file_var]['tmp_name']);
    	if (!$file_size || $file_size > self::MAX_FILE_SIZE)
        	{ $ret[] = 'File exceeds the maximum allowed size'; return $ret; }
     
    	if ($file_size <= 0)
        	{ $ret[] = 'File size outside allowed lower bound'; return $ret; }
		
		// Validate file name (for our purposes we'll just remove invalid characters)
    	$file_name = preg_replace('/[^'.self::VALID_CHARS_REGEX.']$/i', '_', strtolower(basename($_FILES[$file_var]['name'])));
    	if (strlen($file_name) == 0 || strlen($file_name) > self::MAX_FILENAME_LENGTH)
        	{ $ret[] = 'Invalid file name'; return $ret; }
        	
        // Validate file extension
        $upload_extension = explode('.', $file_name);
        $upload_extension = end($upload_extension);
    	if(!in_array($upload_extension, $whitelist))
        	{ $ret[] = 'Invalid file extension :'.$upload_extension; return $ret; }
    	else if(in_array($upload_extension, $blacklist))
        	{ $ret[] = 'Invalid file extension'; return $ret; }
 
		// Validate that we won't over-write an existing file
    	if (file_exists(self::SAVE_PATH . $file_name))
        	{ $ret[] = 'File with this name already exists'; return $ret; }
 
		// Rename the file to be saved    
    	$file_name = md5(uniqid('', true)) . '.' . $upload_extension;
     
		// Verify! Upload the file
    	if (!move_uploaded_file($_FILES[$file_var]['tmp_name'], self::SAVE_PATH.$file_name))
        	{ $ret[] = 'File could not be saved.'; return $ret; }
        
		//chmod(self::SAVE_PATH.$file_name, 0644);

        $ret[0] = true;
        $ret[] = self::SAVE_PATH.$file_name;
        return $ret;
    }
    
    /**
    Constructs the SSE data format and flushes that data to the client.
	*/
	static function send_message($id, $message, $progress, $stop = 0) 
	{
    	$d = array('message' => $message , 'progress' => $progress, 'stop' => $stop);
     
    	echo "id: $id" . PHP_EOL;
    	echo "data: " . json_encode($d) . PHP_EOL;
    	echo PHP_EOL;
     
    	//PUSH THE data out by all FORCE POSSIBLE
    	ob_flush();
    	flush();
	}

    /**
     * D i r
	 * Scope 1: Verify if $dir exists
	 * Scope 2: Create a directory to the specified path (& chmod to 777)
     *
     * @return full server path
     * @param string $dir  Directory
     * @access public
     */
	static function Dir($dir){
	    // get full path on server
		//$dir = preg_replace("/(.*)(\/)$/","\\1", $dir);
		//$dir = $_SERVER['DOCUMENT_ROOT'] . '' . $dir;
		
		// if $dir doesn't exists create a new directory to that location
		if(!is_dir($dir)){
			@mkdir($dir, 0777);
			@chmod($dir, 0777);
		}
		
		// return full server path
		return $dir;
	} 

 	/**
	 * U n i q
  	 * Return a unique code based on curent unix time in microsec plus a random 6 digit number
	 *
	 * @return integer 
	 * @access public
	 */
	static function Uniq(){
		return md5(uniqid('', true));
	}


    /**
     * D e l e t e
	 * Deletes the specified file from server
     *
     * @return boolean
     * @param string $dir  Directory
     * @param string $file  Filename
     * @access  public
     */
	static function Delete($dir, $file){
	    if(!is_dir($dir)) return 0;
		$file = $dir . '/' . $file;
		
		if( (file_exists($file)) && (@unlink($file)) ) return 1;
		return 0;
	}

	
    /**
     * R e n a m e
	 * Rename $file on server with $newname
	 *
     * @param $dir  Directory for file on server
	 * @param $file  Current file on server
	 * @param $newname  New file name
     * @access  public
     */
	static function Rename($dir, $file, $newname){
	    $dir = self::Dir($dir);
		if(rename($dir . '/' . $file, $dir . '/' . $newname)) return 1;
	    return 0;
	}


} //end class
?>