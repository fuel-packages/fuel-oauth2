<?php 
/**
 * OAuth2 Server Model
 * 
 * @package    FuelPHP/OAuth2
 * @category   Server Model
 * @author     Phil Sturgeon
 * @copyright  (c) 2012 HappyNinjas Ltd
 */

namespace OAuth2;

abstract class Model_Server
{
	abstract public function get_client(array $where);

	abstract public function get_session(array $where);
	abstract public function new_session(array $values, array $scopes);
	abstract public function update_session(array $where, array $values);
	abstract public function delete_session(array $where);

	abstract public function get_token_from_session($session_id);
	abstract public function create_access_token($session_id);
	abstract public function has_user_authenicated_client($client_id, $user_id);
	abstract public function has_scope($access_token, $scope);
}