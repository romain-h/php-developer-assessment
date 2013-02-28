<?php
use Silex\Provider\DoctrineServiceProvider;

namespace PeerindexChallenge;
 
class User {
	private $db;
	private $uid;
	private $oauth_token;
	private $oauth_secret;
 
 	public function __construct($db, $uid){
        $this->db = $db;
        $this->uid = $uid;
    }

    public function exist() {
    	$sql = "SELECT * FROM users WHERE oauth_uid = {$this->uid}";
    	$result = $this->db->fetchAssoc($sql, array((int) $id));
    	if(empty($result))
    		return false;
    	
    	return true;
    }

    public function rightUserInSession($session){
    	$sql = "SELECT * FROM users WHERE oauth_uid = {$this->uid}";
    	$result = $this->db->fetchAssoc($sql, array());

    	// Check tokens:
    	return true;
    }

    public function add($user_info, $access_token){
        $query = "INSERT INTO users (oauth_uid, username, oauth_token, oauth_secret) VALUES ({$user_info->id}, '{$user_info->screen_name}', '{$access_token['oauth_token']}', '{$access_token['oauth_token_secret']}')"; 
        $this->db->executeQuery($query);
        $this->setToken($access_token['oauth_token']);
    	$this->setSecret($access_token['oauth_token_secret']);
    }

    public function updateToken($token){
    	$query = "UPDATE users SET oauth_token = '{$token['oauth_token']}', oauth_secret = '{$token['oauth_token_secret']}' WHERE oauth_uid = {$this->uid}";
    	$this->db->executeQuery($query);
    	$this->setToken($token['oauth_token']);
    	$this->setSecret($token['oauth_token_secret']);
    }
    public function getUid(){
        return $this->uid;
    }
    public function setToken($token){
    	$this->oauth_token = $token;
    }
    public function getToken(){
    	return $this->oauth_token;
    }
    public function setSecret($token){
    	$this->oauth_secret = $token;
    }
    public function getSecret(){
    	return $this->oauth_secret;
    }
}
