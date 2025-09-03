<?php 

// extend IPSModule, so that we can use it's functions
class Frontend extends IPSModule {
	const BooleanRepr = false;
	const IntegerRepr = false;
	const FloatRepr = false;

	const NeedsTermiteMaster = false;
	const NeedsOutsideTempSensor = false;
	const NeedsMotionSensor = false;
	const NeedsLocation = false;
	const NeedsSunshineStart = false;
	const NeedsSunshineEnd = false;
	public function __construct($dev) {
		$this->device = $dev;
	}
	public function getFormPart() {
		$res = "";
		if ($this::NeedsTermiteMaster and !$this->device->ReadPropertyBoolean("UseSettings")) {
			$res .= ', {"type": "SelectVariable", "name": "TermiteMaster", "caption": "Termite Master", "validVariableTypes": [0]}';
		}
		if ($this::NeedsOutsideTempSensor and !$this->device->ReadPropertyBoolean("UseSettings")) {
			$res .= ', {"type": "SelectVariable", "name": "OutsideTempSensor", "caption": "Temperatur-Sensor-Wert draußen", "validVariableTypes": [1,2]}';
		}
		if ($this::NeedsMotionSensor) {
			$res .= ', {"type": "SelectVariable", "name": "MotionSensor", "caption": "Bewegungsmelder", "validVariableTypes": [0]}';
		}
		if ($this::NeedsLocation and !$this->device->ReadPropertyBoolean("UseSettings")) {
			$res .= ', { "type": "SelectLocation", "name": "Location", "caption": "Ort" }';
		}
		if ($this::NeedsSunshineStart) {
			$res .= ', {"type": "ValidationTextBox", "name": "SunshineStart", "caption": "Uhrzeit für frühesten Sonnenschutz"},{"type": "Label", "caption": "Format: HH:MM"}';
		}
		if ($this::NeedsSunshineEnd) {
			$res .= ', {"type": "ValidationTextBox", "name": "SunshineEnd", "caption": "Uhrzeit für spätesten Sonnenschutz"},{"type": "Label", "caption": "Format: HH:MM"}';
		}
		return $res;
	}

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data) : void {
		$val_parts = explode(":", $this->device->GetValue("Value"));
		$vall = strtolower($val_parts[0]);
		// handle case where wochenplan and sender is because of substate
		if ($vall == "wochenplan" && $SenderID != intval($val_parts[1])) {
			$val = $this->device->ReadAttributeString("Subvalue");
			$val_parts = explode(":", $val);
			$vall = strtolower($val_parts[0]);
		}
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
				$this->set_auf(false);
			} else {
				$this->set_zu(false);
			}
			break;
		case "nachtisolierung":
			$tmaster = $this->device->ReadPropertyInteger("TermiteMaster");
			$tval = GetValueBoolean($tmaster);
			if ($tval) {
				$this->set_schlitze(false);
			} else {
				$this->set_zu(false);
			}
			break;
		case "beibewegung":
			$msensor = $this->device->ReadPropertyInteger("MotionSensor");
			if (GetValueBoolean($msensor)) {
				$this->set_an(false);
			} else {
				$this->set_aus(false);
			}
			break;
		case "stopp":
			$be = $this->device->GetBackend();
			$working_id = $be->getWorkingID();
			if (GetValue($working_id)) {
				break;
			}
			$this->device->UnregisterMessage($working_id, VM_UPDATE);
			usleep(100000);
			$val = $be->get();
			// like doValueSet, because it is set to stopp iff we wanted to set the value
			if (strtolower($this->device->GetValue("Value")) == "stopp") {
				$this->device->SetValue("Value", "$val");
			}
			if ($this::BooleanRepr) {
				$this->device->SetValue("BooleanRepr", $val > 0);
			}
			if ($this::FloatRepr) {
				$this->device->SetValue("FloatRepr", $val);
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
		case "nachtisolierung":
			$tmaster = $this->device->ReadPropertyInteger("TermiteMaster");
			$this->device->UnregisterMessage($tmaster, VM_UPDATE);
			break;
		case "sonnenschutz":
			$this->device->SetTimerInterval("UpdateSonnenschutz", 0);
			break;
		case "beibewegung":
			$msensor = $this->device->ReadPropertyInteger("MotionSensor");
			$this->device->UnregisterMessage($msensor, VM_UPDATE);
			break;
		case "stopp":
			$working_id = $this->device->GetBackend()->getWorkingID();
			$this->device->UnregisterMessage($working_id, VM_UPDATE);
			break;
		}
	}
	public function set(string $val) : void {
		throw new Exception("Set for Frontend not implemented");
	}
	public function setBoolean(bool $val) : void {
		if ($val) {
			$this->set_an();
		} else {
			$this->set_aus();
		}
	}
	public function setInteger(int $val) : void {
		throw new Exception("SetInteger for Frontend not implemented");
	}
	public function setFloat(float $val) : void {
		$this->set_floatnum($val);
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
		# allow percent values in [0;100] and values in [0;1] by mapping to [0;1] if > 1
		if ($val > 1.0) {
			$val = $val / 100;
		}
		$this->device->GetBackend()->set($val);
		if ($doValueSet) {
			$this->device->SetValue("Value", "$val");
		}
		if ($this::FloatRepr) {
			$this->device->SetValue("FloatRepr", $val);
		}
		if ($this::BooleanRepr) {
			$this->device->SetValue("BooleanRepr", $val > 0);
		}
		
	}
	// requires set_in in backend
	protected function set_floatnum_in(float $val, float $time, bool $doValueSet = true) : void {
		# allow percent values in [0;100] and values in [0;1] by mapping to [0;1] if > 1
		if ($val > 1.0) {
			$val = $val / 100;
		}
		$this->device->GetBackend()->set_in($val, $time);
		if ($doValueSet) {
			$this->device->SetValue("Value", "$val:$time");
		}
		if ($this::FloatRepr) {
			$this->device->SetValue("FloatRepr", $val);
		}
		if ($this::BooleanRepr) {
			$this->device->SetValue("BooleanRepr", $val > 0);
		}
	}
	protected function set_stopp(bool $doValueSet = true) : void {
		$working_id = $this->device->GetBackend()->stop();
		if ($doValueSet) {
			$this->device->SetValue("Value", "STOPP");
		}
		$this->device->RegisterMessage($working_id, VM_UPDATE);
	}
	protected function set_termite(bool $doValueSet = true) : void {
		$tmaster = $this->device->ReadPropertyInteger("TermiteMaster");
		$tval = GetValueBoolean($tmaster);
		if ($tval) {
			$this->set_auf(false);
		} else {
			$this->set_zu(false);
		}
		if ($doValueSet) {
			$this->device->SetValue("Value", "TERMITE");
		}
		$this->device->RegisterMessage($tmaster, VM_UPDATE);
	}
	protected function set_sonnenschutz(bool $doValueSet = true) : void {
		if ($doValueSet) {
			$this->device->SetValue("Value", "SONNENSCHUTZ");
		}
		$this->device->SetTimerInterval("UpdateSonnenschutz", 1000*60*10);
		$this->update_sonnenschutz();
	}
	protected function update_sonnenschutz() : void {
		// is todays max tmp already known? if not get it
		if (!(in_array("maxTmp", $this->device->GetBufferList()) &&
			in_array("maxTmpUpdate", $this->device->GetBufferList()) &&
			$this->device->GetBuffer("maxTmpUpdate") == date("Y-m-d"))) {
			$location = json_decode($this->device->ReadPropertyString("Location"));
			$lat = $location->latitude;
			$long = $location->longitude;
			$data = file_get_contents("https://api.open-meteo.com/v1/forecast?latitude=$lat&longitude=$long&daily=temperature_2m_max&timezone=auto&forecast_days=1");
			$temp = json_decode($data)->daily->temperature_2m_max[0];
			$this->device->SetBuffer("maxTmp", "$temp");
			$this->device->SetBuffer("maxTmpUpdate", date("Y-m-d"));
		} else {
			$temp = floatval($this->device->GetBuffer("maxTmp"));
		}
		// If it will be over 27Degree and is over 23 and time in range for this Direction then schlitze, else offen
		$startTime = str_pad(trim($this->device->ReadPropertyString("SunshineStart")), 5, "0", STR_PAD_LEFT);
		$endTime = str_pad(trim($this->device->ReadPropertyString("SunshineEnd")), 5, "0", STR_PAD_LEFT);
		$inTime = strcmp($startTime, date("H:i")) <= 0 && strcmp(date("H:i"), $endTime) <= 0;
		if ($inTime &&
			$temp > 27 &&
			GetValue($this->device->ReadPropertyInteger("OutsideTempSensor")) > 23) {
			$this->set_schlitze(false);
		} else {
			$this->set_auf(false);
		}
	}
	protected function set_nachtisolierung(bool $doValueSet = true) : void {
		$tmaster = $this->device->ReadPropertyInteger("TermiteMaster");
		$tval = GetValueBoolean($tmaster);
		if ($tval) {
			$this->set_schlitze(false);
		} else {
			$this->set_zu(false);
		}
		if ($doValueSet) {
			$this->device->SetValue("Value", "NACHTISOLIERUNG");
		}
		$this->device->RegisterMessage($tmaster, VM_UPDATE);
	}
	protected function set_beibewegung(bool $doValueSet = true) : void {
		$msensor = $this->device->ReadPropertyInteger("MotionSensor");
		if (GetValueBoolean($msensor)) {
			$this->set_an(false);
		} else {
			$this->set_aus(false);
		}
		if ($doValueSet) {
			$this->device->SetValue("Value", "BEIBEWEGUNG");
		}
		$this->device->RegisterMessage($msensor, VM_UPDATE);
	}

}

class Frontend_KO extends Frontend {
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
		default:
			throw new Exception("Unknown value $val");
		}
	}
}
class Frontend_BO extends Frontend {
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
		default:
			throw new Exception("Unknown value $val");
		}
	}
}
class Frontend_FK extends Frontend {
	const FloatRepr = true;
	const NeedsTermiteMaster = true;
	public function set(string $val, bool $doValueSet = true) : void {
		$val_parts = explode(":", $val);
		$vall = strtolower($val_parts[0]);
		$fun = "set_$vall";
		switch ($vall) {
		case "auf":
		case "zu":
		case "termite":
			$this->$fun($doValueSet);
			break;
		case "wochenplan":
			$this->$fun($val_parts[1], $doValueSet);
			break;
		default:
			if (is_numeric($vall)) {
				$this->set_floatnum(floatval($vall), $doValueSet);
			} else {
				throw new Exception("Unknown value $val");
			}
		}
	}
}
class Frontend_AK extends Frontend {
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
		default:
			throw new Exception("Unknown value $val");
		}
	}
}
class Frontend_du extends Frontend {	// TODO setinteger and stufe:XY
	const IntegerRepr = true;
	public function set(string $val, bool $doValueSet = true) : void {
		$val_parts = explode(":", $val);
		$vall = strtolower($val_parts[0]);
		$fun = "set_$vall";
		switch ($vall) {
		case "aus":
			$this->$fun($doValueSet);
			break;
		default:
			throw new Exception("Unknown value $val");
		}
	}
}
class Frontend_LU extends Frontend {	// TODO setinteger and stufe:XY
	const IntegerRepr = true;
	public function set(string $val, bool $doValueSet = true) : void {
		$val_parts = explode(":", $val);
		$vall = strtolower($val_parts[0]);
		$fun = "set_$vall";
		switch ($vall) {
		case "aus":
			$this->$fun($doValueSet);
			break;
		case "wochenplan":
			$this->$fun($val_parts[1], $doValueSet);
			break;
		default:
			throw new Exception("Unknown value $val");
		}
	}
}
class Frontend_DL extends Frontend {	// TODO Konstantlicht
	const BooleanRepr = true;
	const FloatRepr = true;
	const NeedsMotionSensor = true;
	public function set(string $val, bool $doValueSet = true) : void {
		$val_parts = explode(":", $val);
		$vall = strtolower($val_parts[0]);
		$fun = "set_$vall";
		switch ($vall) {
		case "an":
		case "aus":
		case "beibewegung":
		case "stopp":
			$this->$fun($doValueSet);
			break;
		case "wochenplan":
			$this->$fun($val_parts[1], $doValueSet);
			break;
		case "ausschaltverzoegerung":
			$this->$fun(array_slice($val_parts, 1), $doValueSet);
			break;
		default:
			if (is_numeric($vall)) {
				if (sizeof($vall_parts) == 1) {
					$this->set_floatnum(floatval($vall), $doValueSet);
				} else {
					$this->set_floatnum_in(floatval($vall), floatval($vall_parts[1]), $doValueSet);
			} else {
				throw new Exception("Unknown value $val");
			}
		}
	}
}
class Frontend_SL extends Frontend {
	const BooleanRepr = true;
	const NeedsMotionSensor = true;
	public function set(string $val, bool $doValueSet = true) : void {
		$val_parts = explode(":", $val);
		$vall = strtolower($val_parts[0]);
		$fun = "set_$vall";
		switch ($vall) {
		case "an":
		case "aus":
		case "beibewegung":
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
}
class Frontend_BL extends Frontend {	// TODO Konstantlicht, % in s, stopp, farbe:int, play, status
	const BooleanRepr = true;
	const FloatRepr = true;
	const NeedsMotionSensor = true;
	public function set(string $val, bool $doValueSet = true) : void {
		$val_parts = explode(":", $val);
		$vall = strtolower($val_parts[0]);
		$fun = "set_$vall";
		switch ($vall) {
		case "an":
		case "aus":
		case "beibewegung":
			$this->$fun($doValueSet);
			break;
		case "wochenplan":
			$this->$fun($val_parts[1], $doValueSet);
			break;
		case "ausschaltverzoegerung":
			$this->$fun(array_slice($val_parts, 1), $doValueSet);
			break;
		default:
			if (is_numeric($vall)) {
				if (sizeof($vall_parts) == 1) {
					$this->set_floatnum(floatval($vall), $doValueSet);
				} else {
					$this->set_floatnum_in(floatval($vall), floatval($vall_parts[1]), $doValueSet);
			} else {
				throw new Exception("Unknown value $val");
			}
		}
	}
}
class Frontend_HZ extends Frontend {	// TODO setInteger, Zahl
	const IntegerRepr = true;
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
		default:
			throw new Exception("Unknown value $val");
		}
	}
}
class Frontend_ST extends Frontend {
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
}
class Frontend_RA extends Frontend {	// TODO Konstantlicht, stopp, dunkel
	const FloatRepr = true;
	const NeedsTermiteMaster = true;
	const NeedsOutsideTempSensor = true;
	const NeedsLocation = true;
	const NeedsSunshineStart = true;
	const NeedsSunshineEnd = true;
	public function set(string $val, bool $doValueSet = true) : void {
		$val_parts = explode(":", $val);
		$vall = strtolower($val_parts[0]);
		$fun = "set_$vall";
		switch ($vall) {
		case "auf":
		case "ab":
		case "zu":
		case "schlitze":
		case "sonnenschutz":
		case "nachtisolierung":
			$this->$fun($doValueSet);
			break;
		case "wochenplan":
			$this->$fun($val_parts[1], $doValueSet);
			break;
		default:
			if (is_numeric($vall)) {
				$this->set_floatnum(floatval($vall), $doValueSet);
			} else {
				throw new Exception("Unknown value $val");
			}
		}
	}
}
class Frontend_PC extends Frontend {	// TODO turn off protection when running
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
		default:
			throw new Exception("Unknown value $val");
		}
	}
}
class Frontend_AS extends Frontend {	// TODO Stopp, play, status
	public function set(string $val, bool $doValueSet = true) : void {
		$val_parts = explode(":", $val);
		$vall = strtolower($val_parts[0]);
		$fun = "set_$vall";
		switch ($vall) {
		case "wochenplan":
			$this->$fun($val_parts[1], $doValueSet);
			break;
		default:
			throw new Exception("Unknown value $val");
		}
	}
}
class Frontend_LS extends Frontend {	// TODO Stopp, play, status
	public function set(string $val, bool $doValueSet = true) : void {
		$val_parts = explode(":", $val);
		$vall = strtolower($val_parts[0]);
		$fun = "set_$vall";
		switch ($vall) {
		case "wochenplan":
			$this->$fun($val_parts[1], $doValueSet);
			break;
		default:
			throw new Exception("Unknown value $val");
		}
	}
}
class Frontend_SA extends Frontend {	// TODO status
	public function set(string $val, bool $doValueSet = true) : void {
		$val_parts = explode(":", $val);
		$vall = strtolower($val_parts[0]);
		$fun = "set_$vall";
		switch ($vall) {
		default:
			throw new Exception("Unknown value $val");
		}
	}
}
class Frontend_TA extends Frontend {	// TODO stopp
	const BooleanRepr = true;
	public function set(string $val, bool $doValueSet = true) : void {
		$val_parts = explode(":", $val);
		$vall = strtolower($val_parts[0]);
		$fun = "set_$vall";
		switch ($vall) {
		case "auf":
		case "zu":
			$this->$fun($doValueSet);
			break;
		default:
			throw new Exception("Unknown value $val");
		}
	}
	// boolean setting should be auf/zu unlike the default
	public function setBoolean(bool $val) : void {
		if ($val) {
			$this->set_auf();
		} else {
			$this->set_zu();
		}
	}
}
class Frontend_MA extends Frontend {	// TODO stopp
	const FloatRepr = true;
	const NeedsOutsideTempSensor = true;
	const NeedsLocation = true;
	const NeedsSunshineStart = true;
	const NeedsSunshineEnd = true;
	public function set(string $val, bool $doValueSet = true) : void {
		$val_parts = explode(":", $val);
		$vall = strtolower($val_parts[0]);
		$fun = "set_$vall";
		switch ($vall) {
		case "sonnenschutz":
		case "ausgefahren":
		case "eingefahren":
			$this->$fun($doValueSet);
			break;
		case "wochenplan":
			$this->$fun($val_parts[1], $doValueSet);
			break;
		default:
			if (is_numeric($vall)) {
				$this->set_floatnum(floatval($vall), $doValueSet);
			} else {
				throw new Exception("Unknown value $val");
			}
		}
	}
}
class Frontend_VO extends Frontend {	// TODO stopp
	const FloatRepr = true;
	const NeedsOutsideTempSensor = true;
	const NeedsLocation = true;
	const NeedsSunshineStart = true;
	const NeedsSunshineEnd = true;
	public function set(string $val, bool $doValueSet = true) : void {
		$val_parts = explode(":", $val);
		$vall = strtolower($val_parts[0]);
		$fun = "set_$vall";
		switch ($vall) {
		case "sonnenschutz":
		case "auf":
		case "ab":
			$this->$fun($doValueSet);
			break;
		case "wochenplan":
			$this->$fun($val_parts[1], $doValueSet);
			break;
		default:
			if (is_numeric($vall)) {
				$this->set_floatnum(floatval($vall), $doValueSet);
			} else {
				throw new Exception("Unknown value $val");
			}
		}
	}
}
class Frontend_WS extends Frontend {	// TODO stopp
	const FloatRepr = true;
	public function set(string $val, bool $doValueSet = true) : void {
		$val_parts = explode(":", $val);
		$vall = strtolower($val_parts[0]);
		$fun = "set_$vall";
		switch ($vall) {
		case "auf":
		case "ab":
			$this->$fun($doValueSet);
			break;
		default:
			if (is_numeric($vall)) {
				$this->set_floatnum(floatval($vall), $doValueSet);
			} else {
				throw new Exception("Unknown value $val");
			}
		}
	}
}
class Frontend_NE extends Frontend {	// TODO setInteger, Zahl
	const IntegerRepr = true;
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
		default:
			throw new Exception("Unknown value $val");
		}
	}
}
