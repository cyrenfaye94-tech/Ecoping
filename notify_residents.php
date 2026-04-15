<?php
include 'db_connect.php';

// Get current day
$today = date("l");  // e.g., Monday
$now_time = date("H:i");

// Get all schedules today
$schedules = $conn->query("SELECT * FROM schedules WHERE collection_day='$today'");

while($sched = $schedules->fetch_assoc()){
    // calculate 5 minutes before schedule
    $sched_time = date("H:i", strtotime($sched['time']));
    $notify_time = date("H:i", strtotime($sched['time'] . "-5 minutes"));

    if($now_time == $notify_time){
        // Note: Schedules table uses 'street' and 'block_number', which are distinct from residents' 'barangay' and 'purok'
        // For now, send to all residents since we can't directly match schedules to residents
        // TODO: Implement a proper location mapping system between residents and schedules

        $residents = $conn->query("SELECT id, email, name FROM residents WHERE deleted_at IS NULL AND email_enabled = 1 AND email IS NOT NULL AND email != ''");

        $from_email = 'noreply@' . $_SERVER['HTTP_HOST'];
        $from_name = 'ECOPING Notification';

        while($row = $residents->fetch_assoc()){
            $subject = '🚛 Garbage Truck Incoming!';
            $body = "<div style='font-family: Arial, sans-serif;'><p>Hello ".$row['name'].",</p><p>🚛 The garbage truck for your area (".$sched['street'].", Block ".$sched['block_number'].") is arriving in 5 minutes!</p><p>Please prepare your garbage now.</p></div>";

            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: " . $from_name . " <" . $from_email . ">\r\n";

            if (mail($row['email'], $subject, $body, $headers)) {
                // Log successful notification
                $conn->query("INSERT INTO notifications_log (resident_id, schedule_id) VALUES (".$row['id'].",".$sched['id'].")");
            } else {
                // Log error
                file_put_contents('email_errors.log', "Failed to send to {$row['email']} at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
            }
        }
    }
}
?>
