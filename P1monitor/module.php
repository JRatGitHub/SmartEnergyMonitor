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

			 $CONSUMPTION_W = $this->RegisterVariableFloat('CONSUMPTION_W','Consumption','~Watt.3680');
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