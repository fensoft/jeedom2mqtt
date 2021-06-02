<?php

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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function jeedom2mqtt_install() {
    foreach (eqLogic::byType('jeedom2mqtt') as $eqLogic) {
        try {
            // in case of deactivation / activation of the plugin, we need to restore listener
            $eqLogic->checkAndSetListener();
        } catch (Exception $e) {
        }
    }
    log::add('jeedom2mqtt', 'info', 'Install done');
}

function jeedom2mqtt_update() {
    foreach (eqLogic::byType('jeedom2mqtt') as $eqLogic) {
        try {
            $eqLogic->save();
        } catch (Exception $e) {
        }
    }
    log::add('jeedom2mqtt', 'info', 'Update done');
}

?>
