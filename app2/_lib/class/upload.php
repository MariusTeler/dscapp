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
		
class Upload {

	/**
     * Maximum file size
     * @var integer
     * @access public
     */
	public $max_file_size = 1572864; //1,5 Mb
	const SAVE_PATH = '/var/www/upload/files/';

	/**
     * Allowed file types
     * @var integer
     * @access public
     */
	public $allowed_file_types = 'xlsx,xls,xlsm,csv';

	/**
     * Array with uploaded params: client_file_name, server_file_name, server_file_dir, file_size, file_date
     * @var integer
     * @access public
     */
	public $file;
					
	/**  
 	 * The constructor for the 'Upload' class 
	 *
	 * @access public
	 */
	 function __construct(){
	 	// initiate uploaded file details
	 	$this -> file['client_file_name'] 	= '';   // client file name
	 	$this -> file['server_file_name'] 	= '';	// server file name
	 	$this -> file['server_file_dir'] 	= '';	// server file name + path (starting form root dir)
	 	$this -> file['file_size'] 			= '';	// file size 
	 	$this -> file['file_date'] 			= '';	// upload date & time
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
	function Dir($dir){
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
	 * U p l o a d F i l e
	 * Uploads all files from $_FILES (files from form) to $dir on server
	 * 
	 * @return error message
	 * @param string $file_var  The name of the FILE Field from your form
	 * @param string $dir  Directory where to place the uploaded files
	 * @param boolean $uniq  If is set to 1 then each file will receive a unique name (and keep the extension)
	 * @access public
	 */ 	
	function UploadFile($file_var, $dir, $uniq = 1){
		$dir2 = $dir;
	    $dir = $this -> Dir($dir); 
		
		// verify if a file by that name has been uploaded
	    if(!is_array($_FILES[$file_var])) 
			return 'Error: Inexistent file field "' . $file_var . '" in your form ...';
			
		$file = $_FILES[$file_var];
		
		// verify if filename exists
	    if(empty($file['name'])) 
			return 'Error: Inexistent filename...';
		
		// verify if file has been uploaded on server
		if(!is_uploaded_file($file['tmp_name'])) 
			return 'Error: Could not write on server in the tmp directory...';
					
		//validate file SIZE			
		if ($file['size'] > $this -> max_file_size) 
			return 'Error: The maximum uploaded file size allowed is ' . $this -> max_file_size . '. (your file size is ' . $file['size'] . ')';
					
		// file extension (filetype)
		$nn = $file['name'];
		$narr = explode('.', $nn);
		$earr = end($narr);
		$ext = strtolower($earr);

		// validate file TYPE
		if(!in_array($ext, explode(',', $this -> allowed_file_types)))
			return 'Error: File type not allowed...';
			
		// file NAME
		$filename = basename($file['name']);
		if($uniq) $filename = $this -> Uniq() . '.' . $ext;
					
		// move file to specified location
		if(!@move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) 
			return 'Error: File could not be uploaded on specific location on server...';
		chmod($dir . '/' . $filename, 0644);
			
		// update uploaded file details
	 	$this -> file['client_file_name'] 	= $file['name'];     	// client file name
	 	$this -> file['server_file_name'] 	= $filename;			// server file name
	 	$this -> file['server_file_dir'] 	= $dir2;				// server file name + path (starting form root dir)
	 	$this -> file['file_size'] 			= $file['size'];		// file size 
	 	$this -> file['file_date'] 			= date("Y-m-d H:i:s");	// upload date & time
		
		// return no errors
		return 0;
	}


 	/**
	 * U n i q
  	 * Return a unique code based on curent unix time in microsec plus a random 6 digit number
	 *
	 * @return integer 
	 * @access public
	 */
	function Uniq(){
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
	function Delete($dir, $file){
	    $dir = $this -> Dir($dir);
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
	function Rename($dir, $file, $newname){
	    $dir = $this -> Dir($dir);
		if(rename($dir . '/' . $file, $dir . '/' . $newname)) return 1;
	    return 0;
	}


}