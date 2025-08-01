<?php 

// extend IPSModule, so that we can use it's functions
class Frontend extends IPSModule {
	const BooleanRepr = false;
	const IntegerRepr = false;
	const FloatRepr = false;
	public function __construct($dev) {
		$this->device = $dev;
	}
	public function getFormPart() {
		return "";
	}

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data) : void {
		$val_parts = explode(":", $this->device->GetValue("Value"));
		$vall = strtolower($val_parts[0]);
		switch ($vall) {
		case "wochenplan":
			if ($SenderID != intval($val_parts[1])) {
				throw Exception("sender ($SenderID) is not the current wochenplan ($val_parts[1])");
			}
			$this->prepareValueChange(true);
			// $Data is not documented so just read it from the variable
			$nval = GetValueString($SenderID);
			$this->set($nval, false);
			$this->device->WriteAttributeString("Subvalue", $nval);
			break;
		case "termite":
			$tmaster = $this->device->ReadPropertyInteger("TermiteMaster");
			$tval = GetValueBoolean($tmaster);
			if ($tval) {
				$this->set_auf(true);
			} else {
				$this->set_zu(true);
			}
			break;
		}
	}

	public function prepareValueChange(bool $fromSubstate = false) : void {
		// clean up all mesages and simillar, that the current value needed, so we can set a new value
		if ($fromSubstate) {
			$val = $this->device->ReadAttributeString("Subvalue");
		} else {
			$val = $this->device->GetValue("Value");
		}
		$val_parts = explode(":", $val);
		$vall = strtolower($val_parts[0]);
		switch ($vall) {
		case "wochenplan":
			$this->device->UnregisterMessage(intval($val_parts[1]), VM_UPDATE);
			$this->prepareValueChange(true);
			break;
		case "ausschaltverzoegerung":
			$this->device->SetTimerInterval("TurnOffTimer", 0);
			break;
		case "termite":
			$tmaster = $this->device->ReadPropertyInteger("TermiteMaster");
			$this->device->UnregisterMessage($tmaster, VM_UPDATE);
			break;
		}
	}
	public function set(string $val) : void {
		throw new Exception("Set for Frontend not implemented");
	}
	public function setBoolean(bool $val) : void {
		throw new Exception("SetBoolean for Frontend not implemented");
	}
	public function setInteger(int $val) : void {
		throw new Exception("SetInteger for Frontend not implemented");
	}
	public function setFloat(float $val) : void {
		throw new Exception("SetFloat for Frontend not implemented");
	}

	protected function set_an(bool $doValueSet = true) : void {
		$this->device->GetBackend()->set(1.0);
		if ($doValueSet) {
			$this->device->SetValue("Value", "AN");
		}
		if ($this::BooleanRepr) {
			$this->device->SetValue("BooleanRepr", true);
		}
		if ($this::FloatRepr) {
			$this->device->SetValue("FloatRepr", 1.0);
		}
	}
	protected function set_auf(bool $doValueSet = true) : void {
		$this->device->GetBackend()->set(1.0);
		if ($doValueSet) {
			$this->device->SetValue("Value", "AUF");
		}
		if ($this::BooleanRepr) {
			$this->device->SetValue("BooleanRepr", true);
		}
		if ($this::FloatRepr) {
			$this->device->SetValue("FloatRepr", 1.0);
		}
	}
	protected function set_eingefahren(bool $doValueSet = true) : void {
		$this->device->GetBackend()->set(1.0);
		if ($doValueSet) {
			$this->device->SetValue("Value", "EINGEFAHREN");
		}
		if ($this::BooleanRepr) {
			$this->device->SetValue("BooleanRepr", true);
		}
		if ($this::FloatRepr) {
			$this->device->SetValue("FloatRepr", 1.0);
		}
	}
	protected function set_aus(bool $doValueSet = true) :void {
		$this->device->GetBackend()->set(0.0);
		if ($doValueSet) {
			$this->device->SetValue("Value", "AUS");
		}
		if ($this::BooleanRepr) {
			$this->device->SetValue("BooleanRepr", false);
		}
		if ($this::FloatRepr) {
			$this->device->SetValue("FloatRepr", 0.0);
		}
	}
	protected function set_ab(bool $doValueSet = true) :void {
		$this->device->GetBackend()->set(0.0);
		if ($doValueSet) {
			$this->device->SetValue("Value", "AB");
		}
		if ($this::BooleanRepr) {
			$this->device->SetValue("BooleanRepr", false);
		}
		if ($this::FloatRepr) {
			$this->device->SetValue("FloatRepr", 0.0);
		}
	}
	protected function set_zu(bool $doValueSet = true) :void {
		$this->device->GetBackend()->set(0.0);
		if ($doValueSet) {
			$this->device->SetValue("Value", "ZU");
		}
		if ($this::BooleanRepr) {
			$this->device->SetValue("BooleanRepr", false);
		}
		if ($this::FloatRepr) {
			$this->device->SetValue("FloatRepr", 0.0);
		}
	}
	protected function set_ausgefahren(bool $doValueSet = true) :void {
		$this->device->GetBackend()->set(0.0);
		if ($doValueSet) {
			$this->device->SetValue("Value", "AUSGEFAHREN");
		}
		if ($this::BooleanRepr) {
			$this->device->SetValue("BooleanRepr", false);
		}
		if ($this::FloatRepr) {
			$this->device->SetValue("FloatRepr", 0.0);
		}
	}
	protected function set_schlitze(bool $doValueSet = true) :void {
		$this->device->GetBackend()->set(0.2);
		if ($doValueSet) {
			$this->device->SetValue("Value", "SCHLITZE");
		}
		if ($this::FloatRepr) {
			$this->device->SetValue("FloatRepr", 0.2);
		}
	}
	// the id is not the Wochenplan itself, but a Stirng modified by the wochenplan
	protected function set_wochenplan(string $id, bool $doValueSet = true) : void {
		$id = intval($id);
		$nval = GetValueString($id);
		$this->set($nval, false);	// set to the current value
		$this->device->WriteAttributeString("Subvalue", $nval);
		// get updates from the "wochenplan"
		$this->device->RegisterMessage($id, VM_UPDATE);
		if ($doValueSet) {
			$this->device->SetValue("Value", "WOCHENPLAN:$id");
		}
	}
	protected function set_ausschaltverzoegerung($time, bool $doValueSet = true) : void {
		$seconds = $time[0]*60*60 + $time[1]*60 + $time[2];
		$this->set_AN(false);
		$this->device->SetTimerInterval("TurnOffTimer", 1000*$seconds);
		if ($doValueSet) {
			$this->device->SetValue("Value", "AUSSCHALTVERZOEGERUNG:".implode(":", $time));
		}
	}
	protected function set_floatnum(float $val, bool $doValueSet = true) : void {
		$this->device->GetBackend()->set($val);
		if ($doValueSet) {
			$this->device->SetValue("Value", "$val");
		}
		if ($this::FloatRepr) {
			$this->device->SetValue("FloatRepr", $val);
		}
		
	}
	protected function set_termite(bool $doValueSet = true) : void {
		$tmaster = $this->device->ReadPropertyInteger("TermiteMaster");
		$tval = GetValueBoolean($tmaster);
		if ($tval) {
			$this->set_auf(true);
		} else {
			$this->set_zu(true);
		}
		if ($doValueSet) {
			$this->device->SetValue("Value", "TERMITE");
		}
		$this->device->RegisterMessage($tmaster, VM_UPDATE);
	}
}

class Frontend_SL extends Frontend {
	const BooleanRepr = true;
	public function set(string $val, bool $doValueSet = true) : void {
		$val_parts = explode(":", $val);
		$vall = strtolower($val_parts[0]);
		$fun = "set_$vall";
		switch ($vall) {
		case "an":
		case "aus":
			$this->$fun($doValueSet);
			break;
		case "wochenplan":
			$this->$fun($val_parts[1], $doValueSet);
			break;
		case "ausschaltverzoegerung":
			$this->$fun(array_slice($val_parts, 1), $doValueSet);
			break;
		default:
			throw new Exception("Unknown value $val");
		}
	}
	public function setBoolean(bool $val) : void {
		if ($val) {
			$this->set_AN();
		} else {
			$this->set_AUS();
		}
	}

}

