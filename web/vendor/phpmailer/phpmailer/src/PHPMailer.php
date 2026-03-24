<?php
namespace PHPMailer\PHPMailer;

class PHPMailer
{
    public $Host = '';
    public $Port = 587;
    public $SMTPSecure = '';
    public $SMTPAuth = false;
    public $Username = '';
    public $Password = '';
    public $CharSet = 'UTF-8';
    public $IsHTML = false;
    public $Subject = '';
    public $Body = '';
    public $AltBody = '';
    
    private $from = '';
    private $fromName = '';
    private $to = [];
    private $isSMTP = false;
    
    const ENCRYPTION_STARTTLS = 'tls';
    const ENCRYPTION_SMTPS = 'ssl';
    
    public function __construct($exceptions = false)
    {
    }
    
    public function isSMTP()
    {
        $this->isSMTP = true;
    }
    
    public function setFrom($address, $name = '')
    {
        $this->from = $address;
        $this->fromName = $name;
    }
    
    public function addAddress($address, $name = '')
    {
        $this->to[] = ['email' => $address, 'name' => $name];
    }
    
    public function set($name, $value)
    {
        $this->$name = $value;
    }
    
    public function isHTML($bool = true)
    {
        $this->IsHTML = $bool;
    }
    
    public function send()
    {
        if (empty($this->to)) {
            throw new Exception('No recipients defined');
        }
        
        $to = $this->to[0]['email'];
        $headers = "From: {$this->from}\r\n";
        $headers .= "Reply-To: {$this->from}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        
        if ($this->IsHTML) {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        } else {
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        }
        
        if ($this->isSMTP) {
            return $this->sendSMTP($headers);
        } else {
            return mail($to, $this->Subject, $this->Body, $headers);
        }
    }
    
    private function sendSMTP($headers)
    {
        $to = $this->to[0]['email'];
        
        $smtp = fsockopen($this->Host, $this->Port, $errno, $errstr, 10);
        if (!$smtp) {
            throw new Exception("SMTP connection failed: $errstr");
        }
        
        stream_set_blocking($smtp, true);
        
        // Läs initialt svar
        $this->getResponse($smtp);
        
        // EHLO
        fwrite($smtp, "EHLO localhost\r\n");
        $this->getResponse($smtp);
        
        // STARTTLS
        if ($this->SMTPSecure === 'tls') {
            fwrite($smtp, "STARTTLS\r\n");
            $this->getResponse($smtp);
            if (!stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("STARTTLS failed");
            }
        }
        
        // AUTH LOGIN
        if ($this->SMTPAuth) {
            fwrite($smtp, "AUTH LOGIN\r\n");
            $this->getResponse($smtp);
            
            fwrite($smtp, base64_encode($this->Username) . "\r\n");
            $this->getResponse($smtp);
            
            fwrite($smtp, base64_encode($this->Password) . "\r\n");
            $this->getResponse($smtp);
        }
        
        // MAIL FROM
        fwrite($smtp, "MAIL FROM:<{$this->from}>\r\n");
        $this->getResponse($smtp);
        
        // RCPT TO
        fwrite($smtp, "RCPT TO:<$to>\r\n");
        $this->getResponse($smtp);
        
        // DATA
        fwrite($smtp, "DATA\r\n");
        $this->getResponse($smtp);
        
        // Headers + body
        $message = "Subject: {$this->Subject}\r\n";
        $message .= "From: {$this->from}\r\n";
        $message .= "To: $to\r\n";
        $message .= "\r\n";
        $message .= $this->Body;
        
        fwrite($smtp, $message . "\r\n.\r\n");
        $this->getResponse($smtp);
        
        // QUIT
        fwrite($smtp, "QUIT\r\n");
        fclose($smtp);
        
        return true;
    }
    
    private function getResponse($smtp)
    {
        $response = '';
        while ($line = fgets($smtp, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        if (strpos($response, '550') !== false || strpos($response, '5') === 0) {
            throw new Exception("SMTP Error: $response");
        }
        return $response;
    }
}

class Exception extends \Exception {}
