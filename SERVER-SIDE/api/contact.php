<?php
header("Content-Type: application/json");
require 'vendor/autoload.php'; // For PHPMailer

$response = [
    'success' => false,
    'message' => ''
];

try {
    // Get raw POST data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Validate required fields
    $required = ['fname', 'lname', 'email', 'message'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field", 400);
        }
    }

    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format", 400);
    }

    // Sanitize inputs
    $firstName = htmlspecialchars($data['fname']);
    $lastName = htmlspecialchars($data['lname']);
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars($data['message']);

    // Save to database (optional)
    // $this->saveContactToDatabase($firstName, $lastName, $email, $message);

    // Send email notification
    $mailSent = $this->sendEmailNotification($firstName, $lastName, $email, $message);

    if (!$mailSent) {
        throw new Exception("Failed to send email notification", 500);
    }

    $response = [
        'success' => true,
        'message' => 'Your message was successfully sent!'
    ];

    http_response_code(200);
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);

function sendEmailNotification($firstName, $lastName, $fromEmail, $message) {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.yourdomain.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'contact@yourdomain.com';
        $mail->Password = 'your-email-password';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('contact@yourdomain.com', 'Life Advanced Fitness');
        $mail->addAddress('admin@yourdomain.com', 'Admin');
        $mail->addReplyTo($fromEmail, "$firstName $lastName");

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'New Contact Form Submission';
        $mail->Body = "
            <h2>New Contact Form Submission</h2>
            <p><strong>Name:</strong> $firstName $lastName</p>
            <p><strong>Email:</strong> $fromEmail</p>
            <p><strong>Message:</strong></p>
            <p>$message</p>
        ";
        $mail->AltBody = "Name: $firstName $lastName\nEmail: $fromEmail\nMessage:\n$message";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

function saveContactToDatabase($firstName, $lastName, $email, $message) {
    // Implement your MongoDB/MySQL storage here
    // Example with MongoDB:
    /*
    $db = Database::getInstance()->getDb();
    $result = $db->contacts->insertOne([
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $email,
        'message' => $message,
        'createdAt' => new MongoDB\BSON\UTCDateTime()
    ]);
    return $result->getInsertedCount() > 0;
    */
}
?>