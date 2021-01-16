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

	}