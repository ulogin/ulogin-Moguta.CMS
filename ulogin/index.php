<?php
/*
  Plugin Name: uLogin
  Description: uLogin.ru - виджет авторизации через социальные сети. Шорт-код [ulogin]
  Author: <a style="text-decoration: none; color:black" href="//ulogin.ru" target="_blank">uLogin Team</a>
  Version: 2.0
 */
new ULoginAuth;

class ULoginAuth {

	private static $lang = array(); // массив с переводом плагина
	private static $pluginName = 'ulogin'; // название плагина (соответствует названию папки)
	private static $path = ''; //путь до файлов плагина

	public function __construct() {
		mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin')); //Инициализация  метода при нажатии на кнопку настроект плагина
		mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate')); //Инициализация  метода при активации
		mgDeactivateThisPlugin(__FILE__, array(__CLASS__, 'deactivate')); //Инициализация  метода при деактивации
		mgAddShortcode('ulogin', array(__CLASS__, 'handleShortCode')); // Инициализация шорткода [ulogin] - доступен в любом HTML коде движка.
		mgAddShortcode('sync_ulogin', array(__CLASS__, 'handleShortCodeSync')); // Инициализация шорткода [sync_ulogin] - доступен в любом HTML коде движка.
		self::$pluginName = PM::getFolderPlugin(__FILE__);
		self::$lang = PM::plugLocales(self::$pluginName);
		self::$path = PLUGIN_DIR . self::$pluginName;
		$meta = '';
		if(!URL::isSection('mg-admin')) {
			$meta .= '<script src="//ulogin.ru/js/ulogin.js"></script>';
			$meta .= '<script type="text/javascript" src="' . SITE . '/' . self::$path . '/js/ajax.js"></script>';
		}
		$meta .= '<link href="//ulogin.ru/css/providers.css" rel="stylesheet" type="text/css">';
		mgAddMeta($meta);
	}

	/**
	 * Метод выполняющийся при активации палагина
	 */
	static function activate() {
		copy('mg-plugins/ulogin/socialauth.php', 'mg-pages/socialauth.php');
		self::createDateBase();
	}

	/**
	 * Создает таблицу плагина в БД
	 */
	static function createDateBase() {
		// Запрос для проверки, был ли плагин установлен ранее.
		DB::query("CREATE TABLE IF NOT EXISTS " . PREFIX . self::$pluginName . " (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Порядковый номер',
		    `user_id` int(10) unsigned NOT NULL COMMENT 'Номер Пользователя',
            `identity` varchar(255)  NOT NULL COMMENT 'Уникальный идентификатор Пользователя',
            `network` varchar(255)  NOT NULL COMMENT 'Уникальный идентификатор Пользователя',
            PRIMARY KEY (id)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");
	}

	/**
	 * Метод выполняющийся при деактивации палагина
	 */
	static function deactivate() {
		unlink('mg-pages/socialauth.php');
	}

	/**
	 * Обработчик шотркода вида [ulogin]
	 * выполняется когда при генерации страницы встречается [ulogin]
	 */
	static function handleShortCode() {
		if(!URL::isSection('mg-admin')) {
			$option = MG::getSetting('uLoginSettings');
		} else {
			$option = MG::getOption('uLoginSettings');
		}
		$option = stripslashes($option);
		$options = unserialize($option);
		if($options['widget'] == '') {
			$html = ULoginAuth::getPanelCode(0);
		} else {
			$html = $options['widget'];
		}
		if(USER::isAuth())
			$html = '';

		return $html;
	}

	/**
	 * Обработчик шотркода вида [sync_ulogin]
	 * выполняется когда при генерации страницы встречается [sync_ulogin]
	 */
	static function handleShortCodeSync() {
		if(!URL::isSection('mg-admin')) {
			$option = MG::getSetting('uLoginSettings');
		} else {
			$option = MG::getOption('uLoginSettings');
		}
		$option = stripslashes($option);
		$options = unserialize($option);
		if($options['widget'] == '') {
			$html = ULoginAuth::getSyncPanelCode();
		} else {
			$html = $options['widget'];
		}
		if(!USER::isAuth())
			$html = '';

		return $html;
	}

	/**
	 * Выводит страницу настроек плагина в админке
	 */
	static function pageSettingsPlugin() {
		$lang = self::$lang;
		$pluginName = self::$pluginName;
		self::preparePageSettings();
		echo '<script type="text/javascript" src="' . SITE . '/' . self::$path . '/js/setting_script.js"></script>';
		if(!URL::isSection('mg-admin')) {
			$option = MG::getSetting('uLoginSettings');
		} else {
			$option = MG::getOption('uLoginSettings');
		}
		$option = stripslashes($option);
		$options = unserialize($option);
		include('pageplugin.php');
	}

	/**
	 * Метод выполняющийся перед генерацией страницы настроек плагина
	 */
	static function preparePageSettings() {
		echo '<link rel="stylesheet" href="' . SITE . '/' . self::$path . '/css/style.css" type="text/css" />';
	}

	/**
	 * Возвращает текущий url
	 */
	function ulogin_get_current_page_url() {
		$pageURL = 'http';
		if(isset($_SERVER["HTTPS"])) {
			if($_SERVER["HTTPS"] == "on") {
				$pageURL .= "s";
			}
		}
		$pageURL .= "://";
		if($_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
		} else {
			$pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		}

		return urlencode($pageURL);
	}

	static function getPanelCode($place = 0) {
		/*
		 * Выводит в форму html для генерации виджета
		 */
		$redirect_uri = urlencode(SITE . '/socialauth?backurl=' . urlencode(ULoginAuth::ulogin_get_current_page_url()));
		$ulogin_default_options = array();
		$ulogin_default_options['display'] = 'small';
		$ulogin_default_options['providers'] = 'vkontakte,odnoklassniki,mailru,facebook';
		$ulogin_default_options['fields'] = 'first_name,last_name,email,photo,photo_big';
		$ulogin_default_options['optional'] = 'sex,bdate,country,city';
		$ulogin_default_options['hidden'] = 'other';
		$ulogin_options = array();
		if(!URL::isSection('mg-admin')) {
			$option = MG::getSetting('uLoginSettings');
		} else {
			$option = MG::getOption('uLoginSettings');
		}
		$option = stripslashes($option);
		$options = unserialize($option);
		$ulogin_options['ulogin_id1'] = $options['uloginid1'];
		$ulogin_options['ulogin_id2'] = $options['uloginid2'];
		$default_panel = false;
		switch($place) {
			case 0:
				$ulogin_id = $ulogin_options['ulogin_id1'];
				break;
			case 1:
				$ulogin_id = $ulogin_options['ulogin_id2'];
				break;
			default:
				$ulogin_id = $ulogin_options['ulogin_id1'];
		}
		if(empty($ulogin_id)) {
			$ul_options = $ulogin_default_options;
			$default_panel = true;
		}
		$panel = '';
		$panel .= '<div class="ulogin_panel"';
		if($default_panel) {
			$ul_options['redirect_uri'] = $redirect_uri;
			unset($ul_options['label']);
			$x_ulogin_params = '';
			foreach($ul_options as $key => $value)
				$x_ulogin_params .= $key . '=' . $value . ';';
			if($ul_options['display'] != 'window')
				$panel .= ' data-ulogin="' . $x_ulogin_params . '"></div>'; else
				$panel .= ' data-ulogin="' . $x_ulogin_params . '" href="#"><img src="https://ulogin.ru/img/button.png" width=187 height=30 alt="МультиВход"/></div>';
		} else
			$panel .= ' data-uloginid="' . $ulogin_id . '" data-ulogin="redirect_uri=' . $redirect_uri . '"></div>';
		$panel = '<div class="ulogin_block place' . $place . '">' . $panel . '</div><div style="clear:both"></div>';

		return $panel;
	}

	/**
	 * Вывод списка аккаунтов пользователя
	 * @param int $user_id - ID пользователя (значение по умолчанию = текущий пользователь)
	 * @return string
	 */
	static function getSyncPanelCode($user_id = 0) {
		$current_user = USER::isAuth() ? USER::getThis() : 0;
		$current_user = isset($current_user->id) ? $current_user->id : 0;
		$user_id = empty($user_id) ? $current_user : $user_id;
		if(empty($user_id))
			return '';
		$res = DB::query("SELECT * FROM " . PREFIX . "ulogin WHERE user_id = " . DB::quote($user_id));
		foreach($res as $network) {
			$networks[] = $network;
		}
		$output = '
			<style>
			    .big_provider {
			        display: inline-block;
			        margin-right: 10px;
			    }
			</style>
			<p class="change-pass-title">' . self::$lang['ULOGIN_SYNC'] . '</p>' . self::getPanelCode(1) . '<p>' . self::$lang['ULOGIN_SYNC_HELP'] . '</p>
            <p class="change-pass-title">' . self::$lang['ULOGIN_SYNC_LIST'] . '</p>';

		$output .= '<div id="ulogin_accounts">';
		foreach($networks as $network) {
			if($network['user_id'] = $user_id)
				$output .= "<div data-ulogin-network='{$network['network']}'  data-ulogin-identity='{$network['identity']}' class='ulogin_network big_provider {$network['network']}_big'></div>";
		}
		$output .= '</div>
		<p>' . self::$lang['ULOGIN_SYNC_DELETE'] . '</p>';

		return $output;

		return '';
	}
}