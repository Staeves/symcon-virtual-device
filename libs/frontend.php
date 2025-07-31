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
}

class Frontend_SL extends Frontend {
	const BooleanRepr = true;
}

