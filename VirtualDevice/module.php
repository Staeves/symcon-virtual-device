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

		$this->RegisterPropertyString("Frontend", "Dummy");
		$this->RegisterPropertyInteger("TermiteMaster", 0);
		$this->RegisterPropertyInteger("OutsideTempSensor", 0);
		$this->RegisterPropertyInteger("MotionSensor", 0);
		$this->RegisterPropertyFloat("Latitude", 0.0);
		$this->RegisterPropertyFloat("Longitude", 0.0);
		$this->RegisterPropertyString("SunshineStart", "10:00");
		$this->RegisterPropertyString("SunshineEnd", "17:00");
		$this->RegisterPropertyString("Backend", "Dummy");
		$this->RegisterPropertyInteger("HW_Variable", 0);

		$this->RegisterVariableString("Value", "WERT");
		$this->EnableAction("Value");

		$this->RegisterAttributeString("Subvalue", "");	// e.g. When Wochenplan to store, what the current value is, to prepare it vor value change
	}

	// dynamic configurationform
	
	public function GetConfigurationForm () : string {
		$res = '{ "elements": [
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
			{"type": "Label", 
				"caption": "Beim Ändern des Geräte Typ bitte die Einstellung direkt übernehmen, da sich andere Einstellungen dadurch womöglich ändern können"
			} ' . $this->GetFrontend()->GetFormPart() .' ,
			{"type": "Select", "name": "Backend", "caption": "Backend Typ",
				"options": [
					{ "caption": "Dummy", "value": "Dummy"},
					{ "caption": "IPS Boolean", "value": "IPS_Boolean"},
					{ "caption": "IPS Float", "value": "IPS_Float"}
				]
			},
			{"type": "Label", 
				"caption": "Beim Ändern des Backend Typ bitte die Einstellung direkt übernehmen, da sich andere Einstellungen dadurch womöglich ändern können"
			} ' . $this->GetBackend()->GetFormPart() .' ]}';
		return $res;
	}

	// Overwrites the internal IPS_ApplyChanges($id) function
	public function ApplyChanges(): void {
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
		switch ($this->ReadPropertyString("Backend")) {
		case "IPS_Boolean":
			return new Backend_IPS_Boolean($this);
		case "IPS_Float":
			return new Backend_IPS_Float($this);
		default:
			return new Backend();
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

