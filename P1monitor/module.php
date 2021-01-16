<?php
	class P1monitor extends IPSModule {

		public function Create()
		{
			//Never delete this line!
			parent::Create();

			 //Properties
			 $this->RegisterPropertyString ('IPAddress','192.168.89.134');
			
			 //Variables
			 $EUsage = $this->RegisterVariableFloat('electricityusage','Electricityusage','~Electricity');

			 $CONSUMPTION_W = $this->RegisterVariableFloat('CONSUMPTION_W','Consumption','~Watt.14490');
			 $CONSUMPTION_GAS_M3 = $this->RegisterVariableFloat('CONSUMPTION_GAS_M3','Consumption Gas','~Gas');
			}


		public function Destroy()
		{
			//Never delete this line!
			parent::Destroy();
		}

		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
		}

		public function GetData()
		{
			$url = $this->ReadPropertyString('IPAddressHeatRequestID')
			$data = file_get_contents($url); // put the contents of the file into a variable
			//$characters = json_decode($data); // decode the JSON feed
			$wizards = json_decode($data, true);
			//print_r($characters);
			// actueel verbruik
			SetValueFloat($this->GetIDForIdent('CONSUMPTION_W'),$wizards['0']['8']);
		}

	}