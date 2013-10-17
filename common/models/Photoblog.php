<?php

/**
 * This is the model class for table "photoblog".
 *
 * The followings are the available columns in table 'photoblog':
 * @property integer $id
 * @property string $title
 * @property string $description
 * @property string $create_time
 * @property string $update_time
 * @property string $publish_time
 * @property string $delete_time
 *
 * The followings are the available model relations:
 * @property Categories[] $categories
 * @property Contacts[] $contacts
 * @property Files[] $files
 * @property Tags[] $tags
 */
class Photoblog extends BasePhotoblog
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'photoblog';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('title, create_time', 'required'),
			array('title', 'length', 'max'=>255),
			array('description, update_time, publish_time, delete_time', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, title, description, create_time, update_time, publish_time, delete_time', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'categories' => array(self::MANY_MANY, 'Categories', 'categories_has_photoblog(photoblog_id, categories_id)'),
			'contacts' => array(self::HAS_MANY, 'Contacts', 'photoblog_id'),
			'files' => array(self::HAS_MANY, 'Files', 'photoblog_id'),
			'tags' => array(self::MANY_MANY, 'Tags', 'photoblog_has_tags(photoblog_id, tags_id)'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'title' => 'Title',
			'description' => 'Description',
			'create_time' => 'Create Time',
			'update_time' => 'Update Time',
			'publish_time' => 'Publish Time',
			'delete_time' => 'Delete Time',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('title',$this->title,true);
		$criteria->compare('description',$this->description,true);
		$criteria->compare('create_time',$this->create_time,true);
		$criteria->compare('update_time',$this->update_time,true);
		$criteria->compare('publish_time',$this->publish_time,true);
		$criteria->compare('delete_time',$this->delete_time,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return BasePhotoblog the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
