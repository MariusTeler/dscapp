<?php
/**
 * Created by PhpStorm.
 * User: ionut
 * Date: 18/11/2016
 * Time: 12:28
 */

require_once "ConstantsPdf.php";
/**
 * W o r k s p a c e
 *
 */
class ModulHistory extends BackEnd
{
    public $final_result;
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
        $arr = $this->GenerateArr();
        $id = isset($arr[1]) ? $arr[1] : 0;
        if (isset($arr[2]) && $arr[2] == 'dsc.jpg'){
            return $this->showLogo(ConstantsPdf::DSC_PATH);
        } elseif (isset($arr[2]) && $arr[2] == 'dsc-stampila.png'){
            return $this->showLogo(ConstantsPdf::DSC_STAMPILA, 'png');
        } else {
            $im=imagecreate(1,1);
            $white=imagecolorallocate($im,255,255,255);
            imagesetpixel($im,1,1,$white);
            header("content-type:image/jpg");
            imagejpeg($im);
            imagedestroy($im);
            return;
        }

    }

    function showLogo($file, $ext = 'jpeg'){
        header('Content-type: image/'.$ext);
        echo  file_get_contents($file);
    }
    
}