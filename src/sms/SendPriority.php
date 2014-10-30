<?php
# Author: xingrong0804@163.com (Xing Rong)

chdir(dirname(__FILE__));

require_once(__DIR__ . '/conf/priorityMQ.php');
require_once('/var/gs/message/src/base/lib/RabbitMQClient.inc');
require_once('/var/gs/message/src/base/NotiException.php');

/**
 * @brief    SendPriority类的主要功能是检查用户POST参数的合法性并发送消息至消息队列
 */
class SendPriority{

	/**
	 * @brief    待发送至消息队列的消息
	 * @var array
	 */
	private $_notification;

	/**
	 * @brief    RabbitMQ的PHP客户端对象
	 * @var object
	 */
	private $_client;

	/**
	 * @brief    标记是否启用debug模式，默认为false，即非debug模式
	 * @var boolean
	 */
	private $_debug;

	/**
	 * @brief    优先级消息队列设置
	 * @var array
	 */
	private $_priorityMQ;

	/**
	 * @brief    默认优先级
	 * @var interger
	 */
	private $_priority;

	/**
	 * @brief    默认过滤策略
	 * @var interger
	 */
	private $_filter;

	/**
	 * @brief    用户POST参数
	 * @var array
	 */
	private $_paramArray;

	/**
	 * @brief    标记消息是否发送成功
	 * @var boolean
	 */
	private $_sendFlag;

	/**
	 * @brief    默认用户名
	 * @var string
	 */
	private $_username;

	/**
	 * @brief    __construct 构造函数
	 *
	 * @param    $paramArray 用户POST参数
	 *
	 * @return   void
	 */
	function __construct($paramArray) {
		$this->_paramArray = $paramArray;
		global $priorityMQ;
		$this->_priorityMQ = $priorityMQ;
		try {
			//$this->_client = new RabbitMQClient($this->_priorityMQ);
		}
		catch(Exception $e) {
			//throw new NotiException($e->getMessage(),'11111');
		}
		$this->_debug = false; //默认关闭debug模式
		$this->_priority = 2; //默认优先级
		$this->_filter = 0; //默认过滤策略
		$this->_sendFlag = false; //默认失败
		$this->_username = "Unknown"; //默认用户名

		$this->buildNotification();
		$this->sendNotification();
	}

	/**
	 * @brief    isDebug 是否处于debug模式
	 *
	 * @return   boolean true表示处于debug模式
	 */
	private function isDebug() {
		return $this->_debug;
	}

	/**
	 * @brief    isSent 是否发送成功
	 *
	 * @return   boolean true表示发送成功
	 */
	public function isSent() {
		return $this->_sendFlag;
	}

	/**
	 * @brief    buildNotification 检查用户POST参数的合法性并构造待发送的消息
	 *
	 * @return   void
	 */
	private function buildNotification() {
		//反馈邮箱
		if(!empty($this->_paramArray['feedback'])) {
			$this->_notification['feedback'] = $this->_paramArray['feedback'];
		}
		else {
			$this->_notification['feedback'] = 'xingrong@jike.com';
			//throw new NotiException('Feedback parameter is null!','10102');
		}
		if(!filter_var($this->_notification['feedback'], FILTER_VALIDATE_EMAIL)) {
			throw new NotiException('Parameter feedback\'s value invalid!','10202');
		}
		//优先级
		if(!empty($this->_paramArray['priority'])) {
			$this->_notification['priority'] = $this->_paramArray['priority'];
		}
		else {
			$this->_notification['priority'] = $this->_priority;
		}
		//过滤策略
		if(!empty($this->_paramArray['filter'])) {
			$this->_notification['filter'] = $this->_paramArray['filter'];
		}
		else {
			$this->_notification['filter'] = $this->_filter;
		}
		//用户名
		if(!empty($this->_paramArray['username'])) {
			$this->_notification['username'] = $this->_paramArray['username'];
		}
		else {
			$this->_notification['username'] = $this->_username;
		}
		//手机号码
		if(!empty($this->_paramArray['phone'])) {
			if(!preg_match("/^1\d{10}$/",$this->_paramArray['phone'])) {
				throw new NotiException('Parameter phone\'s value invalid!','10203');
			}
			$this->_notification['phone'] = $this->_paramArray['phone'];
			$ldap_username = $this->ldapUsername($this->_notification['phone']);
			if(!$ldap_username) {
				//默认值
			}
			else {
				if($this->_notification['username'] != $this->_username 
						&& $this->_notification['username'] != $ldap_username) {
					throw new NotiException('Username and phone is inconsistent!','10301');
				}
				else {
					$this->_notification['username'] = $ldap_username;
				}
			}
		}
		else {
			if($this->_notification['username'] != $this->_username) {
				$ldap_mobile = $this->ldapMobile($this->_notification['username']);
				if(!$ldap_mobile) {
					throw new NotiException('Parameter username\'s value invalid!','10205');
				}
				else {
					$this->_notification['phone'] = $ldap_mobile;
				}
			}
			else {
				throw new NotiException('Username and phone parameters are null!','10101');
			}
		}
		//短信内容
		if(!empty($this->_paramArray['content'])) {
			$this->_notification['content'] = $this->_paramArray['content'];
		}
		else {
			throw new NotiException('Content parameter is null!','10102');
		}
		//copy其他信息
		$this->_notification = $this->_notification + $this->_paramArray;
	}

	/**
	 * @brief    sendNotification 发送消息至优先级消息队列
	 *
	 * @return   void
	 */
	private function sendNotification() {
		//转换为JSON格式
		$msg = json_encode($this->_notification,JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
		//首先判断队列消息数目
		$queueCountJson = file_get_contents("/var/gs/message/src/sms/queueCount/noticenter_sms_1_1");
		$queueCountArray = json_decode($queueCountJson,true);
		$queueCount = $queueCountArray['message_count'];
		//if($queueCount > 1000) {
		if(true) {
			system("php ".__DIR__."/FilterModule.php '" . $msg . "'" . " > /dev/null 2>&1 &");
			$this->_sendFlag = true;
			return true;
		}
		//发送消息到优先级队列
		if(array_key_exists(strval($this->_notification['priority']),$this->_priorityMQ['TOPIC'])) {
			$topicArray = $this->_priorityMQ['TOPIC'][strval($this->_notification['priority'])];
			$topic = $topicArray[array_rand($topicArray)]; //负载均衡
			try {
				//$isPublish = $this->_client->publish($topic, $msg, true);
			}
			catch(Exception $e) {
				system("php ".__DIR__."/FilterModule.php '" . $msg . "'" . " > /dev/null 2>&1 &");
				//throw new NotiException($e->getMessage(),'11111');
			}
			if(false) {
				$this->_sendFlag = true;
			}
			else {
				system("php ".__DIR__."/FilterModule.php '" . $msg . "'" . " > /dev/null 2>&1 &");
				//throw new NotiException('Message Send Failed!','30201');
			}      
		}
		else {
			throw new NotiException('Parameter priority\'s value invalid!!','10201');
		}

	}

	/**
	 * @brief    ldapMobile 通过用户名在ldap中查询手机号码
	 *
	 * @param    $username string 用户名
	 *
	 * @return   mixed 返回手机号码，或者返回false表示查找失败
	 */
	private function ldapMobile($username) {
		$ldap_server = "192.168.10.17";
		$conn = ldap_connect($ldap_server);
		if(!$conn){
			//TODO(xingrong):die("Failed to connection LDAP server.\n");
		}
		if (!ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3)) {
			//TODO(xingrong):die("Failed to set protocol version to 3\n");
		}
		$bind = ldap_bind($conn, NULL, NULL);
		if(!$bind){
			//TODO(xingrong):die("Failed to bind LDAP server.\n");
		}
		$result = ldap_search($conn, 'ou=people,dc=test,dc=cn', "(&(uid={$username}))", array('mobile'));
		if(!$result){
			return false;
		}
		$info = ldap_get_entries($conn, $result);
		if(is_array($info) && isset($info) && is_array($info[0])) {
			if(array_key_exists("mobile",$info[0])) {
				return $info[0]['mobile'][0];
			}
			else {
				return false;
			}
		}
	}

	/**
	 * @brief    ldapUsername 通过手机号码在ldap中查询用户名
	 *
	 * @param    $mobile string 手机号码
	 *
	 * @return   mixed 返回用户名，或者返回false表示查找失败
	 */
	private function ldapUsername($mobile) {
		$ldap_server = "192.168.10.17";
		$conn = ldap_connect($ldap_server);
		if(!$conn){
			//TODO(xingrong):die("Failed to connection LDAP server.\n");
		}
		if (!ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3)) {
			//TODO(xingrong):die("Failed to set protocol version to 3\n");
		}
		$bind = ldap_bind($conn, NULL, NULL);
		if(!$bind){
			//TODO(xingrong):die("Failed to bind LDAP server.\n");
		}
		$result = ldap_search($conn, 'ou=people,dc=test,dc=cn', "(&(mobile={$mobile}))", array('uid'));
		if(!$result){
			//  return false;
		}
		$info = ldap_get_entries($conn, $result);
		if(empty($info[0])) {
			return false;
		}
		if(array_key_exists("uid",$info[0])) {
			return $info[0]['uid'][0];
		}
		else {
			return false;
		}
	}

	}
