<?php
	class P1monitor extends IPSModule {

		public function Create()
		{
			//Never delete this line!
			parent::Create();

			 //Properties
			 $this->RegisterPropertyString ('IPAddress','192.168.89.134');
			 $this->RegisterPropertyBoolean('terugleveren',TRUE);
			 $this->RegisterPropertyBoolean('Production',FALSE);
			 $this->RegisterPropertyBoolean('temperatuur',FALSE);
			
			 //Variables
			 if (!IPS_VariableProfileExists("P1monitor.Watt") || !IPS_VariableProfileExists("P1monitor.ProductionKWH")) $this->UpdateProfil();
			 $CONSUMPTION_W = $this->RegisterVariableFloat('CONSUMPTION_W','Consumption','P1monitor.Watt');
			 $PRODUCTION_W = $this->RegisterVariableFloat('PRODUCTION_W','Production','P1monitor.Watt');
			 $CONSUMPTION_GAS_M3 = $this->RegisterVariableFloat('CONSUMPTION_GAS_M3','Consumption Gas','~Gas');
			 
			 $CONSUMPTION_COST_ELECTRICITY = $this->RegisterVariableFloat('CONSUMPTION_COST_ELECTRICITY','Kosten elektriciteit vandaag','~Euro');
			 $CONSUMPTION_COST_GAS = $this->RegisterVariableFloat('CONSUMPTION_COST_GAS','Kosten gas vandaag','~Euro');
			 $CONSUMPTION_COST = $this->RegisterVariableFloat('CONSUMPTION_COST','Kosten vandaag','~Euro');
			
			 $PRODUCTION_KWH_LOW = $this->RegisterVariableFloat('PRODUCTION_KWH_LOW','Teruggeleverd','P1monitor.ProductionKWH');
			 $PRODUCTION_DELTA_KWH = $this->RegisterVariableFloat('PRODUCTION_DELTA_KWH','Teruggeleverd vandaag','P1monitor.ProductionKWH');

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

			//Register variable if enabled
			$this->MaintainVariable('ROOM_TEMPERATURE_IN', 'Temperature aanvoer',2 , '~Temperature', 0, $this->ReadPropertyBoolean('temperatuur'));
			$this->MaintainVariable('ROOM_TEMPERATURE_OUT', 'Temperature retour',2 , '~Temperature', 0, $this->ReadPropertyBoolean('temperatuur'));

			$this->MaintainVariable('ROOM_TEMPERATURE_OUT', 'KWH meter',2 , 'P1monitor.ProductionKWH', 0, $this->ReadPropertyBoolean('Production'));

		}


		public function UpdateProfil()
		{
			if (!IPS_VariableProfileExists("P1monitor.Watt")) {
				IPS_CreateVariableProfile("P1monitor.Watt", 2);
				IPS_SetVariableProfileDigits("P1monitor.Watt", 0);
				IPS_SetVariableProfileText("P1monitor.Watt",""," W");
				IPS_SetVariableProfileIcon("P1monitor.Watt","Electricity");
			}
			if (!IPS_VariableProfileExists("P1monitor.ProductionKWH")) {
				IPS_CreateVariableProfile("P1monitor.ProductionKWH", 2);
				IPS_SetVariableProfileDigits("P1monitor.ProductionKWH", 3);
				IPS_SetVariableProfileText("P1monitor.ProductionKWH",""," kWh");
				IPS_SetVariableProfileIcon("P1monitor.ProductionKWH","EnergyProduction");
			}
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
			$url = $this->ReadPropertyString('IPAddress');
			$url = 'http://' .$url .'/api/v1/smartmeter?limit=1';
		//	print($url);
			$data = file_get_contents($url); // put the contents of the file into a variable
			$wizards = json_decode($data, true);
			SetValueFloat($this->GetIDForIdent('CONSUMPTION_W'),$wizards['0']['8']);
			SetValueFloat($this->GetIDForIdent('PRODUCTION_W'),$wizards['0']['9']);
			SetValueFloat($this->GetIDForIdent('CONSUMPTION_GAS_M3'),$wizards['0']['10']);
			SetValueFloat($this->GetIDForIdent('PRODUCTION_KWH_LOW'),$wizards['0']['5'] + $wizards['0']['6']);

			
			//$url = $this->ReadPropertyString('IPAddress');
			$url = 'http://' .$this->ReadPropertyString('IPAddress') .'/api/v1/indoor/temperature?limit=1';
			$data = file_get_contents($url); // put the contents of the file into a variable
			$wizards = json_decode($data, true);
			
			$bTemperatuur = $this->ReadPropertyBoolean('temperatuur');
			if ($bTemperatuur){
				SetValueFloat($this->GetIDForIdent('ROOM_TEMPERATURE_IN'),$wizards['0']['3']);
				SetValueFloat($this->GetIDForIdent('ROOM_TEMPERATURE_OUT'),$wizards['0']['7']);
			}

			//financial
			$url = 'http://' .$this->ReadPropertyString('IPAddress') .'/api/v1/financial/day?limit=1';
			$data = file_get_contents($url);
			$wizards = json_decode($data, true);
			SetValueFloat($this->GetIDForIdent('CONSUMPTION_COST_ELECTRICITY'),$wizards['0']['2'] + $wizards['0']['3'] );
			SetValueFloat($this->GetIDForIdent('CONSUMPTION_COST_GAS'),$wizards['0']['6']);
			SetValueFloat($this->GetIDForIdent('CONSUMPTION_COST'),$wizards['0']['2'] + $wizards['0']['3'] + $wizards['0']['6']);
		
			//Production
			$url = 'http://' .$this->ReadPropertyString('IPAddress') .'api/v1/powergas/day?limit=1';
			$data = file_get_contents($url);
			$wizards = json_decode($data, true);
			SetValueFloat($this->GetIDForIdent('PRODUCTION_DELTA_KWH'),$wizards['0']['7']);
			
		}

	}