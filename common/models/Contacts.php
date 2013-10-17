<?php

/**
 * This is the model class for table "contacts".
 *
 * The followings are the available columns in table 'contacts':
 * @property integer $id
 * @property integer $photoblog_id
 * @property integer $blog_id
 * @property string $name
 * @property string $email
 * @property string $title
 * @property string $body
 * @property string $create_time
 * @property string $update_time
 * @property string $delete_time
 *
 * The followings are the available model relations:
 * @property Blog $blog
 * @property Photoblog $photoblog
 */
class Contacts extends BaseContacts
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'contacts';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('name, email, title, body, create_time', 'required'),
			array('photoblog_id, blog_id', 'numerical', 'integerOnly'=>true),
			array('name, email, title', 'length', 'max'=>255),
			array('update_time, delete_time', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, photoblog_id, blog_id, name, email, title, body, create_time, update_time, delete_time', 'safe', 'on'=>'search'),
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
			'blog' => array(self::BELONGS_TO, 'Blog', 'blog_id'),
			'photoblog' => array(self::BELONGS_TO, 'Photoblog', 'photoblog_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'photoblog_id' => 'Photoblog',
			'blog_id' => 'Blog',
			'name' => 'Name',
			'email' => 'Email',
			'title' => 'Title',
			'body' => 'Body',
			'create_time' => 'Create Time',
			'update_time' => 'Update Time',
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
		$criteria->compare('photoblog_id',$this->photoblog_id);
		$criteria->compare('blog_id',$this->blog_id);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('email',$this->email,true);
		$criteria->compare('title',$this->title,true);
		$criteria->compare('body',$this->body,true);
		$criteria->compare('create_time',$this->create_time,true);
		$criteria->compare('update_time',$this->update_time,true);
		$criteria->compare('delete_time',$this->delete_time,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return BaseContacts the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
