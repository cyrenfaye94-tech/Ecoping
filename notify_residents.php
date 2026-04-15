<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
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

        $residents = $conn->query("SELECT * FROM residents WHERE deleted_at IS NULL");

while($row = $residents->fetch_assoc()){
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp-relay.brevo.com';
        $mail->SMTPAuth = true;

        // ✅ use environment variables for BOTH
        $mail->Username = getenv('BREVO_SMTP_USERNAME');
        $mail->Password = getenv('BREVO_SMTP_PASSWORD');

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

                $mail->setFrom('cyrenfaye94@gmail.com', 'ECOPING Notification');
                $mail->addAddress($res['email'], $res['name']);
                $mail->isHTML(true);
                $mail->Subject = '🚛 Garbage Truck Incoming!';
                $mail->Body = "<div style='font-family: Arial, sans-serif;'><p>Hello ".$res['name'].",</p><p>🚛 The garbage truck for your area (".$sched['street'].", Block ".$sched['block_number'].") is arriving in 5 minutes!</p><p>Please prepare your garbage now.</p></div>";

                $mail->send();

                // Log
                $conn->query("INSERT INTO notifications_log (resident_id, schedule_id) VALUES (".$res['id'].",".$sched['id'].")");
            } catch (Exception $e){
                // log error
                file_put_contents('email_errors.log', $mail->ErrorInfo."\n", FILE_APPEND);
            }
        }
    }
}
