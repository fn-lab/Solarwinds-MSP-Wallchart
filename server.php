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
			padding-top: 5px;
			padding-bottom: 5px;
			margin: 0px;
			border-spacing: 0px;
			vertical-align: top;
			font-size: 15px;
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
			margin-top: -10px;
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
				var randomId = new Date().getTime();
				var today = new Date();
				var h = today.getHours();
				var m = today.getMinutes();
				var s = today.getSeconds();
				m = checkTime(m);
				s = checkTime(s);
				document.getElementById('clock').innerHTML =
				h + ":" + m + ":" + s + "&nbsp;";
				var t = setTimeout(startTime, 2000);
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
		
		function getFailedClientSites()
		{
			$ResultArray = array();
			$xml = getXML("&service=list_clients");
			foreach ($xml->items->client as $client)
			{
				$FailedSiteFound = false;
				$TempClientArray['ClientId'] = (string)$client->clientid;
				$ClientName = explode(" (", (string)$client->name);
				$TempClientArray['ClientName'] = $ClientName[0];
				$TempClientArray['Sites'] = array();
				
				$xml2 = getXML("&service=list_sites&clientid=" . $TempClientArray['ClientId']);
				foreach ($xml2->items->site as $site)
				{
					$TempSiteArray = array();
					$TempSiteArray['Connected'] = (string)$site->connection_ok;
					$TempSiteArray['SiteName'] = (string)$site->name;
					if($TempSiteArray['Connected'] == "0") {
						$FailedSiteFound = true;
						array_push($TempClientArray['Sites'], $TempSiteArray);
					}
				}
				if($FailedSiteFound)
				{
					array_push($ResultArray, $TempClientArray);
				}
			}
			return $ResultArray;
		}
		
		function getFailedChecks()
		{
			$ResultArray = array();
			$xml = getXML("&service=list_failing_checks");
			foreach ($xml->items->client as $client)
			{
				$FailedChecksFound = false;
				$TempClientArray['ClientId'] = (string)$client->clientid;
				$ClientName = explode(" (", (string)$client->name);
				$TempClientArray['ClientName'] = $ClientName[0];
				$TempClientArray['Sites'] = array();

				foreach ($client->site as $site)
				{
					//$FailedChecksFound = false;
					$TempSiteArray = array();
					$TempSiteArray['SiteId'] = (string)$site->siteid;
					$TempSiteArray['SiteName'] = (string)$site->name;
					$TempSiteArray['Server'] = array();
					
					foreach ($site->servers->server as $server)
					{
						if($server->failed_checks->check)
						{
							$TempServerArray = array();
							$FailedChecksFound = true;
							$TempServerArray['ServerId'] = (string)$server->id;
							$TempServerArray['ServerName'] = (string)$server->name;
							$TempServerArray['LastBoot'] = "";
							$TempServerArray['LastResponse'] = (string)$server->overdue->startdate . " " . (string)$server->overdue->starttime;
							
							$FailedChecks = array();
							foreach($server->failed_checks->check as $failed_check)
							{
								array_push($FailedChecks, array((string)$failed_check->description, (string)$failed_check->formatted_output, $failed_check->check_type));
							}
							$TempServerArray['FailedChecks'] = $FailedChecks;
							array_push($TempSiteArray['Server'], $TempServerArray);
						}
						
						if($server->overdue)
						{
							$TempServerArray = array();
							$FailedChecksFound = true;
							$TempServerArray['ServerId'] = (string)$server->id;
							$TempServerArray['ServerName'] = (string)$server->name;
							$TempServerArray['LastBoot'] = "";
							$TempServerArray['LastResponse'] = (string)$server->overdue->startdate . " " . (string)$server->overdue->starttime;
							$TempServerArray['Overdue'] = "yes";
							array_push($TempSiteArray['Server'], $TempServerArray);
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
			foreach ($xml->server as $Server)
			{
				$ResultArray['ServerName'] = (string)$Server->name;
				$ResultArray['lastresponse'] = (string)$Server->lastresponse;
				$ResultArray['lastboot'] = (string)$Server->lastboot;
			}
			return $ResultArray;
		}
		
				
		$ResultArray = getFailedChecks();
		$ResultArray1 = getFailedClientSites();
		
		$i = 0;
		echo "<table>";
		echo "<tr><th style='width:300px;'>Kunde</th><th>Server</th><th>Fehler</th><th style='width:80px;'>Stand</th><th style='width:170px;'>Uptime</th></tr>";
		
		foreach(array_merge($ResultArray,$ResultArray1) as $Client)
		{
			foreach($Client['Sites'] as $Site)
			{
				if($Site['Connected'] == "0") {
					if($i % 2 == 0)
					{
						$tablecellbg = "background-color:#efefef;";
					} else {
						$tablecellbg = "background-color:#ffffff;";
					}
					
					echo "<tr style='color:red;" . $tablecellbg . "font-weight:bold;'>";
					echo "<td>" . $Client['ClientName'] . "<font style='font-size:80%;color:red;'>&nbsp;-&nbsp;" . $Site['SiteName'] . "</font></td><td>---</td><td>Standort offline</td><td>---</td><td>---</td>";
					echo "</tr>";
					
					$i++;
				} else {
					foreach($Site['Server'] as $Server)
					{
						if($Server['Overdue'] != "") {
							$ServerDetail = getDeviceDetail($Server['ServerId']);
							if($i % 2 == 0)
							{
								$tablecellbg = "background-color:#efefef;";
							} else {
								$tablecellbg = "background-color:#ffffff;";
							}

							echo "<tr style='color:red;" . $tablecellbg . "font-weight:bold;'>";

							echo "<td>" . $Client['ClientName'] . "<font style='font-size:80%;color:#545454;'>&nbsp;-&nbsp;" . $Site['SiteName'] . "</font></td><td>" . $Server['ServerName'] . "</td><td>";

							$TimeSinceLastRespond = date_diff(new DateTime($ServerDetail['lastresponse']), new DateTime());
							echo "Keine Rückmeldung seit " . $TimeSinceLastRespond->format("%h <small>Stunde(n)</small>, %i <small>Minute(n)</small>") . "";

							$TimeSinceLastBoot = date_diff(new DateTime($ServerDetail['lastboot']), new DateTime());
							echo "</td><td>" . date('H:i',strtotime($ServerDetail['lastresponse'])) . " <small>Uhr</small></td><td>" . $TimeSinceLastBoot->format("%a <small>Tag(e)</small>, %h <small>Stunde(n)</small>") . "</td></tr>";
							$i++;
						}
					}
				}
			}
		}
		
		foreach($ResultArray as $Client)
		{
			foreach($Client['Sites'] as $Site)
			{
				foreach($Site['Server'] as $Server)
				{
					if($Server['Overdue'] == "") {
						$ServerDetail = getDeviceDetail($Server['ServerId']);
						if($i % 2 == 0)
						{
							$tablecellbg = "background-color:#efefef;";
						} else {
							$tablecellbg = "background-color:#ffffff;";
						}
						
						echo "<tr style='" . $tablecellbg . "'>";
						
						echo "<td>" . $Client['ClientName'] . "<font style='font-size:80%;color:#545454;'>&nbsp;-&nbsp;" . $Site['SiteName'] . "</font></td><td>" . $Server['ServerName'] . "</td><td>";
						
						foreach($Server['FailedChecks'] as $FailedCheck)
						{
							echo "<img src='https://dashboardgermany1.systemmonitor.eu.com/images/check_" . $FailedCheck[2] . ".gif' style='height:12px;width:13px;'><font style='font-size:80%;'>&nbsp;&nbsp;" . str_replace(array("Skriptüberprüfung - ", "Leistungsüberwachung - ", "SNMP-Überprüfung - ", "Windows-Dienst-Überprüfung - ", "Sicherungsüberprüfung - "), "", $FailedCheck[0]) . " <font style='font-size:80%;color:#545454;'>" . substr(strip_tags($FailedCheck[1]), 0, 100) . " [..]</font></font><br>";
						}
						
						$TimeSinceLastBoot = date_diff(new DateTime($ServerDetail['lastboot']), new DateTime());
						echo "</td><td>" . date('H:i',strtotime($ServerDetail['lastresponse'])) . " <small>Uhr</small></td><td>" . $TimeSinceLastBoot->format("%a <small>Tag(e)</small>, %h <small>Stunde(n)</small>") . "</td></tr>";
						$i++;
					}
				}
			}
		}
		
		echo "</table>";
		?>
	</body>
</html>