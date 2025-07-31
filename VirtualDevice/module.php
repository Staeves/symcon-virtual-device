<?
include __DIR__ . "/../libs/frontend.php";
include __DIR__ . "/../libs/backend.php";

class VirtualDevice extends IPSModule {
	// Overrides the internal IPS_Create($id) function
	public function Create(): void {
	// Don't delete this line
		parent::Create();

		$this->RegisterPropertyString("Frontend", "Dummy");
		$this->RegisterPropertyString("Backend", "Dummy");
		$this->RegisterPropertyInteger("HW_Variable", 0);

		$this->RegisterVariableString("Value", "WERT");
		$this->EnableAction("Value");
	}

	// dynamic configurationform
	
	public function GetConfigurationForm () : string {
		$res = '{ "elements": [
			{"type": "Select", "name": "Frontend", "caption": "Geräte Typ",
				"options": [
					{ "caption": "Dummy", "value": "Dummy" },
					{ "caption": "Schaltbares Licht", "value": "sl" }
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
		case "loatRepr":
			$fe->setFloat($Value);
			break;
		default:
			throw new Exception("Requested action for unknown ident ". $Ident);
		}
	}

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data) : void {
		// only the frontend expects messages, so pass them on
		$this->GetFrontend->MessageSink($TimeStamp, $SenderID, $Message, $Data);
	}

	public function GetFrontend() : Frontend {
		switch ($this->ReadPropertyString("Frontend")) {
		case "sl":
			return new Frontend_SL($this);
		default:
			return new Frontend($this);
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
}

