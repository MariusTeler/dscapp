<?php

/**
 * W o r k s p a c e
 *
 */
class ModulConfirmari extends BackEnd {

    public $final_result;
    public $action_module;
    public $page_prefix;
    public $table;

    /**
     * The constructor for the 'Workspace' class
     * Calls BackEnd constructor
     * Cals Actions function
     *
     * @param array $config  an array with the configuration params read from '/_lib/config/config.php'
     * @param integer $act  (0/1) Specifies if actions are alowed or not
     * @access public
     * @see Actions()
     */
    function __construct($config = 0, $act = 1, $db = 0) {
        parent :: __construct($config, $db);

        $this->vars['title_page'] = 'Confirmari';
        $this->page_prefix = 'confirmari_';

        if ($act) {
            //ACTIONS
            $this->Actions();
        }
    }

//-------------------- a c t i o n s  d e c i s i o n s ------------------------------------------------------------

    /**
     * A c t i o n s
     * Choosing what actions, depending on the profile (if administrator is not loged in - 0, or is loged in - 9), to take.
     *
     * @param string $msg  A message that can be displayed
     * @access public
     */
    function Actions() {
        $this->final_result = '';

        // A D M I N
        if (isset($_GET['logout']))
            $this->Logout();

        else if (!empty($this->user_profile))
        {
            $this->ActionsNivelAcces();   
        } 
        else
            $this->final_result = $this->PageNotFound();
        // R E S U L T
        return $this->final_result;
    }

   
    function ActionsNivelAcces() {
		$this->user_rights = $this->GetDrepturiUtilizator($this->user_profile);
        $arr = $this->GenerateArr();
        //nivel acces 10        
		$flag = 0;
			if ((in_array("confirmare", $this->user_rights) && isset($arr[1]) && $arr[1] == 'confirmare') || $this->user_profile == 10){
				$this->final_result = $this->Confirmare();
				$flag=1;
			}	
        if(empty($flag))
            $this->final_result = $this->PageNotFound();
    }
    
 //-------------------------------- functii ----------------------------------------
   
/*/////////////////////////////////////////////////////////////
				 CONFIRMARI
/////////////////////////////////////////////////////////////*/
    function Confirmare(){
		$this->vars['title_page'] = 'Confirmari';
        return $this->Parse($this->page_prefix . 'confirmari.html', []);
	}
//end class
}
?>
