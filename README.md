cakephp-LogsTools
=================

This plugin contains three log tools.

1.Log slow sql query to debug.log.

2.Log request params(contains url,query time,php process time...) to mysql.

3.Loop to send emails to your email(contains error.log and debug.log).


<h1>HOW TO</h1>

1.Add plugin in bootstrap.php.
CakePlugin::load('LogTools');

2.Config tools in AppController.php.
/**
 * threshold:执行时间大于等于threshold的sql会被记录，单位：毫秒；
 * dataSource:需要记录的数据源，默认为default
 */
'LogTools.SqlLog' => array('enable' => true, 'threshold' => 10, 'dataSource' => array('default', 'master')),
			
/**
 * If you want to use this tool, you must inport logs.sql to mysql first.
 * enable:开关
 */
'LogTools.RequestLog' => array('enable' => true),
			
/**
 * enable:开关；
 * urlEnable:?logSend=true开关；
 * autoClear:是否自动清理log文件；
 * emailConfig:发送email的配置；
 * to:接口log的邮箱
 */
'LogTools.LogFiles' => array('enable' => true, 'urlEnable' => true, 'emailConfig' => 'send', 'to' => 'name@email.com'),