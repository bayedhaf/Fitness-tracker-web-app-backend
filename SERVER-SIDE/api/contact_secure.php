<?php
header("Content-Type: application/json");
require 'vendor/autoload.php';
$config = require 'config.php';

$response = ['success' => false, 'message' => ''];

try {
    // Verify reCAPTCHA
    if (empty($_SERVER['HTTP_RECAPTCHA_TOKEN'])) {
        throw new Exception("reCAPTCHA token missing", 400);
    }

    $recaptchaUrl = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptchaResponse = file_get_contents($recaptchaUrl . '?secret=' . $config['recaptcha']['secret_key'] . '&response=' . $_SERVER['HTTP_RECAPTCHA_TOKEN']);
    $recaptchaData = json_decode($recaptchaResponse);

    if (!$recaptchaData->success || $recaptchaData->score < 0.5) {
        throw new Exception("reCAPTCHA verification failed", 400);
    }

    // Process form data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $required = ['fname', 'lname', 'email', 'message'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field", 400);
        }
    }

    $firstName = htmlspecialchars($data['fname']);
    $lastName = htmlspecialchars($data['lname']);
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars($data['message']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format", 400);
    }

    // Send email
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $config['email']['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['email']['username'];
    $mail->Password = $config['email']['password'];
    $mail->SMTPSecure = $config['email']['encryption'];
    $mail->Port = $config['email']['port'];

    $mail->setFrom($config['email']['username'], 'Life Advanced Fitness');
    $mail->addAddress('admin@yourdomain.com');
    $mail->addReplyTo($email, "$firstName $lastName");

    $mail->isHTML(true);
    $mail->Subject = "New Contact: $firstName $lastName";
    $mail->Body = "<p><strong>Name:</strong> $firstName $lastName</p>
                  <p><strong>Email:</strong> $email</p>
                  <p><strong>Message:</strong></p><p>$message</p>";

    if (!$mail->send()) {
        throw new Exception("Failed to send message", 500);
    }

    $response = ['success' => true, 'message' => 'Your message was sent successfully!'];
    http_response_code(200);
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>