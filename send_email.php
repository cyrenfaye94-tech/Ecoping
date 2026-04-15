<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

function sendEmailToResidents($subject, $message, $conn, $barangay = null, $purok = null) {
    $successCount = 0;
    $failCount    = 0;
    $errors       = [];

    // Query uses correct column names: barangay and purok
    $query = "SELECT email, name FROM residents WHERE deleted_at IS NULL AND email_enabled = 1 AND email IS NOT NULL AND email != ''";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($barangay && $purok) {
        $stmt = $conn->prepare("SELECT email, name FROM residents WHERE deleted_at IS NULL AND email_enabled = 1 AND email IS NOT NULL AND email != '' AND barangay = ? AND purok = ?");
        $stmt->bind_param("ss", $barangay, $purok);
        $stmt->execute();
        $res = $stmt->get_result();
    }

    // If no matching residents found, fall back to ALL active residents
    if (!$res || $res->num_rows == 0) {
        $stmt = $conn->prepare("SELECT email, name FROM residents WHERE deleted_at IS NULL AND email_enabled = 1 AND email IS NOT NULL AND email != ''");
        $stmt->execute();
        $res = $stmt->get_result();

        if (!$res || $res->num_rows == 0) {
            return [
                'success' => false,
                'message' => 'No residents with email notifications enabled were found.'
            ];
        }
    }

    while ($row = $res->fetch_assoc()) {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp-relay.brevo.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('BREVO_SMTP_USERNAME');
            $mail->Password = getenv('BREVO_SMTP_PASSWORD');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->Timeout    = 10;

            $mail->setFrom('cyrenfaye94@gmail.com', 'ECOPING System');
            $mail->addAddress($row['email'], $row['name']);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->AltBody = strip_tags($message);

            $mail->send();
            $successCount++;

        } catch (Exception $e) {
            $failCount++;
            $errors[] = "Failed to send to {$row['email']}: " . $mail->ErrorInfo;
        }

        $mail->clearAddresses();
        $mail->clearAttachments();
    }

    if ($successCount > 0) {
        return [
            'success' => true,
            'message' => "Sent to $successCount resident(s). Failed: $failCount.",
            'details' => $errors
        ];
    } else {
        return [
            'success' => false,
            'message' => "Failed to send all emails. Errors: " . implode(', ', $errors),
            'details' => $errors
        ];
    }
}
?>