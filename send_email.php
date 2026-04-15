<?php
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

    $from_email = 'noreply@' . $_SERVER['HTTP_HOST'];
    $from_name = 'ECOPING System';

    while ($row = $res->fetch_assoc()) {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . $from_name . " <" . $from_email . ">\r\n";

        $to = $row['email'];

        if (mail($to, $subject, $message, $headers)) {
            $successCount++;
        } else {
            $failCount++;
            $errors[] = "Failed to send to {$row['email']}";
        }
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
