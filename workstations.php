<!DOCTYPE html>
<html lang="de">
	<head>
		<title>Wallchart</title>
		<meta charset="utf-8"/>
		<meta name="viewport" content="width=device-width, initial-scale=1"/>
		<style>
		@font-face { font-family: 'clock'; src: url('clock.ttf') format('truetype'); }
		html, body {
			margin: 0px;
			padding: 0px;
			font-family: tahoma,arial,verdana,sans-serif;
		}
		table td {
			padding: 5px;
			padding-top: 2px;
			padding-bottom: 2px;
			margin: 0px;
			border-spacing: 0px;
			vertical-align: top;
			font-size: 13px;
		}
		table td img {
			width: 15px;
		}
		table th {
			border-bottom: 2px solid #000000;
			padding: 5px;
			text-align: left;
		}
		table {
			_margin-top: 10px;
			width: 100%;
			border-collapse: collapse;
		}
		#headleft {
			float: left;
			padding: 10px;
		}
		#headright {
			float: right;
			padding: 5px;
			font-family: "clock";
			font-size: 50px;
			text-align:right;
		}
		#headline {
			width: 100%;
			text-align: center;
			height: 93px;
			line-height: 93px;
			font-size: 50px;
			position: absolute;
			top: -7px;
			left: 0px;
		}
		#date {
			font-size: 30px;
		}
		</style>
		<script>
			function startTime() {
				var today = new Date();
				var h = today.getHours();
				var m = today.getMinutes();
				var s = today.getSeconds();
				m = checkTime(m);
				s = checkTime(s);
				document.getElementById('clock').innerHTML =
				h + ":" + m + ":" + s + "&nbsp;";
				var t = setTimeout(startTime, 500);
			}
			function checkTime(i) {
				if (i < 10) {i = "0" + i};
				return i;
			}
		</script>
		<meta http-equiv="refresh" content="60">
	<head>
	<body onload="startTime()">
	
		<div>
			<div id="headleft"><img src="logo.png" style="height: 70px;" alt="Company Logo"></div>
			<div id="headright"><div id="clock"><?php echo date("H:i:s"); ?></div><div id="date"><?php echo date("l, j.m."); ?>&nbsp;</div></div>
			<div style="clear:both;"></div>
		</div>
		<div id="headline">
			Störungsübersicht
		</div>
		
		
		
		
		
		
		<?php 
		function getXML($param)
		{
			/////////////////////////////////////////////
			$APIKEY = "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX";
			/////////////////////////////////////////////
			return simplexml_load_file("https://wwwgermany1.systemmonitor.eu.com/api/?apikey=" . $APIKEY . $param);
		}
		
		// Array:
		// clientid, clientname, sites
		//	-> siteid, sitename, server
		//   -> serverid, servername, serverlastboot, serverlastresponse, failedchecks
		//    -> failed-check.....
		
		function getFailedChecks()
		{
			$ResultArray = array();
			$xml = getXML("&service=list_failing_checks");
			foreach ($xml->items->client as $client)
			{
				$FailedChecksFound = false;
				$TempClientArray['ClientId'] = (string)$client->clientid;
				$TempClientArray['ClientName'] = (string)$client->name;
				$TempClientArray['Sites'] = array();

				foreach ($client->site as $site)
				{
					//$FailedChecksFound = false;
					$TempSiteArray = array();
					$TempSiteArray['SiteId'] = (string)$site->siteid;
					$TempSiteArray['SiteName'] = (string)$site->name;
					$TempSiteArray['Workstation'] = array();
					
					foreach ($site->workstations->workstation as $workstation)
					{
						if($workstation->failed_checks->check)
						{
							$TempWorkstationArray = array();
							$FailedChecksFound = true;
							$TempWorkstationArray['WorkstationId'] = (string)$workstation->id;
							$TempWorkstationArray['WorkstationName'] = (string)$workstation->name;
							$TempWorkstationArray['LastBoot'] = "";
							$TempWorkstationArray['LastResponse'] = (string)$workstation->overdue->startdate . " " . (string)$workstation->overdue->starttime;
							
							$FailedChecks = array();
							foreach($workstation->failed_checks->check as $failed_check)
							{
								array_push($FailedChecks, array((string)$failed_check->description, (string)$failed_check->formatted_output, $failed_check->check_type));
							}
							$TempWorkstationArray['FailedChecks'] = $FailedChecks;
							array_push($TempSiteArray['Workstation'], $TempWorkstationArray);
						}
						
						if($workstation->offline)
						{
							$TempWorkstationArray = array();
							$FailedChecksFound = true;
							$TempWorkstationArray['WorkstationId'] = (string)$workstation->id;
							$TempWorkstationArray['WorkstationName'] = (string)$workstation->name;
							$TempWorkstationArray['LastBoot'] = "";
							$TempWorkstationArray['LastResponse'] = (string)$workstation->overdue->startdate . " " . (string)$workstation->overdue->starttime;
							$TempWorkstationArray['Offline'] = "yes";
							array_push($TempSiteArray['Workstation'], $TempWorkstationArray);
						}
					}
					if($FailedChecksFound)
					{
						array_push($TempClientArray['Sites'], $TempSiteArray);
					}
				}
				if($FailedChecksFound)
				{
					array_push($ResultArray, $TempClientArray);
				}
			}
			return $ResultArray;
		}
		
		
		function getDeviceDetail($DeviceID)
		{
			$xml = getXML("&service=list_device_monitoring_details&deviceid=" . $DeviceID);
			foreach ($xml->workstation as $Workstation)
			{
				$ResultArray['WorkstationName'] = (string)$Workstation->name;
				$ResultArray['lastresponse'] = (string)$Workstation->lastresponse;
				$ResultArray['lastboot'] = (string)$Workstation->lastboot;
			}
			return $ResultArray;
		}
		
				
		$ResultArray = getFailedChecks();
		
		$i = 0;
		echo "<table>";
		echo "<tr><th>Kunde</th><th>Workstation</th><th>Fehler</th><th style='min-width:80px;'><!--Stand--></th><th style='min-width:170px;'><!--Uptime--></th></tr>";
		foreach($ResultArray as $Client)
		{
			foreach($Client['Sites'] as $Site)
			{
				foreach($Site['Workstation'] as $Workstation)
				{
					if($Workstation['Offline'] != "yes") {
						//$WorkstationDetail = getDeviceDetail($Workstation['WorkstationId']);
						if($i % 2 == 0)
						{
							$tablecellbg = "background-color:#efefef;";
						} else {
							$tablecellbg = "background-color:#ffffff;";
						}
						if($Workstation['Overdue'] != "") {
							echo "<tr style='color:red;" . $tablecellbg . "'>";
						} else {
							echo "<tr style='" . $tablecellbg . "'>";
						}
						echo "<td>" . $Client['ClientName'] . "<font style='font-size:80%;color:#545454;'>&nbsp;-&nbsp;" . $Site['SiteName'] . "</font></td><td>" . $Workstation['WorkstationName'] . "</td><td>";
						
						if($Workstation['Overdue'] == "")
						{
							foreach($Workstation['FailedChecks'] as $FailedCheck)
							{
								echo "<img src='https://dashboardgermany1.systemmonitor.eu.com/images/check_" . $FailedCheck[2] . ".gif'>&nbsp;&nbsp;" . $FailedCheck[0] . " <font style='font-size:80%;color:#545454;'>" . substr(strip_tags($FailedCheck[1]), 0, 100) . " [..]</font><br>";
							}
						} else {
							$TimeSinceLastRespond = date_diff(new DateTime($WorkstationDetail['lastresponse']), new DateTime());
							echo "Keine Rückmeldung seit " . $TimeSinceLastRespond->format("%h <small>Stunde(n)</small>, %i <small>Minute(n)</small>") . "";
						}
						$TimeSinceLastBoot = date_diff(new DateTime($WorkstationDetail['lastboot']), new DateTime());
						echo "</td><td><!--" . date('H:i',strtotime($WorkstationDetail['lastresponse'])) . " <small>Uhr</small>--></td><td><!--" . $TimeSinceLastBoot->format("%a <small>Tag(e)</small>, %h <small>Stunde(n)</small>") . "--></td></tr>";
						$i++;
					}
				}
			}
		}
		echo "</table>";
		
		?>
	</body>
</html>