<?php if (!defined('YII_PATH')) exit('No direct script access allowed!');
/**
 * CTBaseActiveRecord class
 *
 * @author Ramin Farmani <ramin.farmani@gmail.com>
 * @link http://www.thankyoumenu.com/
 * @copyright Copyright &copy; 2013
 * @license http://www.thankyoumenu.com/license/
 */
class CTBaseActiveRecord extends EActiveRecord
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
    public $slaves = array();

    /**
     * @var CDbConnection
     */
    private $_slave;

    private static $_events = array();

    /**
     * Attach exists events while model creation
     */
    public function init()
    {
        $this->attachEvents($this->events());

        $this->setSlave();

        // When OnBeforeXXX event triggered Master db
        $this->attachEventHandler('OnBeforeSave', array($this, 'switchToMaster'));
        $this->attachEventHandler('OnBeforeDelete', array($this, 'switchToMaster'));

        $this->attachEventHandler('OnAfterDelete', array($this, 'switchToSlave'));
        $this->attachEventHandler('OnAfterSave', array($this, 'switchToSlave'));

        parent::init();
    }

	public function behaviors()
	{
		return array(
			'CTimestampBehavior' => array(
				'class' => 'CTTimestampBehavior',
				'createAttribute' => 'created_at',
				'updateAttribute' => 'updated_at',
				'deleteAttribute' => 'deleted_at',
				'softDelete' => true,
				'setUpdateOnCreate' => true,
				'timestampExpression' => new CDbExpression('UTC_TIMESTAMP()'),
			)
		);
	}

    public function switchToMaster()
    {
        self::$db = Yii::app()->db;
        return true;
    }

    public function switchToSlave()
    {
        if (Yii::app()->db->enableSlave)
            self::$db = $this->getSlave();
        return true;
    }

    /**
     * return slave connection CDbConnection for read operation.
     * @return CDbConnection
     * */
    public function getSlave()
    {
        if (!isset($this->_slave)) {
            $this->setSlave();
        }
        return $this->_slave;
    }

    /**
     * Construct a slave connection CDbConnection for read operation.
     * @return CDbConnection
     */
    public function setSlave()
    {
        $randomSlave = rand(1, 100);
        $selectSlave = 0;
        foreach ($this->slaves as $slaveConfig) {
            if (($slaveConfig['weight'] + $selectSlave) >= $randomSlave) {
                if (!isset($slaveConfig['class']))
                    $slaveConfig['class'] = 'CTDbConnection';
                try {
                    if ($slave = Yii::createComponent($slaveConfig)) {
                        Yii::app()->setComponent('dbslave', $slave);
                        $this->_slave = $slave;
                        break;
                    }
                } catch (Exception $e) {
                    Yii::log('Create slave database connection failed!', 'warn');
                    $selectSlave += $slaveConfig['weight'];
                    continue;
                }
            } else
                $selectSlave += $slaveConfig['weight'];

        }
        if (!$this->_slave) {
            $this->_slave = $this;
            Yii::app()->db->enableSlave = false;
        }
        return $this->_slave;
    }


    public function afterSave()
    {
        $modelName = ucfirst($this->model()->tableName());

        Yii::app()->cache->set(CTCacheDependency::buildCacheId($modelName), time());

        /*
        $modelMethods = get_class_methods($modelName);
        foreach ($modelMethods as $methodName) {
            $key = 'm:' . $modelName . ':' . $methodName . ':' . $this->id;
            Yii::app()->cache->delete($key);
        }
        */
    }

    /**
     * Attach events
     *
     * @param array $events
     */
    public function attachEvents($events)
    {
        foreach ($events as $event) {
            if ($event['component'] == get_class($this))
                parent::attachEventHandler($event['name'], $event['handler']);
        }
    }

    /**
     * Get exists events
     *
     * @return array
     */
    public function events()
    {
        return self::$_events;
    }

    /**
     * Attach event handler
     *
     * @param string $name Event name
     * @param mixed $handler Event handler
     */
    public function attachEventHandler($name,$handler)
    {
        self::$_events[] = array(
            'component' => get_class($this),
            'name' => $name,
            'handler' => $handler
        );
        parent::attachEventHandler($name, $handler);
    }

    /**
     * Detach event handler
     *
     * @param string $name Event name
     * @param mixed $handler Event handler
     * @return bool
     */
    public function detachEventHandler($name,$handler)
    {
        foreach (self::$_events as $index => $event) {
            if ($event['name'] == $name && $event['handler'] == $handler)
                unset(self::$_events[$index]);
        }
        return parent::detachEventHandler($name, $handler);
    }

	public function defaultScope()
	{
		return array(
			'condition'=>$this->getTableAlias(true,false).'.`deleted_at` IS NULL',
		);
	}
}