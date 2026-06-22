<?php
/**
 * 简单 SMTP 邮件发送类（纯 PHP，无依赖）
 */
class SimpleSMTPMailer
{
    private $smtpHost;
    private $smtpPort;
    private $smtpUser;
    private $smtpPass;
    private $timeout = 30;
    
    public function __construct(string $host, int $port, string $user = '', string $pass = '')
    {
        $this->smtpHost = $host;
        $this->smtpPort = $port;
        $this->smtpUser = $user;
        $this->smtpPass = $pass;
    }
    
    public function send(string $to, string $subject, string $body, string $from = ''): bool
    {
        $from = $from ?: $this->smtpUser;
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $from,
            'Reply-To: ' . $from,
            'X-Mailer: PHP/' . phpversion(),
        ];
        
        // 尝试使用 mail() 函数（如果服务器已配置）
        if (function_exists('mail') && ini_get('sendmail_path') || ini_get('SMTP')) {
            return @mail($to, $subject, $body, implode("\r\n", $headers));
        }
        
        // 否则使用 socket 直接连接 SMTP
        return $this->sendViaSocket($to, $subject, $body, $from, $headers);
    }
    
    private function sendViaSocket(string $to, string $subject, string $body, string $from, array $headers): bool
    {
        $socket = @fsockopen($this->smtpHost, $this->smtpPort, $errno, $errstr, $this->timeout);
        if (!$socket) {
            error_log("SMTP 连接失败: $errstr ($errno)");
            return false;
        }
        
        stream_set_timeout($socket, $this->timeout);
        
        try {
            // 读取欢迎消息
            $this->readResponse($socket);
            
            // HELO
            $this->sendCommand($socket, 'HELO ' . gethostname());
            
            // 如果需要认证
            if ($this->smtpUser && $this->smtpPass) {
                $this->sendCommand($socket, 'AUTH LOGIN');
                $this->sendCommand($socket, base64_encode($this->smtpUser));
                $this->sendCommand($socket, base64_encode($this->smtpPass));
            }
            
            // MAIL FROM
            $this->sendCommand($socket, 'MAIL FROM: <' . $from . '>');
            
            // RCPT TO
            $this->sendCommand($socket, 'RCPT TO: <' . $to . '>');
            
            // DATA
            $this->sendCommand($socket, 'DATA');
            
            // 邮件内容
            $email = "To: {$to}\r\n";
            $email .= "Subject: {$subject}\r\n";
            $email .= implode("\r\n", $headers) . "\r\n";
            $email .= "\r\n";
            $email .= $body . "\r\n";
            $email .= ".\r\n";
            
            $this->sendCommand($socket, $email, false);
            
            // QUIT
            $this->sendCommand($socket, 'QUIT');
            
            fclose($socket);
            return true;
            
        } catch (\Throwable $e) {
            error_log('SMTP 发送失败: ' . $e->getMessage());
            @fclose($socket);
            return false;
        }
    }
    
    private function sendCommand($socket, string $command, bool $readResponse = true): string
    {
        fwrite($socket, $command . "\r\n");
        return $readResponse ? $this->readResponse($socket) : '';
    }
    
    private function readResponse($socket): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $response;
    }
}
