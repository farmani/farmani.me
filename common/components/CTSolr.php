<?php if (!defined('YII_PATH')) exit('No direct script access allowed!');
/**
 * CTBaseActiveRecord class
 *
 * @author Ramin Farmani <ramin.farmani@gmail.com>
 * @link http://www.thankyoumenu.com/
 * @copyright Copyright &copy; 2013
 * @license http://www.thankyoumenu.com/license/
 */
class CTSolr extends CApplicationComponent
{

    /**
     * @var array $slaves.Slave database connection(Read) config array.
     * The array value's format is the same as CDbConnection.
     * @example
     * 'components'=>array(
     *         'db'=>array(
     *             'connectionString'=>'mysql://<master>',
     *             'slaves'=>array(
     *                 array('connectionString'=>'mysql://<slave01>','username'=>'root','password'=>'','weight' => 60),
     *                 array('connectionString'=>'mysql://<slave02>','username'=>'root','password'=>'','weight' => 40),
     *             )
     *         )
     * )
     * */
    public $clientOptions = array(
        'hostname'  => 'localhost',
        'port'      => 8080,
        'path'      => '/solr/core0',
        'login'     => 'solr',
        'passwrod'  => 'you@retheLORD',
        'wt'        => 'json',
        'timeout'   => 80
    );

    private $_client = null;

    /**
     * Initializes this application component.
     * This method is required by the {@link IApplicationComponent} interface.
     * It creates the redis instance and adds redis servers.
     * @throws CException if redis extension is not loaded
     */
    public function init()
    {

        parent::init();
        return self::getSolr();
        Yii::log('Loading "CTSolr" application component', CLogger::LEVEL_TRACE, 'components.CTSolr');
    }

    /**
     * @return mixed the solr instance
     */
    protected function getSolr()
    {
        if ($this->_client === null) {
            Yii::log('Opening Solr connection', CLogger::LEVEL_TRACE, 'CTSolr');

            $this->_client = new SolrClient($this->clientOptions);
        }
        return $this->_client;
    }
}