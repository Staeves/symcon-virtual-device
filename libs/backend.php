<?php 

class Backend extends IPSModule {
	const Invertable = false;
	public function __construct($dev) {
		$this->device = $dev;
	}
	public function getFormPart() {
		$res = "";
		if ($this::Invertable) {
			$res .= ', { "type": "CheckBox", "name": "Inverted", "caption": "Richtung invertieren" }';
		}
		return $res;
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

//##########################################################
//# Home matic
//##########################################################

class Backend_HM extends Backend {
	public function getFormPart() {
		return ', {"type": "SelectInstance", "name": "HW_Variable", "caption": "HomeMatic Instance"}' . parent::getFormPart();
	}
	public function set(float $val) {
		$this->int_set( $this->device->ReadPropertyBoolean("Inverted") ? 1 - $val : $val );
	}
	public function get() : float {
		$val = $this->int_get();
		return $this->device->ReadPropertyBoolean("Inverted") ? 1 - $val : $val;
	}
}

/*
 * generic binary
 * STATE boolean
 *
 * HM-OU-CF-Pl		1,2	(LED and CHIME signal, but does not have a SUBMIT)
 * HM-ES-PMSw1-Pl	1
 * HM-ES-PMSw1-Pl-DN-R1	1
 * HM-ES-PMSw1-Pl-DN-R2	1
 * HM-ES-PMSw1-Pl-DN-R3	1
 * HM-ES-PMSw1-Pl-DN-R4	1
 * HM-ES-PMSw1-Pl-DN-R5	1
 * HM-ES-PMSw1-DR	1
 * HM-ES-PMSw1-SM	1
 * HM-ES-PMSwX		1
 * HM-Dis-TD-T		
 * HM-LC-Sw1-Pl
 * HM-LC-Sw1-Pl-2
 * HM-LC-Sw1-SM
 * HM-LC-Sw2-SM
 * HM-LC-Sw4-SM
 * HM-LC-Sw4-PCB
 * HM-LC-Sw4-WM
 * HM-LC-Sw1-FM
 * 263 130
 * HM-LC-Sw2-FM
 * HM-LC-Sw1-PB-FM
 * HM-LC-Sw2-PB-FM
 * HM-LC-Sw4-DR
 * HM-LC-Sw2-DR
 * ZEL STG RM FZS
 * ZEL STG RM FZS-2
 * HM-LC-SwX
 * HM-Sec-SFA-SM
 * HM-Sec-Sir-WM	1,2
 * ST6-SH		1,2
 * HM-LC-Sw1PBU-FM
 * 263 131
 * HM-LC-Sw2PBU-FM
 * HM-LC-Sw4-Ba-PCB
 * HM-LC-Sw1-Pl-3
 * HM-LC-Sw1-SM-2
 * HM-LC-Sw4-SM
 * HM-LC-Sw4-SM-2
 * HM-LC-Sw4-PCB-2
 * HM-LC-Sw4-WM-2
 * HM-LC-Sw1-FM-2
 * HM-LC-Sw2-FM-2
 * HM-LC-Sw4-DR-2
 * HM-LC-Sw2-DR-2
 * HM-LC-Sw1-Pl-DN-R1
 * HM-LC-Sw1-Pl-DN-R2
 * HM-LC-Sw1-Pl-DN-R3
 * HM-LC-Sw1-Pl-DN-R4
 * HM-LC-Sw1-Pl-DN-R5
 * HM-LC-Sw1-DR
 * HM-LC-Sw1-Pl-CT-R1
 * HM-LC-Sw1-Pl-CT-R2
 * HM-LC-Sw1-Pl-CT-R3
 * HM-LC-Sw1-Pl-CT-R4
 * HM-LC-Sw1-Pl-CT-R5
 * HM-LC-Sw1-PCB
 * HM-MOD-Re-8
 * HM-LC-Sw1-Ba-PCB
 * HM-LC-Sw1-Pl
 * HM-LC-Sw1-SM
 * HM-LC-Sw2-SM
 * HM-LC-Sw4-SM
 * HM-LC-Sw4-PCB
 * HM-LC-Sw1-FM
 * HM-LC-Sw2-FM
 * HM-LC-SwX
 * HM-LC-Sw1-Pl
 * HM-LC-Sw1-Pl-2
 * HM-LC-Sw1-SM
 * HM-LC-Sw2-SM
 * HM-LC-Sw4-SM
 * HM-LC-Sw4-PCB
 * HM-LC-Sw4-WM
 * HM-LC-Sw1-FM
 * 263 130
 * HM-LC-Sw2-FM
 * HM-LC-Sw1-PB-FM
 * HM-LC-Sw2-PB-FM
 * HM-LC-Sw4-DR
 * HM-LC-Sw2-DR
 * ZEL STG RM FZS
 * ZEL STG RM FZS-2
 * HM-LC-SwX
 * HM-LC-Sw1-Pl-OM54
 * HM-LC-Sw1-SM-ATmega168
 * HM-LC-Sw4-SM-ATmega168
 * HMW-IO-12-Sw14-DR		1-6
 * HMW-IO-12-Sw7-DR		13-19
 * HMW-LC-Sw2-DR		3-4
 */
class Backend_HM_Boolean extends Backend_HM {
	const Invertable = true;
	public function int_set(float $val) {
		HM_WriteValueBoolean($this->device->ReadPropertyInteger("HW_Variable"), "STATE", $val > 0);
	}
	public function int_get() : float {
		$inst_id = $this->device->ReadPropertyInteger("HW_Variable");
		return GetValueBoolean(IPS_GetObjectIDByIdent("STATE", $inst_id)) ? 1.0 : 0.0;
	}
}

/*
 * generic float
 * LEVEL 0.0 - 1.0
 *
 * HMW-LC-Dim1L-DR		3
 */

/*
 * dimmer:
 * LEVEL 0.0 - 1.0
 * RAMP_TIME 0.0 - inf
 * RAMP_STOP action
 * WORKING boolean
 *
 * HM-LC-AO-SM		1-3
 * HM-LC-Dim1L-Pl
 * HM-LC-Dim1L-Pl-2
 * HM-LC-Dim1L-CV
 * 263 132
 * HM-LC-Dim2L-CV
 * HM-LC-Dim2L-SM
 * HSS-DX
 * HM-LC-Dim1L-Pl-3
 * HM-LC-Dim1L-CV-2
 * HM-LC-Dim1PWM-CV
 * HM-LC-Dim1PWM-CV-2
 * HM-LC-Dim1TPBU-FM
 * HM-LC-Dim1TPBU-FM-2
 * 263 133
 * HM-LC-Dim1T-Pl
 * HM-LC-Dim1T-CV
 * HM-LC-Dim1T-FM
 * HM-LC-Dim1T-Pl-3
 * HM-LC-Dim1T-CV-2
 * HM-LC-Dim1T-FM-2
 * HM-LC-Dim1T-DR
 * HM-LC-Dim1T-FM-LF
 * HM-LC-Dim2L-SM-2
 * HM-DW-WM
 * HM-LC-Dim2T-SM
 * HM-LC-Dim2T-SM-2
 * HM-LC-Dim1T-Pl-2
 * HM-LC-Dim1T-CV
 * 263 134
 * HM-LC-DW-WM
 * OLIGO.smart.iq.HM
 */

/*
 * rgbX
 * ch a:
 * LEVEL 0.0 - 1.0
 * RAMP_TIME 0.0 - inf
 * RAMP_STOP action
 * WORKING boolean
 * ch b:
 * COLOR 0 - 255
 * ch c:
 * PROGRAM 0 - 255
 *
 * HM-LC-RGBW-WM	1	2	3
 */

/*
 * Rolladen:
 * LEVEL 0.0 - 1.0
 * STOP	action
 * WORKING boolean
 *
 * HM-LC-Bl1-SM
 * HM-LC-Bl1-FM
 * HM-LC-Bl1-PB-FM
 * ZEL STG RM FEP 230V
 * 263 146
 * HM-LC-BlX
 * HM-LC-Bl1-SM-2
 * HM-LC-Bl1-FM-2
 * HM-LC-Bl1PBU-FM
 * 263 147
 * HM-LC-Ja1PBU-FM
 * HM-LC-JaX
 * HMW-LC-Bl1-DR	3
 * HMW-LC-Bl1-DR-2	3
 */

/*
 * Fensterkipper:
 * LEVEL -0.005, 0.0-1.0
 * STOP action
 * WORKING boolean
 *
 * HM-Sec-Win		1
 * HM-Sec-Win-Generic	1

/*
 * Heizkörperthermostat
 * SET_TEMPERATURE 4.5-30.5
 *
 * HM-CC-RT-DN		4
 * HM-CC-RT-DN-BoM	4
 */
/* Wandtehrmostat
 * SETPOINT	0.0, 6.0-30.0, 100.0
 *
 * HM-CC-TC		2
 * ZEL STG RM FWT	2
 */

/* 
 * Signal (sound and light)
 * STATE boolean
 * SUBMIT string 	(https://homematic-forum.de/forum/viewtopic.php?f=31&t=9593)
 *
 * HM-OU-CFM-Pl		1,2	(also has a submit chanal)
 * HM-OU-CFM-TW		1,2	(same as before)
 * HM-OU-CM-PCB		1
 */
