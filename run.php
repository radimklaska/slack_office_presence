<?php

$names = array(
        "00:00:00:00:00:00" => 'TODO-NAME',
);

// @Todo: Config as ecxternal git ignored file.
$timestamp = time();
$path = "/tmp/";
$prefix = "detected_devices_";
$file = $path . $prefix . $timestamp . ".txt";

// @Todo: configurable IP range.
// @Todo: configurable interface.
exec("sudo arp-scan -q -r 10 -I eth0 192.168.0.1-192.168.0.255 | awk 'NR > 2 {print $2}' | awk '{if (a) print a; a=b; b=c; c=$0}' > " . $file);

$mac_old = exec("ls -l /tmp | awk '{print $9}' | grep detected_devices_ | sort -k1.15n | tail -2 | head -n 1");
// var_dump("old: " . $mac_old);
$mac_new = exec("ls -l /tmp | awk '{print $9}' | grep detected_devices_ | sort -k1.15n | tail -1");
// var_dump("new: " . $mac_new);

$mac_old = file($path . $mac_old);
$mac_new = file($path . $mac_new);

// Cleanup
sort($mac_new);
sort($mac_old);
foreach ($mac_old as $key => $tmp) {
	$mac_old[$key] = trim($tmp);
}
foreach ($mac_new as $key => $tmp) {
        $mac_new[$key] = trim($tmp);
}

// Any change?
if ($mac_old != $mac_new) {
	$connected = array_diff($mac_new, $mac_old);
	$disconneted = array_diff($mac_old, $mac_new);
	
	foreach ($connected as $key => $tmp) {
        	$connected[$key] = (isset($names[$tmp]) ? $names[$tmp] . ' (' . $tmp . ')' : $tmp);
	}

        foreach ($disconneted as $key => $tmp) {
                $disconneted[$key] = (isset($names[$tmp]) ? $names[$tmp] : $tmp);
        }

        foreach ($mac_new as $key => $tmp) {
                $mac_new[$key] = (isset($names[$tmp]) ? $names[$tmp] : $tmp);
        }

	$message = "======================== " . date('H:i') . " ========================\n";

	if (count($connected) > 0) {
		$message .= "*Prisel:*\n";		        
	        foreach ($connected as $key => $tmp) {
			$message .= "* " . $tmp . "\n";
	        }
	}
       
        if (count($disconneted) > 0) {
                $message .= "*Odesel:*\n";
                foreach ($disconneted as $key => $tmp) {
                        $message .= "* " . $tmp . "\n";
                }
        }
        
        if (count($mac_new) > 0) {
                $message .= "*Pritomen:*\n";
                foreach ($mac_new as $key => $tmp) {
                        $message .= "* " . $tmp . "\n";
                }
        }

	if ((count($connected) + count($disconneted)) > 0) {
		slack ($message);
	}
}

function slack($message, $room = "botnet", $icon = ":ghost:") {
        $room = ($room) ? $room : "botnet";
        $data = "payload=" . json_encode(array(
                "channel"       =>  "#{$room}",
		            "username"      =>  "Spybot",
                "text"          =>  $message,
                "icon_emoji"    =>  $icon
            ));
	
	// You can get your webhook endpoint from your Slack settings
        // @Todo: Configurable slack endpoint
        $ch = curl_init("SLACK-ENDPOINT");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
	
	// Laravel-specific log writing method
        // Log::info("Sent to Slack: " . $message, array('context' => 'Notifications'));
        return $result;
}
