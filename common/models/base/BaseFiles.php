<?php

/**
 * This is the model class for table "files".
 *
 * The followings are the available columns in table 'files':
 * @property integer $id
 * @property integer $portfolio_id
 * @property integer $photoblog_id
 * @property integer $blog_id
 * @property string $file_name
 * @property string $file_path
 * @property string $create_time
 * @property string $update_time
 * @property string $delete_time
 * @property string $metadata
 *
 * The followings are the available model relations:
 * @property Blog $blog
 * @property Photoblog $photoblog
 * @property Portfolio $portfolio
 */
class BaseFiles extends CTBaseActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'files';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('file_name, file_path, create_time', 'required'),
			array('portfolio_id, photoblog_id, blog_id', 'numerical', 'integerOnly'=>true),
			array('file_name, file_path', 'length', 'max'=>255),
			array('update_time, delete_time, metadata', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, portfolio_id, photoblog_id, blog_id, file_name, file_path, create_time, update_time, delete_time, metadata', 'safe', 'on'=>'search'),
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
			'portfolio' => array(self::BELONGS_TO, 'Portfolio', 'portfolio_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'portfolio_id' => 'Portfolio',
			'photoblog_id' => 'Photoblog',
			'blog_id' => 'Blog',
			'file_name' => 'File Name',
			'file_path' => 'File Path',
			'create_time' => 'Create Time',
			'update_time' => 'Update Time',
			'delete_time' => 'Delete Time',
			'metadata' => 'Metadata',
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
		$criteria->compare('portfolio_id',$this->portfolio_id);
		$criteria->compare('photoblog_id',$this->photoblog_id);
		$criteria->compare('blog_id',$this->blog_id);
		$criteria->compare('file_name',$this->file_name,true);
		$criteria->compare('file_path',$this->file_path,true);
		$criteria->compare('create_time',$this->create_time,true);
		$criteria->compare('update_time',$this->update_time,true);
		$criteria->compare('delete_time',$this->delete_time,true);
		$criteria->compare('metadata',$this->metadata,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return BaseFiles the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
