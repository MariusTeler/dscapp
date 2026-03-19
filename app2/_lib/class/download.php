<?php


/** 
 * Download
 * Download file from a custom directory & 
 * 
 * 
 */
		
class Download {

	private $db;
	private $filename;
	private $filepath;
					
	/**  
 	 * The constructor for the 'Download' class 
	 *
	 * @access public
	 */
	 function __construct($db, $expeditie, $tip = 0) {
	 	// initiate
	 	$this->db = $db;
	 	
	 	//search nt
	 	$query = "select expeditie, folder from exp_confirmari where expeditie=".intval($expeditie);
		if($tip == 1) {
			$query = "select expeditie, folder, tStamp from exp_recantarite where id=".intval($expeditie);
			//error_log($query);
		}
		$sql = $this->db->QFetchRowAssoc($query);
		if(empty($sql) || !is_array($sql)) die("File does not exist.");
		
		$this->filepath = $sql['folder'];
		$this->filename = "{$sql['expeditie']}.jpg";
		if($tip == 1) {
			$this->filename = "{$sql['expeditie']}_{$sql['tStamp']}.jpg";
		}
		
		if (!is_file($this->filepath."/".$this->filename)) {
  			die("File does not exist."); 
		}

		// http headers for downloads
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		header("Content-type: image/jpeg");
		header("Content-Transfer-Encoding: binary");
		header("Content-Disposition: attachment; filename=\"".$this->filename."\"");
		header("Content-Length: ".filesize($this->filepath."/".$this->filename));
	 }
	 
	function sendFile(){
		ob_end_flush();
		@readfile($this->filepath."/".$this->filename);
		flush();
	}


} //end class
?>