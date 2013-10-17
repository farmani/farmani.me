<?php
/**
 * Image processing with Imagine.
 *
 * Installation and configuration
 * Install Imagine via composer.
 * Copy component to extensions/image-component directory located inside your application
 * and add it to the application configuration the following way:
 * <pre>
 * return array(
 * ...
 *      'components'=>array(
 *      ...
 *          'image'=>array(
 *              'class'=>'ext.image-component.ImageComponent',
 *              'driver'=>'Gd',
 *          ),
 *      ...
 *      ),
 * ...
 * );
 * </pre>
 *
 * Usage example
 * <pre>
 * <?php
 * $image=Yii::app()->image->open('example.png');
 * $thumbnail=$image->thumbnail(new Imagine\Image\Box(100,100));
 * $thumbnail->save('example.thumb.png');
 * </pre>
 *
 * @version 0.04
 * @package yiiext.image-component
 */
class CTImage extends CApplicationComponent
{
    /**
     * @var string driver. Defaults to 'Gd'.
     */
    public $driver='Gd';
    private $_class;

    /**
     * Initializes the application component.
     */
    public function init()
    {
        parent::init();
        $class="Imagine\\{$this->driver}\\Imagine";
        $this->_class=new $class;
    }

    /**
     * Calls the named method which is not a class method.
     * @param string $name the method name
     * @param array $params method parameters
     * @return mixed the method return value
     */
    public function __call($name,$params)
    {
        if(method_exists($this->_class,$name))
            return call_user_func_array(array($this->_class,$name),$params);

        return parent::__call($name,$params);
    }

	/*
	public function resize($source,$destination,$width,$height)
	{
		$imagine = new \Imagine\Gd\Imagine();
		$size = new Imagine\Image\Box(120, 120);
		$mode = Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND;
		$img = $this->_class->open($source);
		$resizeimg = $img->thumbnail($size, $mode);
		$sizeR = $resizeimg->getSize();
		$widthR = $sizeR->getWidth();
		$heightR = $sizeR->getHeight();

		$preserve = $imagine->create($size);
		$startX = $startY = 0;
		if ($widthR < 120) {
			$startX = (120 - $widthR) / 2;
		}
		if ($heightR < 120) {
			$startY = (120 - $heightR) / 2;
		}
		$preserve->paste($resizeimg, new Imagine\Image\Point($startX, $startY))->save($destination);
	}
	*/

	public function resizer($baseDir, $subDir, $fileName, $imageUses) {
		$destinationBaseDir = $baseDir . $subDir . $imageUses['name'] . '/';
		$destination = $destinationBaseDir . $fileName;

		$imagine = $this->_class;
		$size = new Imagine\Image\Box($imageUses['width'],$imageUses['height']);
		$mode = Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND;

		$img = $this->_class->open($baseDir . $subDir . $fileName);
		$resizeimg = $img->thumbnail($size, $mode);
		$sizeR = $resizeimg->getSize();
		$widthR = $sizeR->getWidth();
		$heightR = $sizeR->getHeight();

		$preserve = $imagine->create($size);
		$startX = $startY = 0;
		if ($widthR < $imageUses['width']) {
			$startX = ($imageUses['width'] - $widthR) / 2;
		}
		if ($heightR < $imageUses['height']) {
			$startY = ($imageUses['height'] - $heightR) / 2;
		}
		$preserve->paste($resizeimg, new Imagine\Image\Point($startX, $startY))->save($destination);

		$pictureFile = $subDir . $imageUses['name'] . '/' . $fileName;
		$content = array(
			'SourceFile'=> $destination,
			'CacheControl' => 'max-age=94608000',
			'Expires' => gmdate('D, d M Y H:i:s T', strtotime('+3 years')),
			'ContentType' => CFileHelper::getMimeType($baseDir . $subDir . $fileName)
		);

		$amazon = new A2S3();
		try {
			$amazon->getClient()->putObject(array_merge($content,array(
						'Bucket'        => 'cdn.thankyoumenu.com',
						'Key'           => $pictureFile,
						'ACL'           => 'public-read',
					)));

			// We can poll the object until it is accessible
			$amazon->getClient()->waitUntilObjectExists(array(
					'Bucket'        => 'cdn.thankyoumenu.com',
					'Key'           => $pictureFile,
				));
			unlink($baseDir.$subDir.$imageUses['name'].'/'.$fileName);
		} catch (Aws\S3\Exception\S3Exception $e) {
			Yii::log($e,CLogger::LEVEL_ERROR,'CTS3AssetManager');
			throw new CTApiError(
				HHttp::ERROR_BADREQUEST,
				Yii::t('CTS3AssetManager', 'You need to configure the S3 component or set the variable s3Component properly!')
			);
		}

		return true;

	}
}