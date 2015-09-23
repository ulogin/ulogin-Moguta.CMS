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
?>
<div class="widget-table-body">
	<div class="wrapper-ulogin-setting">
		<ul>
			<li>
				<p class="label-text"><?php echo $lang['ULOGINID1'] ?></p>
				<input type="text" name="uloginid1" value="<?php echo $options['uloginid1'] ?>">
				<span class="help-text"><?php echo $lang['ULOGINID1_TEXT']; ?></span>
			</li>
			<li>
				<p class="label-text"><?php echo $lang['ULOGINID2'] ?></p><br/>
				<input type="text" name="uloginid2" value="<?php echo $options['uloginid2'] ?>">
				<span class="help-text"><?php echo $lang['ULOGINID2_TEXT']; ?></span>
			</li>
			<li>
				<button id="ulogin-save" class="base-setting-save button" title="<?php echo $lang['SAVE_MODAL']; ?>">
					<span><?php echo $lang['SAVE']; ?></span>
				</button>
			</li>
		</ul>
	</div>
</div>