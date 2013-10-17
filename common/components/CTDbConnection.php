<?php if (!defined('YII_PATH')) exit('No direct script access allowed!');
/**
 * CTDbConnection class
 *
 * @author Ramin Farmani <ramin.farmani@gmail.com>
 * @link http://www.picontest.com/
 * @copyright Copyright &copy; 2013
 * @license http://www.picontest.com/license/
 */
class CTDbConnection extends CDbConnection
{
    /**
     * @var array the slaves for establishing DB connection. Defaults to empty array.
     */
    public $slaves=array();

    /**
     * @var array the enableSlave to enable slaves db or not DB connection. Defaults to false.
     */
    public $enableSlave=false;

}
