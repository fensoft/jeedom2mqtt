
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

$('.pluginAction[data-action=openLocation]').on('click',function(){
    window.open($(this).attr("data-location"), "_blank", null);
});

$('#linkCommand').on('click', function() {
    $('#table_jeedom2mqttSelectedCmds').trigger('update');
  });

$('.eqLogicAttr[data-l1key=configuration][data-l2key=mode]').on('change',function(){
    if($(this).value()=='cron') {
        $('.cronSchedule').show();
    } else {
        $('.cronSchedule').hide();
    }
});

$('.bt_jeedom2mqttAdvanceCmdConfiguration').off('click').on('click', function () {
    $('#md_modal2').dialog({title: "{{Configuration de la commande}}"});
    $('#md_modal2').load('index.php?v=d&modal=cmd.configure&cmd_id=' + $(this).attr('data-id')).dialog('open');
  });

var refreshTimeout;
var timeout_refreshDaemonInfo = null;
function showDaemonInfo(data) {
    switch(data.launchable) {
        case 'ok':
            $('.bt_startDaemon').show();
            $('.daemonLaunchable').empty().append('<span class="label label-success" style="font-size:1em;">{{OK}}</span>');
            break;
        case 'nok':
            $('.bt_startDaemon').hide();
            $('.daemonLaunchable').empty().append('<span class="label label-danger" style="font-size:1em;">{{NOK}}</span> ' + data.message);
            break;
        default:
           $('.daemonLaunchable').empty().append('<span class="label label-warning" style="font-size:1em;">' + data.state + '</span>');
    }
    switch (data.state) {
        case 'ok':
            $('.daemonState').empty().append('<span class="label label-success" style="font-size:1em;">{{OK}}</span>');
            $("#div_broker_daemon").closest('.panel').removeClass('panel-warning').removeClass('panel-danger').addClass('panel-success');
            break;
        case 'pok':
            $('.daemonState').empty().append('<span class="label label-warning" style="font-size:1em;">{{POK}}</span> ' + data.message);
            $("#div_broker_daemon").closest('.panel').removeClass('panel-danger').removeClass('panel-success').addClass('panel-warning');
            break;
        case 'nok':
            $('.daemonState').empty().append('<span class="label label-danger" style="font-size:1em;">{{NOK}}</span> ' + data.message);
            $("#div_broker_daemon").closest('.panel').removeClass('panel-warning').removeClass('panel-success').addClass('panel-danger');
            break;
        default:
            $('.daemonState').empty().append('<span class="label label-warning" style="font-size:1em;">'+data.state+'</span>');
    }
    
    $('.daemonLastLaunch').empty().append(data.last_launch);
    
    if ($("#div_broker_daemon").is(':visible')) {
        clearTimeout(timeout_refreshDaemonInfo);
        timeout_refreshDaemonInfo = setTimeout(refreshDaemonInfo, 5000);
    }
}
function callPluginAjax(_params) {
    $.ajax({
        async: _params.async == undefined ? true : _params.async,
        global: false,
        type: "POST",
        url: "plugins/jeedom2mqtt/core/ajax/jeedom2mqtt.ajax.php",
        data: _params.data,
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) { 
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
            }
            else {
                if (typeof _params.success === 'function') {
                    _params.success(data.result);
                }
            }
        }
    });
}
$.urlParam = function(name){
    var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
    if (results==null) {
       return null;
    }
    return decodeURI(results[1]) || 0;
}
function refreshDaemonInfo() {
    var id = $('.eqLogicAttr[data-l1key=id]').value();
    callPluginAjax({
        data: {
            action: 'getDaemonInfo',
            id: id,
        },
        success: function(data) {
            showDaemonInfo(data);
        }
    });
}
var observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if ($("#brokertab").is(':visible')) {
            refreshDaemonInfo();
        }
    });    
});
observer.observe($("#brokertab")[0], {attributes: true});
$('body').off('jeedom2mqtt::EventState').on('jeedom2mqtt::EventState', function (_event,_options) {
    showDaemonInfo(_options);
});
$('.bt_startDaemon').on('click',function(){
    var id = $('.eqLogicAttr[data-l1key=id]').value();
    clearTimeout(timeout_refreshDaemonInfo);
    callPluginAjax({
        data: {
            action: 'daemonStart',
            id: id,
        },
        success: function(data) {
            refreshDaemonInfo();
        }
    });
});