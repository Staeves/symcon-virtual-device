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

	protected function set_AN() : void {
		$this->device->GetBackend()->set(1.0);
		$this->device->SetValue("Value", "AN");
		if ($this::BooleanRepr) {
			$this->device->SetValue("BooleanRepr", true);
		}
		if ($this::FloatRepr) {
			$this->device->SetValue("FloatRepr", 1.0);
		}
	}
	protected function set_AUS() :void {
		$this->device->GetBackend()->set(0.0);
		$this->device->SetValue("Value", "AUS");
		if ($this::BooleanRepr) {
			$this->device->SetValue("BooleanRepr", false);
		}
		if ($this::FloatRepr) {
			$this->device->SetValue("FloatRepr", 0.0);
		}
	}
}

class Frontend_SL extends Frontend {
	const BooleanRepr = true;
	public function set(string $val) : void {
		switch ($val) {
		case "AN":
		case "AUS":
			$fun = "set_$val";
			$this->$fun();
			break;
		default:
			throw new Exception("Unknown value $val");
	}
	public function setBoolean(bool $val) : void {
		if ($val) {
			$this->set_AN();
		} else {
			$this->set_AUS();
		}
	}

}

