/**
 * Plugin Name: uLogin - виджет авторизации через социальные сети
 * Plugin URI:  http://ulogin.ru/
 * Description: uLogin — это инструмент, который позволяет пользователям получить единый доступ к различным Интернет-сервисам без необходимости повторной регистрации, а владельцам сайтов — получить дополнительный приток клиентов из социальных сетей и популярных порталов (Google, Яндекс, Mail.ru, ВКонтакте, Facebook и др.)
 * Version:     2.0.0
 * Author:      uLogin
 * Author URI:  http://ulogin.ru/
 * License:     GPL2
 */

var uloginSyncModule = (function () {

    return {
        lang: [], // локаль плагина
        init: function () {
            $(document).ready(function () {
                var uloginNetwork = $('#ulogin_accounts').find('.ulogin_network');
                uloginNetwork.click(function () {
                    var network = $(this).attr('data-ulogin-network');
                    var identity = $(this).attr('data-ulogin-identity');
                    uloginDeleteAccount(network, identity);
                });
            });
            function uloginDeleteAccount(network, identity) {
                var query = $.ajax({
                    type: "POST",
                    url: mgBaseDir + "/ajaxrequest",
                    dataType: 'json',
                    data: {
                        mguniqueurl: "action/uloginDeleteAccount", // действия для выполнения на сервере
                        pluginHandler: 'ulogin',
                        data: {
                            identity: identity,
                            network: network
                        }
                    },
                    error: function (data) {
                        alert('Error');
                    },
                    success: function (data) {
                        if (data.answerType == 'error') {
                            alert(data.msg);
                        }
                        if (data.answerType == 'ok') {
                            var accounts = $('#ulogin_accounts');
                            nw = accounts.find('[data-ulogin-network=' + network + ']');
                            if (nw.length > 0) nw.hide();
                            alert(data.msg);
                        }
                    }
                });

                return false;
            }
        }
    }
})();

uloginSyncModule.init();