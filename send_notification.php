<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require 'config.php';
require 'email_config.php';

function sendAbnormalNotification($machine_id)
{
    global $conn;
    $query = "SELECT machine_name, plant FROM machine WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $machine_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        return false;
    }
    $machine = $result->fetch_assoc();
    $machine_name = $machine['machine_name'];
    $plant = $machine['plant'];

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_USER, EMAIL_FROM_NAME);

        // Ambil semua email aktif
        $result = mysqli_query($conn, "SELECT email FROM notification_settings WHERE is_active=1"); 
        $hasRecipient = false;
        while ($row = mysqli_fetch_assoc($result)) {
            if (filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                $mail->addAddress($row['email']);
                $hasRecipient = true;
            }
        }

        if (!$hasRecipient) {
            echo "Tidak ada email aktif untuk dikirim.";
            return false;
        }

        $mail->isHTML(true);
        $mail->Subject = 'ALERT: Mesin ABNORMAL';
        $mail->Body = "
            <h3 style='color:red;'>⚠️ Mesin Mengalami Gangguan</h3>
            <p><strong>Machine:</strong> {$machine_name}</p>
            <p><strong>Plant:</strong> {$plant}</p>
            <p>Status berubah menjadi <b style='color:red;'>ABNORMAL</b></p>
            <p>Silakan cek <a href='http://localhost/mtc_project/mtc_project/monitor.php'>monitor page</a></p>
            <hr>
            <small>Machine Monitoring System</small>
        ";

        $mail->send();
        echo "Email notifikasi berhasil dikirim!";
        return true;
    } catch (Exception $e) {
        echo "Mailer Error: " . $mail->ErrorInfo;
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}