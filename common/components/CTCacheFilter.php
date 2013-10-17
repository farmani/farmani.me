<?php if (!defined('YII_PATH')) exit('No direct script access allowed!');
/**
 * CTCacheFilter implements caching. It works a lot like {@link COutputCache}
 * as a filter, except that content caching is being done on the client side.
 *
 * @author Ramin Farmani <ramin.farmani@gmail.com>
 * @link http://www.thankyoumenu.com/
 * @copyright Copyright &copy; 2013
 * @license http://www.thankyoumenu.com/license/
 */
class CTCacheFilter extends CFilter
{
	public $id;
	protected function preFilter($filterChain)
	{
		header('Content-Type: application/json; charset=UTF-8');
		if(isset($_SERVER['HTTP_ACCEPT_ENCODING']) && stripos($_SERVER['HTTP_ACCEPT_ENCODING'],'gzip,') !== false && stripos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6.') === false) {
			$key = 'gzip:' . $filterChain->controller->uniqueId . ':' . $filterChain->action->id . ':' . $this->id;
		} else
			$key = $filterChain->controller->uniqueId . ':' . $filterChain->action->id . ':' . $this->id;

		$result = Yii::app()->cache->get($key);
		if ($result === false)
			return true;
		else {
			if(isset($_SERVER['HTTP_ACCEPT_ENCODING']) && stripos($_SERVER['HTTP_ACCEPT_ENCODING'],'gzip,') !== false && stripos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6.') === false) {
				header('Content-Encoding: gzip');
				header('Vary: Accept-Encoding');
			}
			echo $result;
		}

		// logic being applied before the action is executed
		return false; // false if the action should not be executed
	}

	protected function postFilter($filterChain)
	{
		$result = $filterChain->controller->output;
		if(isset($_SERVER['HTTP_ACCEPT_ENCODING']) && stripos($_SERVER['HTTP_ACCEPT_ENCODING'],'gzip,') !== false && stripos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6.') === false) {
			header('Content-Encoding: gzip');
			header('Vary: Accept-Encoding');
			$key = 'gzip:' . $filterChain->controller->uniqueId . ':' . $filterChain->action->id . ':' . $this->id;
			$result = gzencode($result, 9);

			Yii::app()->cache->add($key, $result, Yii::app()->params['RESTControllerCacheDuration']);
		} else{
			$key = $filterChain->controller->uniqueId . ':' . $filterChain->action->id . ':' . $this->id;

			Yii::app()->cache->add($key, $result, Yii::app()->params['RESTControllerCacheDuration']);
			Yii::app()->cache->add('gzip:' . $key, $result, Yii::app()->params['RESTControllerCacheDuration']);
		}
		echo $result;
	}
}
