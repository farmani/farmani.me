<?php if (!defined('YII_PATH')) exit('No direct script access allowed!');
/**
 * CHttpCacheFilter class file.
 *
 * @author Da:Sourcerer <webmaster@dasourcerer.net>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2012 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * CTHttpCacheFilter implements http caching. It works a lot like {@link COutputCache}
 * as a filter, except that content caching is being done on the client side.
 *
 * @author Ramin Farmani <ramin.farmani@gmail.com>
 * @link http://www.thankyoumenu.com/
 * @copyright Copyright &copy; 2013
 * @license http://www.thankyoumenu.com/license/
 */
class CTHttpCacheFilter extends CHttpCacheFilter
{
    /**
     * @var array|boolean the functions used to serialize and unserialize cached data. Defaults to null, meaning
     * using the default PHP `serialize()` and `unserialize()` functions. If you want to use some more efficient
     * serializer (e.g. {@link http://pecl.php.net/package/igbinary igbinary}), you may configure this property with
     * a two-element array. The first element specifies the serialization function, and the second the deserialization
     * function. If this property is set false, data will be directly sent to and retrieved from the underlying
     * cache component without any serialization or deserialization. You should not turn off serialization if
     * you are using {@link CCacheDependency cache dependency}, because it relies on data serialization.
     */
    public $serializer;
    /**
     * @var string the name of the hashing algorithm to be used by {@link computeHMAC}.
     * See {@link http://php.net/manual/en/function.hash-algos.php hash-algos} for the list of possible
     * hash algorithms. Note that if you are using PHP 5.1.1 or below, you can only use 'sha1' or 'md5'.
     *
     * Defaults to 'md5', meaning using md5 hash algorithm.
     * @since 1.1.3
     */
    public $hashAlgorithm='md5';
	/**
	 * @var string Http cache control headers. Set this to an empty string in order to keep this
	 * header from being sent entirely.
	 */
	public $cacheControl='max-age=259200, public';

	/**
	 * Performs the pre-action filtering.
	 * @param CFilterChain $filterChain the filter chain that the filter is on.
	 * @return boolean whether the filtering process should continue and the action should be executed.
	 */
	public function preFilter($filterChain)
	{
		// Only cache GET and HEAD requests
		if(!in_array(Yii::app()->getRequest()->getRequestType(), array('GET', 'HEAD')))
			return true;

		$lastModified=$this->getLastModifiedValue();
		$etag=$this->getEtagValue();

		if($etag===false&&$lastModified===false)
			return true;

		if($etag)
			header('ETag: '.$etag);

		if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])&&isset($_SERVER['HTTP_IF_NONE_MATCH']))
		{
			if($this->checkLastModified($lastModified)&&$this->checkEtag($etag))
			{
				$this->send304Header();
				$this->sendCacheControlHeader();
				return false;
			}
		}
		elseif(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
		{
			if($this->checkLastModified($lastModified))
			{
				$this->send304Header();
				$this->sendCacheControlHeader();
				return false;
			}
		}
		elseif(isset($_SERVER['HTTP_IF_NONE_MATCH']))
		{
			if($this->checkEtag($etag))
			{
				$this->send304Header();
				$this->sendCacheControlHeader();
				return false;
			}

		}

		if($lastModified)
			header('Last-Modified: '.gmdate('D, d M Y H:i:s', $lastModified).' GMT');

		$this->sendCacheControlHeader();
		return true;
	}

	/**
	 * Generates a quoted string out of the seed
	 * @param mixed $seed Seed for the ETag
     * @return string base64 encoded md5 value
	 */
	protected function generateEtag($seed)
	{
		return 'W/"'.base64_encode(md5($seed,true)).'"';
	}
}
