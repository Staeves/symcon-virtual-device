<?

/*
 * provides option to change values that are likely the same for many devices in one place
 * there can allways only be one VirtualDeviceSettings instance
 * Virtual devices can use it, or configure everything themselves (using these values as initial defaults, but not updating them)
 *
 * When VirtualDeviceSettigs applies changes, it calls UpdateSettings on all VirtualDevices, with a list of the new settings
 * 	UpdateSettings decides whether to apply these changes on that device or not
 * When a VirtualDevice is created, it calls GetSettings on the VirtualDeviceSettings instance (if it exists) to load the settings initially
 * When a VirtualDevice is switched to use the global settings it does the same again
 */
class VirtualDeviceSettings extends IPSModule {
	// Overrides the internal IPS_Create($id) function
	public function Create(): void {
		// There can only be one
		if (count(IPS_GetInstanceListByModuleID("{FE5AAE00-92DF-96C3-7435-2CAEBCF1CB62}")) > 1) {
			throw new Exception("There can only be one VirtualDeviceSettings and there already exists one");
		}
		// Don't delete this line
		parent::Create();

		$this->RegisterPropertyInteger("TermiteMaster", 0);
		$this->RegisterPropertyInteger("OutsideTempSensor", 0);
		$this->RegisterPropertyString("Location", '{"latitude":0.0, "longitude": 0.0}');
	}

	// Overwrites the internal IPS_ApplyChanges($id) function
	public function ApplyChanges(): void {
		// Don't delete this line
		parent::ApplyChanges();

		$instances = IPS_GetInstanceListByModuleID("{5FC7B1D7-ED60-B72C-EA50-A8135F4E387A}");
		foreach ($instances as $id) {
			$inst = IPS_GetInstance($id);
			if ($inst["InstanceStatus"] == 102) {
				VirtDev_UpdateGlobalSettings($id);
			}
		}
	}

	public function GetSettings() {
		return array(
			"TermiteMaster" => $this->ReadPropertyInteger("TermiteMaster"),
			"OutsideTempSensor" => $this->ReadPropertyInteger("OutsideTempSensor"),
			"Location" => $this->ReadPropertyString("Location")
		);
	}	

}
