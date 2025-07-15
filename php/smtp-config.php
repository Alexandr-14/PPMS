<?php
// SMTP Email Configuration for PPMS
// Using Gmail SMTP with App Password

class SimpleMailer {
    private $smtp_host = 'smtp.gmail.com';
    private $smtp_port = 587;
    private $smtp_username;
    private $smtp_password;
    private $from_email;
    private $from_name;
    
    public function __construct($email, $app_password, $from_name = 'PPMS System') {
        $this->smtp_username = $email;
        $this->smtp_password = $app_password;
        $this->from_email = $email;
        $this->from_name = $from_name;
    }
    
    public function sendEmail($to_email, $to_name, $subject, $html_body, $attachments = []) {
        // Simple fallback method using PHP mail() with Gmail SMTP settings
        // This requires your server to have mail() configured with SMTP

        // Create email headers
        $headers = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "From: {$this->from_name} <{$this->from_email}>";
        $headers[] = "Reply-To: {$this->from_email}";
        $headers[] = "X-Mailer: PPMS Email System";

        $header_string = implode("\r\n", $headers);

        // Try to send email using PHP's mail() function
        $success = mail($to_email, $subject, $html_body, $header_string);

        return $success;
    }
    
    // Alternative method using socket connection (more reliable)
    public function sendEmailSMTP($to_email, $to_name, $subject, $html_body) {
        try {
            // Create socket connection to Gmail SMTP
            $socket = fsockopen($this->smtp_host, $this->smtp_port, $errno, $errstr, 30);
            
            if (!$socket) {
                throw new Exception("Could not connect to SMTP server: $errstr ($errno)");
            }
            
            // Read initial response
            $response = fgets($socket, 512);
            if (substr($response, 0, 3) != '220') {
                throw new Exception("SMTP Error: $response");
            }
            
            // Send EHLO
            fputs($socket, "EHLO localhost\r\n");
            $response = fgets($socket, 512);
            
            // Start TLS
            fputs($socket, "STARTTLS\r\n");
            $response = fgets($socket, 512);
            
            // Enable crypto
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            
            // Send EHLO again after TLS
            fputs($socket, "EHLO localhost\r\n");
            $response = fgets($socket, 512);
            
            // Authenticate
            fputs($socket, "AUTH LOGIN\r\n");
            $response = fgets($socket, 512);
            
            fputs($socket, base64_encode($this->smtp_username) . "\r\n");
            $response = fgets($socket, 512);
            
            fputs($socket, base64_encode($this->smtp_password) . "\r\n");
            $response = fgets($socket, 512);
            
            if (substr($response, 0, 3) != '235') {
                throw new Exception("SMTP Authentication failed: $response");
            }
            
            // Send email
            fputs($socket, "MAIL FROM: <{$this->from_email}>\r\n");
            $response = fgets($socket, 512);
            
            fputs($socket, "RCPT TO: <$to_email>\r\n");
            $response = fgets($socket, 512);
            
            fputs($socket, "DATA\r\n");
            $response = fgets($socket, 512);
            
            // Email content
            $email_content = "From: {$this->from_name} <{$this->from_email}>\r\n";
            $email_content .= "To: $to_name <$to_email>\r\n";
            $email_content .= "Subject: $subject\r\n";
            $email_content .= "MIME-Version: 1.0\r\n";
            $email_content .= "Content-Type: text/html; charset=UTF-8\r\n";
            $email_content .= "\r\n";
            $email_content .= $html_body;
            $email_content .= "\r\n.\r\n";
            
            fputs($socket, $email_content);
            $response = fgets($socket, 512);
            
            // Quit
            fputs($socket, "QUIT\r\n");
            fclose($socket);
            
            return substr($response, 0, 3) == '250';
            
        } catch (Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            return false;
        }
    }
}

// Email configuration - UPDATE THESE VALUES
function getEmailConfig() {
    return [
        'gmail_email' => 'iskandardzulqarnain0104@gmail.com',  // Your Gmail from GitHub profile
        'app_password' => 'khfroloeubtuyvgb',      // Replace with your Gmail App Password
        'from_name' => 'Perwira Parcel Management System'
    ];
}
?>
