<?php

require_once "ConstantsPdf.php";

class FacturaAndroidZpl {

        private $vars;

        function __construct($vars = []) {
                $this->vars = $vars;
        }
    
        public function setVars($vars){
		$this->vars = $vars;
	}
	
	public function makeFacturaAndroid(){
                $cds_title = "DSC EXPRES LOGISTIC S.R.L.";
                $cds_adresa = "Calea Bucuresti nr. 1, Otopeni, Ilfov 075100".PHP_EOL
                        ."O.R.C.: J23 /402 /2016 - C.U.I.: RO29255819".PHP_EOL
                        ."TEL.: 021-9501, comenzi@curierdragonstar.ro";
                
                return "Factura fiscala " . PHP_EOL . $this->vars['serie'];
	}
	
}