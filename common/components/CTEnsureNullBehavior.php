<?php
/**
 * CTEnsureNullBehavior behavior
 *
 * Ensures no empty AR property value is written to DB if property's default is `NULL`.
 * Ensures no empty AR property value is written to DB if property's default is NULL.
 * Useful if you want to be sure all empty values will be saved as nulls.
 *
 * Installation and configuration
 * Copy to your application extensions directory. Define behaviors() method in your
 * ActiveRecord mode as follows:
 * <pre>
 * function behaviors() {
 *      return array(
 *          'ensureNull' => array(
 *              'class' => 'CTEnsureNullBehavior',
 *              // Uncomment if you don't want to ensure nulls on update
 *              // 'useOnUpdate' => false,
 *          )
 *      );
 * }
 * </pre>
 * @version 1.0.1
 * @author creocoder <creocoder@gmail.com>
 */
class CTEnsureNullBehavior extends CActiveRecordBehavior
{
    /**
     * @var bool Ensure nulls on update
     */
    public $useOnUpdate=true;

    public function beforeSave($event)
    {
        $owner=$this->getOwner();

        if($owner->getIsNewRecord() || $this->useOnUpdate)
        {
            foreach($owner->getTableSchema()->columns as $column)
            {
                if($column->allowNull && trim($owner->getAttribute($column->name))==='')
                    $owner->setAttribute($column->name,null);
            }
        }
    }
}