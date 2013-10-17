<?php
/**
 * CTTimestampBehavior.php class
 *
 * @author Ramin Farmani <ramin.farmani@gmail.com>
 * @link http://www.farmani.ir/
 * @copyright Copyright &copy; 2013
 */
 /**
  * CTTimestampBehavior will automatically fill date and time related attributes.
  *
  * CTTimestampBehavior will automatically fill date and time related attributes when the active record
  * is created and/or updated.
  * You may specify an active record model to use this behavior like so:
  * <pre>
  * public function behaviors(){
  * 	return array(
  * 		'CTTimestampBehavior' => array(
  * 			'class' => 'CTTimestampBehavior',
  * 			'createAttribute' => 'create_time_attribute',
  * 			'updateAttribute' => 'update_time_attribute',
  * 			'deleteAttribute' => 'delete_time_attribute',
  *             'softDelete' => true,
  *             'setUpdateOnCreate' => true,
  *             'timestampExpression' => new CDbExpression('UTC_TIMESTAMP()'),
  * 		)
  * 	);
  * }
  * </pre>
  * The {@link createAttribute} and {@link updateAttribute} options actually default to 'create_time' and 'update_time'
  * respectively, so it is not required that you configure them. If you do not wish CTTimestampBehavior
  * to set a timestamp for record update or creation, set the corresponding attribute option to null.
  *
  * By default, the update attribute is only set on record update. If you also wish it to be set on record creation,
  * set the {@link setUpdateOnCreate} option to true.
  *
  * Although CTTimestampBehavior attempts to figure out on it's own what value to inject into the timestamp attribute,
  * you may specify a custom value to use instead via {@link timestampExpression}
  *
  * @author Jonah Turnquist <poppitypop@gmail.com>
  * @package zii.behaviors
  * @since 1.1
  */

class CTTimestampBehavior extends CActiveRecordBehavior {
    /**
     * @var mixed The name of the attribute to store the creation time.  Set to null to not
     * use a timestamp for the creation attribute.  Defaults to 'created_at'
     */
    public $createAttribute = 'created_at';
    /**
     * @var mixed The name of the attribute to store the modification time.  Set to null to not
     * use a timestamp for the update attribute.  Defaults to 'updated_at'
     */
    public $updateAttribute = 'updated_at';
    /**
     * @var mixed The name of the attribute to store the deletion time.  Set to null to not
     * use a timestamp for the delete attribute.  Defaults to 'deleted_at'
     */
    public $deleteAttribute = 'deleted_at';

    /**
     * @var bool Whether to set the update attribute to the creation timestamp upon creation.
     * Otherwise it will be left alone.  Defaults to false.
     */
    public $setUpdateOnCreate = false;

    /**
     * @var bool Whether to set the delete attribute to the delete timestamp upon deletion and leave the row.
     * Otherwise the row will be deleted.  Defaults to false.
     */
    public $softDelete = false;

    /**
     * @var mixed The expression that will be used for generating the timestamp.
     * This can be either a string representing a PHP expression (e.g. 'time()'),
     * or a {@link CDbExpression} object representing a DB expression (e.g. new CDbExpression('NOW()')).
     * Defaults to null, meaning that we will attempt to figure out the appropriate timestamp
     * automatically. If we fail at finding the appropriate timestamp, then it will
     * fall back to using the current UNIX timestamp
     */
    public $timestampExpression;

    /**
     * @var array Maps column types to database method
     */
    protected static $map = array(
        'datetime'=>'NOW()',
        'timestamp'=>'NOW()',
        'date'=>'NOW()',
    );

    /**
     * Responds to {@link CModel::onBeforeSave} event.
     * Sets the values of the creation or modified attributes as configured
     *
     * @param CModelEvent $event event parameter
     * @return boolean
     */
    public function beforeSave($event) {
        if ($event->sender->getIsNewRecord() && ($this->createAttribute !== null)) {
	        if ($event->sender->hasAttribute($this->createAttribute)) {
		        $event->sender->setAttribute($this->createAttribute, $this->getTimestampByAttribute($this->createAttribute));
	        }
        }
        if ((!$event->sender->getIsNewRecord() || $this->setUpdateOnCreate) && ($this->updateAttribute !== null)) {
	        if ($event->sender->hasAttribute($this->updateAttribute)) {
		        $event->sender->setAttribute($this->updateAttribute, $this->getTimestampByAttribute($this->updateAttribute));
	        }
        }
	    // Pass it on...
	    return parent::beforeFind($event);
    }

    /**
     * Responds to {@link CModel::onBeforeDelete} event.
     * Sets the values of the deletion attributes as configured
     *
     * @param CModelEvent $event event parameter
     * @throws CDbException if $id is not an integer
     * @return bool return false to prevent hard deletion and true if deleteAttribute is null
     */
    public function beforeDelete($event) {
	    // Pass it on...
	    parent::beforeDelete($event);
        if ($this->deleteAttribute !== null && $event->isValid && !$event->handled) {
	        // Perform a soft delete if this model allows
	        if ($event->sender->hasAttribute($this->deleteAttribute))
	        {
		        $event->isValid = false;
		        $event->handled = true;
		        $event->sender->setAttribute($this->deleteAttribute, $this->getTimestampByAttribute($this->deleteAttribute));
		        if (!$event->sender->update(array($this->deleteAttribute)))
			        throw new CDbException('Error saving soft delete row.');
	        }
        }
	    return true;
    }

	/**
	 * Insert our soft-delete criteria
	 * @param CEvent $event
	 */
	public function beforeFind($event)
	{
		if ( $this->deleteAttribute && $event->sender->hasAttribute($this->deleteAttribute) )
		{
			// Merge in the soft delete indicator
			$event->sender->getDbCriteria()->mergeWith(
				array(
					'condition' => $event->sender->getTableAlias(true,false) . '.`' . $this->deleteAttribute . '` IS NULL',
				)
			);
		}

		// Pass it on...
		return parent::beforeFind($event);
	}


	function reinstate() {
        if ($this->deleteAttribute !== null) {
            if ($this->getOwner()->{$this->deleteAttribute} !== null) {
                $this->getOwner()->{$this->deleteAttribute} = null;
                $this->getOwner()->save();
            }
        }
    }
    
    /**
     * Gets the approprate timestamp depending on the column type $attribute is
     *
     * @param string $attribute $attribute
     * @return mixed timestamp (eg unix timestamp or a mysql function)
     */
    protected function getTimestampByAttribute($attribute) {
        if ($this->timestampExpression instanceof CDbExpression)
            return $this->timestampExpression;
        elseif ($this->timestampExpression !== null)
            return @eval('return '.$this->timestampExpression.';');

        $columnType = $this->getOwner()->getTableSchema()->getColumn($attribute)->dbType;
        return $this->getTimestampByColumnType($columnType);
    }

    /**
     * Returns the approprate timestamp depending on $columnType
     *
     * @param string $columnType $columnType
     * @return mixed timestamp (eg unix timestamp or a mysql function)
     */
    protected function getTimestampByColumnType($columnType) {
        return isset(self::$map[$columnType]) ? new CDbExpression(self::$map[$columnType]) : time();
    }
}