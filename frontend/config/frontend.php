<?php
/**
 *
 * frontend.php configuration file
 *
 * @author Antonio Ramirez <amigo.cobos@gmail.com>
 * @link http://www.ramirezcobos.com/
 * @link http://www.2amigos.us/
 * @copyright 2013 2amigOS! Consultation Group LLC
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */
defined('APP_CONFIG_NAME') or define('APP_CONFIG_NAME', 'frontend');

// web application configuration
return array(
	'name' => '{APPLICATION NAME}',
	'basePath' => realPath(__DIR__ . '/..'),
	// path aliases
	'aliases' => array(
		'bootstrap' => dirname(__FILE__) . '/../..' . '/common/lib/vendor/2amigos/yiistrap',
		'yiiwheels' =>  dirname(__FILE__) . '/../..' . '/common/lib/vendor/2amigos/yiiwheels'
	),

	// application behaviors
	'behaviors' => array(),

	// controllers mappings
	'controllerMap' => array(),

	// application modules
	'modules' => array(),

	// import paths - yiistrap configuration
    'import' => array(
        'bootstrap.helpers.TbHtml',
    ),
    // application components
    'components' => array(
        // yiistrap configuration
        'bootstrap' => array(
            'class' => 'bootstrap.components.TbApi',
        ),
        // yiiwheels configuration
        'yiiwheels' => array(
            'class' => 'yiiwheels.YiiWheels',
        ),

		'clientScript' => array(
			'scriptMap' => array(
				'bootstrap.min.css' => false,
				'bootstrap.min.js' => false,
				'bootstrap-yii.css' => false
			)
		),
        'urlManager' => array(
            'class' => 'CTUrlManager',
            'currentDomain' => 'farmani.me',
            'urlFormat' => 'path',
            'showScriptName' => false,
            'caseSensitive' => true,
            'appendParams' => true,
            'cacheID' => 'cache',
            'urlSuffix' => '.html',
            'rules' => array(
                '<language:(en|fa)>/<_c>/<_a>/*' => '<_c>/<_a>/',
                '<language:(en|fa)>/<_m>/<_c>/<_a>/*' => '<_m>/<_c>/<_a>/',
            ),
        ),
        'log' => array(
            'class' => 'CLogRouter',
            'routes' => array(
                array(
                    'class' => 'vendor.malyshev.yii-debug-toolbar.yii-debug-toolbar.YiiDebugToolbarRoute',
                    'ipFilters' => array('127.0.0.1', '192.168.1.5'),
                ),
                array(
                    'class'=>'CWebLogRoute',
                    // you can include more levels separated by commas
                    'levels'=>'trace, info, error, warning, vardump',
                    // categories are those you used in the call to Yii::trace
                    'categories'=>'*',
                    // This is self-explanatory right? but also works in Chrome!
                    'showInFireBug'=>true
                ),
                array(
                    'class'=>'CFileLogRoute',
                    'levels'=>'trace, info, error, warning, vardump',
                ),
            ),
        ),
		'errorHandler' => array(
			'errorAction' => 'site/error',
		)
	),
);