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
 * Класс Pactioner наследник стандарного Actioner
 * Предназначен для выполнения действий,  AJAX запросов плагина
 */
class Pactioner extends Actioner {

	public function saveBaseOption() {
		$this->messageSucces = $this->lang['SAVE_BASE'];
		$this->messageError = $this->lang['NOT_SAVE'];
		$data = $_POST['data'];
		if(!empty($data)) {
			MG::setOption(array('option' => 'uLoginSettings', 'value' => addslashes(serialize($_POST['data']))));

			return true;
		}

		return false;
	}

	public function uloginDeleteAccount() {
		if(isset($_POST['data'])) {
			try {
				$udata = $_POST['data'];
				DB::query("DELETE FROM " . PREFIX . "ulogin WHERE `identity`='" . $udata['identity'] . "'");
				echo json_encode(array('answerType' => 'ok', 'msg' => "Удаление привязки аккаунта " . $udata['network'] . " успешно выполнено"));
				unset($udata);
				exit;
			} catch(Exception $e) {
				echo json_encode(array('answerType' => 'error', 'msg' => "Ошибка при удалении аккаунта \n Exception: " . $e->getMessage()));
				unset($udata);
				exit;
			}
		}
	}
}

