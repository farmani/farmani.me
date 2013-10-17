<?php if (!defined('YII_PATH')) exit('No direct script access allowed!');
/**
 * CTLightOpenID.php class
 *
 * @author Ramin Farmani <ramin.farmani@gmail.com>
 * @link http://www.thankyoumenu.com/
 * @copyright Copyright &copy; 2013
 * @license http://www.thankyoumenu.com/license/
 */
class CTLightOpenID extends CApplicationComponent
{
    /**
     * Language name in 'en_EN' format
     * @var string
     */
    private $_language;

    /**
     * init function
     */
    public function init()
    {
        parent::init();
        Yii::import('vendors.lightopenid.LightOpenID');
    }

    /**
     * Main extension loader
     * @param array $config - configuration array
     * @return LightOpenID
     */
    public function load($config = array())
    {
        $openid = new LightOpenID('http://www.thankyoumenu.com');
        if (!empty($config)) {
            foreach ($config as $key => $val) {
                $openid->$key = $val;
            }
        }
        return $openid;
    }
}