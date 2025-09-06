<?
include __DIR__ . "/../libs/frontend.php";
include __DIR__ . "/../libs/backend.php";

class VirtualDevice extends IPSModule {
	// Overrides the internal IPS_Create($id) function
	public function Create(): void {
	// Don't delete this line
		parent::Create();

		$this->RegisterTimer("TurnOffTimer", 0, "VirtDev_TimerIsOver($this->InstanceID);");
		$this->RegisterTimer("UpdateSonnenschutz", 0, "VirtDev_UpdateSonnenschutz($this->InstanceID);");

		// if a settings Instance exists, use that as default
		$settings = IPS_GetInstanceListByModuleID("{FE5AAE00-92DF-96C3-7435-2CAEBCF1CB62}");
		if (count($settings) > 0) {
			$vals = VirtDevSettings_GetSettings($settings[0]);
		} else {
			$vals = array (
				"TermiteMaster" => 0,
				"OutsideTempSensor" => 0,
				"Location" => '{"latitude":0.0, "longitude": 0.0}'
			);
		}

		$this->RegisterPropertyString("Frontend", "Dummy");
		$this->RegisterPropertyBoolean("UseSettings", true);
		$this->RegisterPropertyInteger("TermiteMaster", $vals["TermiteMaster"]);
		$this->RegisterPropertyInteger("OutsideTempSensor", $vals["OutsideTempSensor"]);
		$this->RegisterPropertyInteger("MotionSensor", 0);
		$this->RegisterPropertyString("Location", $vals["Location"]);
		$this->RegisterPropertyString("SunshineStart", "10:00");
		$this->RegisterPropertyString("SunshineEnd", "17:00");
		$this->RegisterPropertyString("Backend", "Dummy");
		$this->RegisterPropertyInteger("HW_Variable", 0);
		$this->RegisterPropertyBoolean("Inverted", false);

		$this->RegisterVariableString("Value", "WERT");
		$this->EnableAction("Value");

		$this->RegisterAttributeString("Subvalue", "");	// e.g. When Wochenplan to store, what the current value is, to prepare it vor value change
	}

	// dynamic configurationform
	
	public function GetConfigurationForm () : string {
		$res = '{ "elements": [
			{"type": "Label", 
				"caption": "Nach dem Übernehemen der Einstellungen können neue Optionen erscheinen!"
			},
			{ "type": "RowLayout", "items": [ 
			{ "type": "ColumnLayout", "items": [
			{"type": "Select", "name": "Frontend", "caption": "Geräte Typ",
				"options": [
					{ "caption": "Dummy", "value": "Dummy" },
					{ "caption": "Kochplatte", "value": "ko" },
					{ "caption": "Backofen", "value": "bo" },
					{ "caption": "Fensterkipper", "value": "fk" },
					{ "caption": "Akku", "value": "ak" },
					{ "caption": "Dunstabzugshaube", "value": "du" },
					{ "caption": "Lüftung", "value": "lu" },
					{ "caption": "Dimmbares Licht", "value": "dl" },
					{ "caption": "Schaltbares Licht", "value": "sl" },
					{ "caption": "RGB-Licht", "value": "bl" },
					{ "caption": "Heizung", "value": "hz" },
					{ "caption": "Steckdose", "value": "st" },
					{ "caption": "Rolladen", "value": "ra" },
					{ "caption": "PC", "value": "pc" },
					{ "caption": "Soundsignal", "value": "as" },
					{ "caption": "Lichtsignal", "value": "ls" },
					{ "caption": "Statusanzeige", "value": "sa" },
					{ "caption": "Torantrieb", "value": "ta" },
					{ "caption": "Markise", "value": "ma" },
					{ "caption": "Volant", "value": "vo" },
					{ "caption": "Wäscheständer", "value": "ws" },
					{ "caption": "Nebler", "value": "ne" }
				]
			},
			{ "type": "CheckBox", "name": "UseSettings", "caption": "Nutze VirtualDeviceSettings" }
			 ' . $this->GetFrontend()->GetFormPart() .' 
			]} , { "type": "ColumnLayout", "items": [
			{"type": "Select", "name": "Backend", "caption": "Backend Typ",
				"options": [
					{ "caption": "Dummy", "value": "Dummy"},
					{ "caption": "IPS Boolean", "value": "IPS_Boolean"},
					{ "caption": "IPS Float", "value": "IPS_Float"},
					{ "caption": "HomeMatic generisch Binär", "value": "HM_Boolean"},
					{ "caption": "HomeMatic generisch Float", "value": "HM_Float"},
					{ "caption": "HomeMatic Dimmer", "value": "HM_Dimmer"},
					{ "caption": "HomeMatic Rolladen", "value": "HM_Shutter"},
					{ "caption": "HomeMatic Fensterkipper", "value": "HM_Window"},
					{ "caption": "HomeMatic Heizkörperthermostat", "value": "HM_Heater"},
					{ "caption": "HomeMatic Wandthermostat", "value": "HM_Thermostate"}
				]
			} ' . $this->GetBackend()->GetFormPart() .']} ]} ]}';
		return $res;
	}

	// Overwrites the internal IPS_ApplyChanges($id) function
	public function ApplyChanges(): void {
		if ($this->ReadPropertyBoolean("UseSettings")) {
			// if a settings Instance exists, use that
			$settings = IPS_GetInstanceListByModuleID("{FE5AAE00-92DF-96C3-7435-2CAEBCF1CB62}");
			if (count($settings) > 0) {
				$vals = VirtDevSettings_GetSettings($settings[0]);
				foreach($vals as $key => $val) {
					IPS_SetProperty($this->InstanceID, $key, $val);	// will be applied, as we are at the beginning of the apply function
				}
			}
		}
		// Don't delete this line
		parent::ApplyChanges();
		// add or remove variables according to frontend
		$fe = $this->GetFrontend();
		$this->MaintainVariable("BooleanRepr", "Booean Wert", 0, "", 0, $fe::BooleanRepr);
		if ($fe::BooleanRepr) {
			$this->EnableAction("BooleanRepr");
		}
		$this->MaintainVariable("IntegerRepr", "Integer Wert", 1, "", 0, $fe::IntegerRepr);
		if ($fe::IntegerRepr) {
			$this->EnableAction("IntegerRepr");
		}
		$this->MaintainVariable("FloatRepr", "Float Wert", 2, "", 0, $fe::FloatRepr);
		if ($fe::FloatRepr) {
			$this->EnableAction("FloatRepr");
		}

	}

	public function RequestAction ($Ident, $Value) : void {
		$fe = $this->GetFrontend();
		$fe->prepareValueChange();
		switch ($Ident) {
		case "Value":
			$fe->set($Value);
			break;
		case"BooleanRepr":
			$fe->setBoolean($Value);
			break;
		case "IntegerRepr":
			$fe->setInteger($Value);
			break;
		case "FloatRepr":
			$fe->setFloat($Value);
			break;
		default:
			throw new Exception("Requested action for unknown ident ". $Ident);
		}
	}

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data) : void {
		// only the frontend expects messages, so pass them on
		$this->GetFrontend()->MessageSink($TimeStamp, $SenderID, $Message, $Data);
	}

	public function GetFrontend() : Frontend {
		$fe = $this->ReadPropertyString("Frontend");
		if ($fe == 'Dummy') {
			return new Frontend($this);
		} else {
			$fe = strtoupper($fe);
			$name = "Frontend_$fe";	
			return new $name($this);
		}
	}

	public function GetBackend() : Backend {
		$be = $this->ReadPropertyString("Backend");
		if ($be == 'Dummy') {
			return new Backend($this);
		} else {
			$name = "Backend_$be";	
			return new $name($this);
		}
	}

	public function UpdateGlobalSettings() : void {
		if ($this->ReadPropertyBoolean("UseSettings")) {
			$this->ApplyChanges();	// loads values anyways
		}
	}

	public function TimerIsOver() : void {
		if (strtolower(explode(":", $this->GetValue("Value"))[0]) == "ausschaltverzoegerung") {
			$this->SetTimerInterval("TurnOffTimer", 0);	// turn off timer
			$this->GetFrontend()->set("AUS");
		} elseif (strtolower(explode(":", $this->ReadAttributeString("Subvalue"))[0]) == "ausschaltverzoegerung") {
			$this->SetTimerInterval("TurnOffTimer", 0);	// turn off timer
			$this->GetFrontend()->set("AUS", false);
		} else {
			throw new Exception("Turn off timer triggert, but state is different");
		}
	}

	public function UpdateSonnenschutz() : void {
		$this->GetFrontend()->update_sonnenschutz();
	}
}

