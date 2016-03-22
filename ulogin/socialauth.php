<?php
/**
 * Plugin Name: uLogin - виджет авторизации через социальные сети
 * Plugin URI:  http://ulogin.ru/
 * Description: uLogin — это инструмент, который позволяет пользователям получить единый доступ к различным
 * Интернет-сервисам без необходимости повторной регистрации, а владельцам сайтов — получить дополнительный приток
 * клиентов из социальных сетей и популярных порталов (Google, Яндекс, Mail.ru, ВКонтакте, Facebook и др.)
 * Version:     2.0.0
 * Author:      uLogin
 * Author URI:  http://ulogin.ru/
 * License:     GPL2
 */
if(!isset($_POST['token']))
	return;  // не был получен токен uLogin
$user = uloginGetUserFromToken($_POST['token']);
if(!$user) {
	die('Ошибка работы uLogin:Не удалось получить данные о пользователе с помощью токена.');
}
$u_user = json_decode($user, true);
$check = uloginCheckTokenError($u_user);
if(!$check) {
	return false;
}
$user_id = getUserIdByIdentity($u_user['identity']);
if(!empty($user_id)) {
	$d = USER::getUserById($user_id);
	if($user_id > 0 && $d->id > 0) {
		uloginCheckUserId($user_id);
	} else {
		$user_id = ulogin_registration_user($u_user, 1);
	}
} else $user_id = ulogin_registration_user($u_user);
if($user_id > 0) {
	$userData = USER::getUserById($user_id);
	$userData->hash = md5($userData->email.$userData->date_add.$_SERVER['REMOTE_ADDR']);
	$_SESSION['userAuthDomain'] = $_SERVER['SERVER_NAME'];
	$_SESSION['user'] = $userData;
	$_SESSION['loginAttempt']='';
}
header('Location: ' . urldecode($_GET['backurl']));
/**
 * Обменивает токен на пользовательские данные
 * @param bool $token
 * @return bool|mixed|string
 */
function uloginGetUserFromToken($token = false) {
	$response = false;
	if($token) {
		$data = array('cms' => 'moguta', 'version' => VER);
		$request = 'http://ulogin.ru/token.php?token=' . $token . '&host=' . $_SERVER['HTTP_HOST'] . '&data=' . base64_encode(json_encode($data));
		if(in_array('curl', get_loaded_extensions())) {
			$c = curl_init($request);
			curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
			$response = curl_exec($c);
			curl_close($c);
		} elseif(function_exists('file_get_contents') && ini_get('allow_url_fopen'))
			$response = file_get_contents($request);
	}

	return $response;
}

/**
 * Проверка пользовательских данных, полученных по токену
 * @param $u_user - пользовательские данные
 * @return bool
 */
function uloginCheckTokenError($u_user) {
	if(!is_array($u_user)) {
		die('Ошибка работы uLogin. Данные о пользователе содержат неверный формат');
	}
	if(isset($u_user['error'])) {
		$strpos = strpos($u_user['error'], 'host is not');
		if($strpos) {
			die('Ошибка работы uLogin. Адрес хоста не совпадает с оригиналом');
		}
		switch($u_user['error']) {
			case 'token expired':
				die('Ошибка работы uLogin. Время жизни токена истекло');
			case 'invalid token':
				die('Ошибка работы uLogin. Неверный токен');
			default:
				die(('Ошибка работы uLogin. ') . $u_user['error'] . '.');
		}
	}
	if(!isset($u_user['identity'])) {
		die('Ошибка работы uLogin. В возвращаемых данных отсутствует переменная
			 "identity"');
	}

	return true;
}

function getUserIdByIdentity($identity) {
	$res = DB::query('SELECT user_id FROM ' . PREFIX . 'ulogin WHERE identity = ' . DB::quote($identity));
	if($row = DB::fetchObject($res))
		return $row->user_id;

	return false;
}

/**
 * @param $user_id
 * @return bool
 */
function uloginCheckUserId($user_id) {
	$current_user = USER::isAuth() ? USER::getThis() : 0;
	$current_user = isset($current_user->id) ? $current_user->id : 0;
	if(($current_user > 0) && ($user_id > 0) && ($current_user != $user_id)) {
		die('Данный аккаунт привязан к другому пользователю. Вы не можете использовать этот аккаунт.');
	}

	return true;
}

/**
 * Регистрация на сайте и в таблице uLogin
 * @param Array $u_user - данные о пользователе, полученные от uLogin
 * @param int $in_db - при значении 1 необходимо переписать данные в таблице uLogin
 * @return bool|int|Error
 */
function ulogin_registration_user($u_user, $in_db = 0) {
	if(!isset($u_user['email'])) {
		die("Через данную форму выполнить вход/регистрацию невозможно. </br>" . "Сообщиете администратору сайта о следующей ошибке: </br></br>" . "Необходимо указать <b>email</b> в возвращаемых полях <b>uLogin</b>");
	}
	$u_user['network'] = isset($u_user['network']) ? $u_user['network'] : '';
	$u_user['phone'] = isset($u_user['phone']) ? $u_user['phone'] : '';
	// данные о пользователе есть в ulogin_table, но отсутствуют в Базе
	if($in_db == 1) {
		DB::query("DELETE FROM " . PREFIX . "ulogin   WHERE `identity` = " . DB::quote($u_user['identity']));
	}
	$user_id = USER::getUserInfoByEmail($u_user['email']);
	$user_id = $user_id->id;
	// $check_m_user == true -> есть пользователь с таким email
	$check_m_user = $user_id > 0 ? true : false;
	$current_user = USER::isAuth() ? USER::getThis() : 0;
	// $isLoggedIn == true -> ползователь онлайн
	$isLoggedIn = (isset($current_user->id) && $current_user->id > 0) ? true : false;
	if(!$check_m_user && !$isLoggedIn) { // отсутствует пользователь с таким email в базе -> регистрация
		$date = explode('.', $u_user['bdate']);
		$insert_user = array('pass' => md5(microtime(true)), 'email' => $u_user['email'], 'role' => 2, 'name' => $u_user['first_name'], 'sname' => $u_user['last_name'], 'address' => '', 'phone' => $u_user['phone'], 'birthday' => $date['2'] . '-' . $date['1'] . '-' . $date['0'], 'activity' => 1);
		$user_id = USER::add($insert_user);
		$user_id = DB::insertId();
		$userData = USER::getUserById($user_id);
		$res = DB::query("INSERT INTO " . PREFIX . "ulogin (user_id, identity, network)
		VALUES (" . DB::quote($user_id) . "," . DB::quote($u_user['identity']) . "," . DB::quote($u_user['network']) . ")");

		return $userData->id;
	} else { // существует пользователь с таким email или это текущий пользователь
		if(!isset($u_user["verified_email"]) || intval($u_user["verified_email"]) != 1) {
			die('<head></head><body><script src="//ulogin.ru/js/ulogin.js"  type="text/javascript"></script><script type="text/javascript">uLogin.mergeAccounts("' . $_POST['token'] . '")</script>' . ("Электронный адрес данного аккаунта совпадает с электронным адресом существующего пользователя. <br>Требуется подтверждение на владение указанным email.</br></br>") . ("Подтверждение аккаунта") . "</body>");
		}
		if(intval($u_user["verified_email"]) == 1) {
			$user_id = $isLoggedIn ? $current_user->id : $user_id;
			$other_u = DB::query("SELECT identity FROM " . PREFIX . "ulogin where `user_id` = " . DB::quote($user_id));
			$other_u = DB::fetchAssoc($other_u);
			if($other_u) {
				if(!$isLoggedIn && !isset($u_user['merge_account'])) {
					die('<head></head><body><script src="//ulogin.ru/js/ulogin.js"  type="text/javascript"></script><script type="text/javascript">uLogin.mergeAccounts("' . $_POST['token'] . '","' . $other_u['identity'] . '")</script>' . ("С данным аккаунтом уже связаны данные из другой социальной сети. <br>Требуется привязка новой учётной записи социальной сети к этому аккаунту.<br/>") . ("Синхронизация аккаунтов") . "</body>");
				}
			}
			DB::query("INSERT INTO " . PREFIX . "ulogin (user_id, identity, network)
			VALUES (" . DB::quote($user_id) . "," . DB::quote($u_user['identity']) . "," . DB::quote($u_user['network']) . ")");

			return $user_id;
		}
	}

	return false;
}