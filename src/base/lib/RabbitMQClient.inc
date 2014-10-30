<?php

require_once("amqp.inc");

/**
*  RabbitMQ Client's message format
**/
class message{
    public $body;
    public $delivery_info;
    public function __construct(&$body = '', &$delivery_info = null)
    {
        $this->body = $body;
        $this->delivery_info=$delivery_info;

    }
}


/**
 * RabbitMQ Client Library
 **/
class RabbitMQClient {
    private $serverAddress;   //serverAddress[0,1]['host'],['port']
    private $user;
    private $pass;
    private $vhost;
    private $exchange;

    private $connection;
    private $channel;
    private $curServer;            //current server
    private $numServer;            //the num of servers

    private $numQ;            //the queues num of consumer subscribed
    private $arrQueue;
    private $prefetchCount;
    private $autoAck;
    private $processMessage;

    const messageMaxLen = 131072;  //Message Max Length is 128KB

    private function _loadConfig($config){
        $this->serverAddress = $config['serverAddress'];
        $this->user     = $config['USER'];
        $this->pass     = $config['PASS'];
        $this->vhost    = $config['VHOST'];
        $this->exchange = $config['EXCHANGE'];

        $this->numServer= count($this->serverAddress);
        $this->curServer= -1;
        $this->numQ      = -1;
    }

    private function _createConnection(){
        $this->curServer  = (1 + $this->curServer)%(count($this->serverAddress));
        $host             = $this->serverAddress[$this->curServer]['host'];
        $this->connection = new AMQPConnection($host, $this->serverAddress[$this->curServer]['port'], $this->user, $this->pass, $this->vhost);
        $this->channel    = $this->connection->channel();
        error_log("RabbitMQClient::connection:  Connect to server $host successfully");
    }

    /**
     * RabbitMQ construct function
    **/
    public function __construct($config) {
        $this->_loadConfig($config);
        shuffle($this->serverAddress);
        if(!$this->numServer){
            throw new RabbitMQClientExcetpion('RabbitMQClient::__construct:  No Server Address');
        }
        for ($i = 1; $i <= $this->numServer; $i++){
            try{
                    $this->_createConnection();
                    $this->channel->exchange_declare($this->exchange, '',$passive=true);
                    return true;
            }
            catch (Exception $e){
                $e1 = $e->getMessage(); $e2 = $e->getFile(); $e3 = $e->getLine();
                $host = $this->serverAddress[$this->curServer]['host'];
                if ($i < $this->numServer){
                    if(strpos($e1, 'no exchange')!=false){
                        throw new RabbitMQClientExcetpion("RabbitMQClient::__construct: $e1");
                    }
                    error_log("RabbitMQClient::__construct:  (error occurs when trying to connect to server $host) $e1 in $e2 on $e3");
                    error_log("RabbitMQClient::__construct:  try to connect to a new server...");
                }
                if ($i == $this->numServer){
                    error_log("RabbitMQClient::__construct:  (error occurs when trying to connect to last server $host) $e1 in $e2 on $e3");
                    error_log("RabbitMQClient::__construct:  throw exception!");
                    throw new RabbitMQClientExcetpion("RabbitMQClient::__construct: fail to  connect to server");
                }
            }
        }
    }


    private function _basicPublish($topic, $body, $duration){
        if (strlen($body) > self::messageMaxLen){
            throw new RabbitMQClientExcetpion(__METHOD__.': Message length must be 128K or below!');
        }
        for($i=1; $i<= $this->numServer; $i++){
            try{
                if($i!=1){
                    $this->_createConnection();
                }
                if($duration){
                    $msg = new AMQPMessage($body, array('delivery_mode'=>2));
                }
                else{
                    $msg = new AMQPMessage($body);
                }
                $this->channel->basic_publish($msg, $this->exchange, $topic);
                return true;
            }
            catch(Exception $e){
                $e1 = $e->getMessage(); $e2 = $e->getFile(); $e3 = $e->getLine();
                $host = $this->serverAddress[$this->curServer]['host'];
                error_log('RabbitMQClient::publish:  error occurs when connecting to '. $host.') '. $e1 .' in '. $e2 .' on '. $e3);
                if($i < $this->numServer){
                    error_log('RabbitMQClient::publish:  try to connect to a new server...');
                }
                else{
                    error_log('RabbitMQClient::publish:  fail to publish message. Throw exception!');
                    throw new RabbitMQClientExcetpion(__METHOD__.': '. $e1.' in '. $e2.' on '.$e3);
                }
            }
        }
    }


    /**
     *  interface for producer to publish a message or a message array
    **/
    public function publish($topic, $msg, $duration = true){
        if(is_array($msg)){
            foreach($msg as &$body){
                $this->_basicPublish($topic, $body, $duration);
            }
        }
        else{
            $this->_basicPublish($topic, $msg, $duration);
        }
        return true;
    }


    /**
     *  interface for consumer to subscribe a queue, which is used to receive a message in ***BLOCKING*** mode
     **/
    public function subscribe($queue, $prefetchCount = 0, $autoAck = true){
       $this->numQ=-1; 
       if(is_array($queue)){
            foreach($queue as $q){
                $this->numQ++;
                $this->arrQueue[$this->numQ]=$q;
            }
        }
        else{
            $this->numQ=0;
            $this->arrQueue[$this->numQ]=$queue;
        }
        $this->prefetchCount = $prefetchCount;
        $this->autoAck =$autoAck;
        for($i=1; $i<=$this->numServer; $i++){
            try{
                if($i!=1){
                    $this->_createConnection();
                }
                $this->channel->basic_qos(null, $this->prefetchCount, null);
                for($j=0;$j<=$this->numQ;$j++){
                        $this->channel->basic_consume($this->arrQueue[$j], "", false, false, false, false);
                }
                return true;
            }
            catch(Exception $e){
                $e1 = $e->getMessage(); $e2 = $e->getFile(); $e3 = $e->getLine();
                $host = $this->serverAddress[$this->curServer]['host'];
                if(strpos($e1, 'no queue')!=false){
                    throw new RabbitMQClientExcetpion(__METHOD__. ': '. $e1.' in '.$e2.' on '.$e3);
                }
                error_log(__METHOD__.':  (error occurs when connecting to '. $host.') '. $e1 .' in '. $e2 .' on '. $e3);
                if($i < $this->numServer){
                    error_log(__METHOD__.':  try to connect to a new server...');
                }
                else{
                    error_log(__METHOD__.':  fail to subscribe message. Throw exception!');
                    throw new RabbitMQClientExcetpion(__METHOD__.': '. $e1.' in '. $e2.' on '.$e3);
                }
            }
        }
    }


    /**
     *  Interface for consumer to receive a message in  ***BLOCKING*** mode. You should call interface "subscrible()" first to use this interface.
     **/
    public function consume(){
        for($i=0; $i<=$this->numServer; $i++){
            try{
                if($i!=0){
                    $this->_createConnection();
                    for($j=0;$j<=$this->numQ;$j++){
                        $this->channel->basic_qos(null, $this->prefetchCount, null);
                        $this->channel->basic_consume($this->arrQueue[$j], "", false, false, false, false);
                    }
                }
                $msg = $this->channel->wait();

                $ret_msg = null;
                if($msg!=null){
                    $delivery_info = array(
                        'queue' => $msg->delivery_info['routing_key'],
                        'redelivered' => $msg->delivery_info['redelivered'] == null? 0: $msg->delivery_info['redelivered'],
                        'delivery_tag' => $msg->delivery_info['delivery_tag'],
                    );
                    $ret_msg = new message($msg->body, $delivery_info);
                    if($this->autoAck){
                        $this->channel->basic_ack($msg->delivery_info['delivery_tag']);
                    }
                }
                return $ret_msg;
            }
            catch(Exception $e){
                $e1 = $e->getMessage(); $e2 = $e->getFile(); $e3 = $e->getLine();
                $host = $this->serverAddress[$this->curServer]['host'];
                error_log(__METHOD__.':  (error occurs when connecting to '. $host.') '. $e1 .' in '. $e2 .' on '. $e3);
                if($i < $this->numServer){
                    error_log(__METHOD__.':  try to connect to a new server...');
                }
                else{
                    error_log(__METHOD__.':  fail to consume message. Throw exception!');
                    throw new RabbitMQClientExcetpion(__METHOD__.': '. $e1.' in '. $e2.' on '.$e3);
                }
            }
        }
    }
    /**
     *  interface for consumer to receive a message in  ***nonBLOCKING*** mode. You should **NOT*** call the interface "subscrible()".
     **/
    public function get($queue, $autoAck=true)
    {
        for($i=1; $i<= $this->numServer; $i++){
            try{
                if($i!=1){
                    $this->_createConnection();
                }
                $msg = $this->channel->basic_get($queue, false, null);
                $ret_msg = null;
                if($msg!=null){
                    $delivery_info = array(
                        'queue' => $queue,
                        'redelivered' => $msg->delivery_info['redelivered'] == null? 0: $msg->delivery_info['redelivered'],
                        'delivery_tag' => $msg->delivery_info['delivery_tag'],
                        'message_count'=> $msg->delivery_info['message_count']
                    );
                    $ret_msg = new message($msg->body, $delivery_info);
                    if($autoAck){
                        $this->channel->basic_ack($msg->delivery_info['delivery_tag']);
                    }
                }
                if(!$ret_msg){
                    usleep(100000);
		}
                return $ret_msg;
            }
            catch(Exception $e){
                $e1 = $e->getMessage(); $e2 = $e->getFile(); $e3 = $e->getLine();
                $host = $this->serverAddress[$this->curServer]['host'];
                error_log('RabbitMQClient::get:  error occurs when connecting to '. $host.') '. $e1 .' in '. $e2 .' on '. $e3);
                if($i < $this->numServer){
                    error_log('RabbitMQClient::get:  try to connect to a new server...');
                }
                else{
                    error_log('RabbitMQClient::get:  fail to get message. Throw exception!');
                    throw new RabbitMQClientExcetpion(__METHOD__.': '. $e1.' in '. $e2.' on '.$e3);
                }
            }
        }
    }

    /**
     *  interface for consumer to acknowledge a message
     */
    public function ack($msg, $multiple=false) {
        if($msg && $msg->delivery_info['delivery_tag'])
            $this->channel->basic_ack($msg->delivery_info['delivery_tag'], $multiple);
    }

    public function __destruct(){
        try{
            if (isset($this->channel)&&$this->channel){
            $this->channel->close();
            }
            if (isset($this->connection)&&$this->connection){
                $this->connection->close();
            }
        }
        catch (Exception $e){
        }
    }
};

class RabbitMQClientExcetpion extends Exception{
};

?>
