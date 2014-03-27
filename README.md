cakephp-LogsTools
=================

This plugin contains three log tools.
-------

1.Log slow sql query to debug.log.

2.Log request params(contains request url,request time,php process time...) to mysql.

3.Loop to send emails to your email(contains error.log and debug.log).


HOW TO
------

1.Add plugin in bootstrap.php.

	CakePlugin::load('LogTools');

2.Config tools in AppController.php.
	/**
	 * enable:This is a switch.
	 * threshold(unit:ms):Sql time more than threshold to be loged.
	 * dataSource:The dataSource wanted to log, default value:default.
	 */	 
	'LogTools.SqlLog' => array('enable' => true, 'threshold' => 10, 'dataSource' => array('default', 'others')),
				
	/**
	 * If you want to use this tool, you must import logs.sql to mysql first.
	 * enable:This is a switch.	 
	 */	 
	'LogTools.RequestLog' => array('enable' => true),
				
	/**
	 * enable:This is a switch.
	 * urlEnable:If you want to use '?logSend=true' after url, you should set to true; This could send email immediately.
	 * autoClear:If you want to clear log files to null after sended, you should set to true.
	 * emailConfig:The config to send email, this file should be in 'app/Config/', such as 'send.php'.
	 * to:The email address you want to receive logs.	 
	 */	 
	'LogTools.LogFiles' => array('enable' => true, 'urlEnable' => true, 'emailConfig' => 'send', 'to' => 'name@email.com'),