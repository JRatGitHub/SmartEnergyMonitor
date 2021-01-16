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

			 $this->RegisterTimer('INTERVAL',10, 'MON_GetData($id)');
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

		protected function RegisterTimer($ident, $interval, $script) {
			$id = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
		
			if ($id && IPS_GetEvent($id)['EventType'] <> 1) {
			  IPS_DeleteEvent($id);
			  $id = 0;
			}
		
			if (!$id) {
			  $id = IPS_CreateEvent(1);
			  IPS_SetParent($id, $this->InstanceID);
			  IPS_SetIdent($id, $ident);
			}
		
			IPS_SetName($id, $ident);
			IPS_SetHidden($id, true);
			IPS_SetEventScript($id, "\$id = \$_IPS['TARGET'];\n$script;");
		
			if (!IPS_EventExists($id)) throw new Exception("Ident with name $ident is used for wrong object type");
		
			if (!($interval > 0)) {
			  IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, 1);
			  IPS_SetEventActive($id, false);
			} else {
			  IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, $interval);
			  IPS_SetEventActive($id, true);
			}
		  }

		public function GetData()
		{
			$url = $this->ReadPropertyString('IPAddressHeatRequestID');
			$url = $url + '/api/v1/smartmeter?limit=1';
			print_r($url);
			$data = file_get_contents($url); // put the contents of the file into a variable
			//$characters = json_decode($data); // decode the JSON feed
			$wizards = json_decode($data, true);
			//print_r($characters);
			// actueel verbruik
			SetValueFloat($this->GetIDForIdent('CONSUMPTION_W'),$wizards['0']['8']);
		}

	}