<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ramin
 * Date: 7/2/13
 * Time: 8:12 PM
 * To change this template use File | Settings | File Templates.
 */

class CTS3AssetManager extends CAssetManager
{

	public $bucket;
	public $path;
	public $host;
	public $s3Component = 's3';
	public $cacheComponent = 'cache';
	private $_baseUrl;
	private $_basePath;
	private $_published;

	public function getBasePath()
	{
		if ($this->_basePath === null) {
			$this->_basePath = $this->path;
		}
		return $this->_basePath;
	}

	public function getBaseUrl()
	{
		if ($this->_baseUrl === null) {
			$this->_baseUrl = 'https://' . $this->host . '/' . $this->path;
		}
		return $this->_baseUrl;
	}

	private function getCache()
	{
		if (!Yii::app()->{$this->cacheComponent}) {
			throw new CException('You need to configure a cache storage or set the variable cacheComponent');
		}

		return Yii::app()->{$this->cacheComponent};
	}

	private function _putObject($source, $destination)
	{
		$ext = substr($source,-3,3);
		if($ext == '.js' || $ext == 'css'){
			$data = implode("", file($source));
			$gzdata = gzencode($data, 9);
			$content = array(
				'Body'      =>$gzdata,
				'ContentEncoding'  => 'gzip',
				'CacheControl' => 'max-age=94608000',
				'Expires' => gmdate('D, d M Y H:i:s T', strtotime('+3 years')),
			);
		}else
			$content = array(
				'SourceFile'=> $source,
				'CacheControl' => 'max-age=94608000',
				'Expires' => gmdate('D, d M Y H:i:s T', strtotime('+3 years')),
				'ContentType' => CFileHelper::getMimeType($source)
			);

		$amazon = new A2S3();
		try {
			$amazon->getClient()->putObject(array_merge($content,array(
					'Bucket'        => $this->bucket,
					'Key'           => $destination,
					'ACL'           => 'public-read',
				))
			);
			// We can poll the object until it is accessible
			$amazon->getClient()->waitUntilObjectExists(array(
					'Bucket' => $this->bucket,
					'Key'    => $destination
				));

			return true;
		} catch (Aws\S3\Exception\S3Exception $e) {
			Yii::log($e,CLogger::LEVEL_ERROR,'CTS3AssetManager');
			throw new CException(
				Yii::t('CTS3AssetManager', 'You need to configure the S3 component or set the variable s3Component properly!')
			);
		}
	}

	private function getCacheKey($path)
	{
		return $this->hash(Yii::app()->request->serverName) . '.' . $path;
	}

	public function publish($path, $hashByName = false, $level = -1, $forceCopy = false)
	{
		if (isset($this->_published[$path])) {
			return $this->_published[$path];
		} elseif (($src = realpath($path)) !== false) {
			if (is_file($src)) {
				$dir = $this->hash($hashByName ? basename($src) : dirname($src) . filemtime($src));
				$fileName = basename($src);
				$dstDir = $this->getBasePath() . '/' . $dir;
				$dstFile = $dstDir . '/' . $fileName;
				if ($this->getCache()->get($this->getCacheKey($path)) === false) {
					if ($this->_putObject($src, $dstFile)) {
						$this->getCache()->set($this->getCacheKey($path), true, 0, new CFileCacheDependency($src));
					} else {
						throw new CException('Could not send asset do S3');
					}
				}

				return $this->_published[$path] = $this->getBaseUrl() . "/$dir/$fileName";
			} elseif (is_dir($src)) {
				$dir = $this->hash($hashByName ? basename($src) : $src . filemtime($src));
				$dstDir = $this->getBasePath() . DIRECTORY_SEPARATOR . $dir;

				if ($this->getCache()->get($this->getCacheKey($path)) === false) {
					$files = CFileHelper::findFiles(
						$src, array(
							'exclude' => $this->excludeFiles,
							'level' => $level,
						)
					);

					foreach ($files as $f) {
						$dstFile
							= $this->getBasePath() . '/' . $dir . '/' . str_replace($src . DIRECTORY_SEPARATOR, "", $f);

						if (!$this->_putObject($f, $dstFile)) {
							throw new CException('Could not send assets do S3');
						}
					}

					$this->getCache()->set($this->getCacheKey($path), true, 0, new CDirectoryCacheDependency($src));
				}


				return $this->_published[$path] = $this->getBaseUrl() . '/' . $dir;
			}
		}
		throw new CException(Yii::t(
			'yii', 'The asset "{asset}" to be published does not exist.', array('{asset}' => $path)
		));
	}

}