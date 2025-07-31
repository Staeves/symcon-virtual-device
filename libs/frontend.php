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
			// $Data is not documented so just read it from the variable
			$this->set(GetValueString($SenderID), false);
			break;
		}
	}

	public function prepareValueChange() : void {
		// clean up all mesages and simillar, that the current value needed, so we can set a new value
		$val_parts = explode(":", $this->device->GetValue("Value"));
		$vall = strtolower($val_parts[0]);
		switch ($vall) {
		case "wochenplan":
			$this->device->UnregisterMessage(intval($val_parts[1]), VM_UPDATE);
			break;
		case "ausschaltverzoegerung":
			$this->device->SetTimerInterval("TurnOffTimer", 0);
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
	// the id is not the Wochenplan itself, but a Stirng modified by the wochenplan
	protected function set_wochenplan(string $id, bool $doValueSet = true) : void {
		$id = intval($id);
		$this->set(GetValueString($id), false);	// set to the current value
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

