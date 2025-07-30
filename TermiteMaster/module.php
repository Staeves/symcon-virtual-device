<?

class TermiteMaster extends IPSModule {
	// Overrides the internal IPS_Create($id) function
	public function Create(): void {
	// Don't delete this line
		parent::Create();

		$this->RegisterPropertyInteger("InsideTempVar", 0);
		$this->RegisterPropertyInteger("OutsideTempVar", 0);
		$this->RegisterVariableFloat("MinInsideTemp","MinInsideTemp");
		$this->RegisterVariableFloat("MaxInsideTemp", "MaxInsideTemp");
		$this->EnableAction("MinInsideTemp");
		$this->EnableAction("MaxInsideTemp");
		$this->RegisterVariableBoolean("Open", "Open");
	}

	// Overwrites the internal IPS_ApplyChanges($id) function
	public function ApplyChanges(): void {
		// Don't delete this line
		parent::ApplyChanges();
		
		// remove old messages and references
		foreach ($this->GetMessageList() as $senderID => $messages) {
			foreach ($messages as $message) {
				$this->UnregisterMessage($senderID, $message);
			}
		}
		foreach ($this->GetReferenceList() as $reference) {
			$this->UnregisterReference($reference);
		}

		// add new mesages for variable update of either temp var
		$in_var = $this->ReadPropertyInteger('InsideTempVar');
		if (IPS_VariableExists($in_var)) {
			$this->RegisterMessage($in_var, VM_UPDATE);
			$this->RegisterReference($in_var);
		}
		$out_var = $this->ReadPropertyInteger('OutsideTempVar');
		if (IPS_VariableExists($out_var)) {
			$this->RegisterMessage($out_var, VM_UPDATE);
			$this->RegisterReference($out_var);
		}
		
		$this->Update();
	}

	public function RequestAction ($Ident, $Value) : void {
		$this->Update();
	}

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data) : void {
		$this->Update();
	}

	private function Update() : void {
		$in_var = $this->ReadPropertyInteger('InsideTempVar');
		$out_var = $this->ReadPropertyInteger('OutsideTempVar');
		if (!IPS_VariableExists($in_var) || !IPS_VariableExists($out_var)) {
			// not yet set up
			return;
		}
		// if the in tmp is outside of min and max open, iff out tmp improves in tmp
		$in_tmp = GetValue($in_var);
		$min = $this->GetValue("MinInsideTemp");
		$max = $this->GetValue("MaxInsideTemp");
		if ($in_tmp < $min) {
			$this->SetV($in_tmp < GetValue($out_var));
		} 
		if ($max < $in_tmp) {
			$this->SetV($in_tmp > GetValue($out_var));
		}

	}

	private function SetV($val) : void {
		if (!$val == $this->GetValue("Open")) {
			$this->SetValue("Open", $val);
		}
	}
}
