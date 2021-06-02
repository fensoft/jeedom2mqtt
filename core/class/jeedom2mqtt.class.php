<?php

require_once __DIR__ . '/../../../../core/php/core.inc.php';

class jeedom2mqtt extends eqLogic {
	public $client = null;
	private static $_depLogFile;
	private static $_depProgressFile;

	public static function getBrokerFromId($id) {
        $broker = jeedom2mqtt::byId($id);
        if (!is_object($broker)) {
            throw new Exception(__('Pas d\'équipement avec l\'id fourni', __FILE__) . ' (id=' . $id . ')');
        } 
        return $broker;
    }

	public function startDaemon($restart = false) {
        $daemon_info = $this->getDaemonInfo();
        if ($daemon_info['launchable'] != 'ok') {
            throw new Exception(__('Le démon n\'est pas démarrable. Veuillez vérifier la configuration', __FILE__));
        }
        
        if ($restart) {
            $this->stopDaemon();
            sleep(1);
        }
        $cron = $this->getDaemonCron();
        if (!is_object($cron)) {
            $cron = new cron();
            $cron->setClass(__CLASS__);
            $cron->setFunction('daemon');
            $cron->setOption(array('id' => $this->getId()));
            $cron->setEnable(1);
            $cron->setDeamon(1);
            $cron->setSchedule('* * * * *');
            //$cron->setTimeout('1440');
            $cron->save();
        }
        $this->log('info', 'démarre le démon');
        $this->setLastDaemonLaunchTime();
        $this->sendDaemonStateEvent();
        $cron->run();
    }

	public function setLastDaemonLaunchTime() {
        return $this->setCache('lastDaemonLaunchTime', date('Y-m-d H:i:s'));
    }

    public function stopDaemon() {
        $this->log('info', 'arrête le démon');
        $cron = $this->getDaemonCron();
        if (is_object($cron)) {
            $cron->halt();
        }

        $this->sendDaemonStateEvent();
    }

	private function sendDaemonStateEvent() {
        event::add('jeedom2mqtt::EventState', $this->getDaemonInfo());
    }

	public static function dependancy_install() {
        log::add('jeedom2mqtt', 'info', 'Installation des dépendances, voir log dédié (' . self::$_depLogFile . ')');
        log::remove(self::$_depLogFile);
        return array(
            'script' => dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . self::$_depProgressFile . ' ' .
            config::byKey('installMosquitto', 'jeedom2mqtt', 1),'log' => log::getPathToLog(self::$_depLogFile));
    }

	public static function dependancy_info() {
        if (! isset(self::$_depLogFile))
            self::$_depLogFile = __CLASS__ . '_dep';
            
		if (! isset(self::$_depProgresFile))
			self::$_depProgressFile = jeedom::getTmpFolder(__CLASS__) . '/progress_dep.txt';
			
		$return = array();
		$return['log'] = log::getPathToLog(self::$_depLogFile);
		$return['progress_file'] = self::$_depProgressFile;
		
		// is lib PHP exists?
		$libphp = extension_loaded('mosquitto');
		
		// build the state status; if nok log debug information
		if ($libphp || true) {
			$return['state'] = 'ok';
		} else {
			$return['state'] = 'nok';
			log::add('jeedom2mqtt', 'debug', 'dependancy_info: NOK');
			log::add('jeedom2mqtt', 'debug', '   * Mosquitto extension loaded: ' . $libphp);
		}
		$return["a"] = "a";
		return $return;
    }

	public function getDaemonInfo() {
        $return = array('message' => '', 'launchable' => 'nok', 'state' => 'nok', 'log' => 'nok');

        // Is the daemon launchable
        $return['launchable'] = 'ok';
        $dependancy_info = plugin::byId('jeedom2mqtt')->dependancy_info();
        if ($dependancy_info['state'] == 'ok') {
            if (!$this->getIsEnable()) {
                $return['launchable'] = 'nok';
                $return['message'] = __("L'équipement est désactivé", __FILE__);
            }
            if (config::byKey('enableCron', 'core', 1, true) == 0) {
                $return['launchable'] = 'nok';
                $return['message'] = __('Les crons et démons sont désactivés', __FILE__);
            }
            if (!jeedom::isStarted()) {
                $return['launchable'] = 'nok';
                $return['message'] = __('Jeedom n\'est pas encore démarré', __FILE__);
            }
        }
        else {
            $return['launchable'] = 'nok';
            if ($dependancy_info['state'] == 'in_progress') {
                $return['message'] = __('Dépendances en cours d\'installation', __FILE__);
            } else {
                $return['message'] = __('Dépendances non installées', __FILE__);
            }
        }

        $return['log'] = $this->getDaemonLogFile();
        $return['last_launch'] = $this->getLastDaemonLaunchTime();      
        $return['state'] = $this->getDaemonState();
        if ($dependancy_info['state'] == 'ok') {
            if ($return['state'] == "nok" && $return['message'] == '')
                $return['message'] = __('Le démon est arrêté', __FILE__);
        }

        return $return;
    }

	public function getDaemonState() {
        $cron = $this->getDaemonCron();
        if (is_object($cron) && $cron->running()) {
            $return = "ok";
        }
        else
            $return = "nok";
        
        return $return;
    }

	public function getDaemonCron() {
        return cron::byClassAndFunction('jeedom2mqtt', 'daemon', array('id' => $this->getId()));
    }

	public static function daemon($option) {
		while (true) {
			$broker = self::getBrokerFromId($option['id']);
        	$broker->log('debug', 'daemon starts, pid is ' . getmypid());
			sleep(15);
			$broker->log('debug', 'daemon stopped');
		}
	}

	public function log($level, $msg) {
        log::add($this->getDaemonLogFile(), $level, $msg);
    }

	public function getDaemonLogFile($force=false) {
        return $this->_log = 'jeedom2mqtt_' . str_replace(' ', '_', $this->getName());
    }

	public function getLastDaemonLaunchTime() {
        return $this->getCache('lastDaemonLaunchTime', __('Inconnue', __FILE__));
    }

    private function getMosquittoClient($id = '') {
        // https://github.com/mqtt/mqtt.github.io/wiki/mosquitto-php
        $client = ($id == '') ? new Mosquitto\Client() : new Mosquitto\Client($id);
        if ($this->getConfiguration('user') != '') {
            $client->setCredentials($this->getConfiguration('user'), $this->getConfiguration('password'));
        }
        $client->setReconnectDelay(1, 16, true);
		$client->connect($this->getConfiguration('serverIP'), $this->getConfiguration('serverPort', 1883), 60);
        return $client;
    }

	private function publish($cmd, $value) {
		if ($this->client == null)
			$this->client = $this->getMosquittoClient();
		$this->client->publish($this->cmdToTopic($cmd), $value, 0, false);
	}

	private function cmdToTopic($cmd) {
		$topicTemplate = $this->getConfiguration('topicTemplate', "jeedom/#plugin#/#objectName#/#eqLogicName#/#cmdName#");
		$topicTemplate = str_replace("#objectName#", $cmd->getEqLogic()->getObject()->getName(), $topicTemplate);
		$topicTemplate = str_replace("#eqLogicName#", $cmd->getEqLogic()->getName(), $topicTemplate);
		$topicTemplate = str_replace("#cmdName#", $cmd->getName(), $topicTemplate);
		$topicTemplate = str_replace("#plugin#", $cmd->getEqLogic()->getEqType_name(), $topicTemplate);
		return $topicTemplate;
	}

	public static function trigger($_option) {
		if (!is_numeric($_option['event_id'])) return;
		$cmd = cmd::byId($_option['event_id']);
		if (!is_object($cmd)) return;

		log::add(__CLASS__, 'info', "trigger started for {$cmd->getHumanName()}={$_option['value']}");

		$jeedom2mqttEqLogic = eqLogic::byId($_option['id']);
		if (is_object($jeedom2mqttEqLogic) && $jeedom2mqttEqLogic->getIsEnable() == 1) {
			try {
				$jeedom2mqttEqLogic->publish($cmd, $_option['value']);
			} catch (\Throwable $th) {
				log::add(__CLASS__, 'error', $th->getMessage());
			}
		} else {
			log::add(__CLASS__, 'error', 'no active Jeedom2mqtt eqLogic found with id :'. $_option['id']);
		}
	}

	public static function cron() {
		try {
			foreach (eqLogic::byType(__CLASS__, true) as $eqLogic) {
				if ($eqLogic->getConfiguration('mode', 'cron') != 'cron') continue;
				$schedule = $eqLogic->getConfiguration('cronSchedule', '* * * * *');
				if ($schedule == '')  continue;
				try {
					$cron = new Cron\CronExpression($schedule, new Cron\FieldFactory);
					if ($cron->isDue()) {
						$eqLogic->sendAllMeasurements();
					}
				} catch (\Throwable $th) {
					log::add(__CLASS__, 'error', __('Expression cron non valide pour ', __FILE__) . $eqLogic->getHumanName() . ' : ' . $schedule);
				}
			}
		} catch (\Throwable $th) {
		}
	}

	public function sendAllMeasurements() {
		log::add(__CLASS__, 'debug', "sendAllMeasurements for {$this->getName()}");
		try {
			$points = array();

			foreach ($this->getConfiguration('selectedCmds') as $cmd_id => $isActive) {
				if ($isActive == 1) {
					if (!is_numeric($cmd_id)) continue;
					$cmd = cmd::byId($cmd_id);
					if (!is_object($cmd)) continue;
					$this->publish($cmd, $cmd->execCmd());
				}
			}
		} catch (\Throwable $th) {
			log::add(__CLASS__, 'error', $th->getMessage());
		}
	}

	private function getListener() {
		return listener::byClassAndFunction(__CLASS__, 'trigger', array('id' => $this->getId()));
	}

	private function removeListener() {
		$listener = $this->getListener();
		if (is_object($listener)) {
			$listener->remove();
		}
	}

	public function checkAndSetListener() {
		log::add(__CLASS__, 'debug', "checkAndSetListener for {$this->getName()}");

		if ($this->getIsEnable() == 0 || $this->getConfiguration('mode', 'cron') == 'cron') {
			$this->removeListener();
			return;
		}

		$listener = $this->getListener();
		if (!is_object($listener)) {
			$listener = new listener();
			$listener->setClass(__CLASS__);
			$listener->setFunction('trigger');
			$listener->setOption(array('id' => $this->getId()));
		}
		$listener->emptyEvent();

		$eventAdded = false;
		foreach ($this->getConfiguration('selectedCmds') as $cmd_id => $isActive) {
			if ($isActive == 1) {
				if (!is_numeric($cmd_id)) continue;
				$cmd = cmd::byId($cmd_id);
				if (!is_object($cmd)) continue;
				$listener->addEvent($cmd->getId());
				$eventAdded = true;
			}
		}

		if ($eventAdded) {
			$listener->save();
		} else {
			$listener->remove();
		}
	}

	public function preInsert() {
		$this->setConfiguration('mode', 'cron');
	}

	public function postInsert() {

	}

	public function preSave() {
		$totalSelectedCmdCount = 0;
		$selectedCmds = $this->getConfiguration('selectedCmds');
		$activeCmds = array();
		foreach ($selectedCmds as $cmd_id => $isActive) {
			if ($isActive == 1) {
				++$totalSelectedCmdCount;
				$activeCmds[$cmd_id] = 1;
			}
		}
		$this->setConfiguration('selectedCmds', $activeCmds);
		$this->setConfiguration('totalSelectedCmdCount', $totalSelectedCmdCount);

		$cmdsInConfig = $this->getConfiguration('topic');
		$cmdsToSave = array();
		foreach ($cmdsInConfig as $cmd_id => $value) {
			if ($value != '') {
				$cmdsToSave[$cmd_id] = $value;
			}
		}
		$this->setConfiguration('topic', $cmdsToSave);
	}

	public function postSave() {

	}

	public function preUpdate() {

	}

	public function postUpdate() {
		$this->checkAndSetListener();
	}

	public function preRemove() {
		$this->removeListener();
	}

	public function postRemove() {

	}
}

class jeedom2mqttCmd extends cmd {

}
