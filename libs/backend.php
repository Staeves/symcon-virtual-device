<?php 

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
