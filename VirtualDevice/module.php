<?

class VirtualDevice extends IPSModule {
	// Overrides the internal IPS_Create($id) function
	public function Create(): void {
	// Don't delete this line
		parent::Create();

		$this->RegisterPropertyString("Frontend", "sl");
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
					{ "caption": "Schaltbares Licht", "value": "sl" }
				]
			},
			{"type": "Label", 
				"caption": "Beim Ändern des Geräte Typ bitte die Einstellung direkt übernehmen, da sich andere Einstellungen dadurch womöglich ändern können"
			},
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


	}

	public function RequestAction ($Ident, $Value) : void {
		$this->SetValue($Ident, $Value);
	}

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data) : void {
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

class Backend extends IPSModule {
	public function __construct() {
	}
	public function getFormPart() {
		return "";
	}
	public function set(float $val) {
	}

	public function get() : float {
		return 0.0;
	}
}

class Backend_IPS extends Backend {
	public function __construct($dev) {
		$this->id = $dev->ReadPropertyInteger("HW_Variable");
	}
	public function getFormPart() {
		return ', {"type": "SelectVariable", "name": "HW_Variable", "caption": "Variable", "validVariableTypes": [' . $this->var_type_id . ']}';
	}
}
class Backend_IPS_Boolean extends Backend_IPS {
	public function __construct($dev) {
		parent::__construct($dev);
		$this->var_type_id = 0;
	}
	public function set(float $val) {
		SetValueBoolean($this->id, $val > 0.5);
	}
	public function get() : float {
		return GetValueBoolean($this->id) ? 1.0 : 0.0;
	}
}
class Backend_IPS_Float extends Backend_IPS {
	public function __construct($dev) {
		parent::__construct($dev);
		$this->var_type_id = 2;
	}
	public function set(float $val) {
		SetValueFloat($this->id, $val);
	}
	public function get() : float {
		return getValueFloat($this->id);
	}
}
