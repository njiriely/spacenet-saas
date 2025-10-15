<?php
// includes/MikrotikAPI.php
class MikrotikAPI {
    private $socket;
    private $timeout = 3;
    
    public function connect($ip, $username, $password, $port = 8728) {
        $this->socket = @fsockopen($ip, $port, $errno, $errstr, $this->timeout);
        
        if (!$this->socket) {
            throw new Exception("Cannot connect to RouterOS: $errstr ($errno)");
        }
        
        if (!$this->login($username, $password)) {
            throw new Exception("Login failed");
        }
        
        return true;
    }
    
    public function createHotspotUser($username, $package, $durationMinutes) {
        $commands = [
            '/ip/hotspot/user/add',
            '=name=' . $username,
            '=password=' . $this->generatePassword(),
            '=limit-uptime=' . $this->formatDuration($durationMinutes),
            '=rate-limit=' . $package['speed_limit'] . 'M/' . $package['speed_limit'] . 'M',
            '=profile=default'
        ];
        
        return $this->sendCommand($commands);
    }
    
    public function removeHotspotUser($username) {
        $commands = [
            '/ip/hotspot/user/remove',
            '=numbers=' . $username
        ];
        
        return $this->sendCommand($commands);
    }
    
    public function getHotspotUsers() {
        $commands = ['/ip/hotspot/user/print'];
        return $this->sendCommand($commands);
    }
    
    public function kickUser($username) {
        $commands = [
            '/ip/hotspot/active/remove',
            '=numbers=' . $username
        ];
        
        return $this->sendCommand($commands);
    }
    
    private function login($username, $password) {
        $this->sendCommand(['/login']);
        $response = $this->readResponse();
        
        if (isset($response[0]['=ret'])) {
            $hash = $response[0]['=ret'];
            $hashedPassword = md5(chr(0) . $password . pack('H*', $hash));
            
            $this->sendCommand([
                '/login',
                '=name=' . $username,
                '=response=00' . $hashedPassword
            ]);
            
            $response = $this->readResponse();
            return isset($response[0]['!done']);
        }
        
        return false;
    }
    
    private function sendCommand($commands) {
        foreach ($commands as $command) {
            $this->writeString($command);
        }
        $this->writeString('');
        
        return $this->readResponse();
    }
    
    private function writeString($string) {
        $length = strlen($string);
        
        if ($length < 0x80) {
            $lengthStr = chr($length);
        } elseif ($length < 0x4000) {
            $lengthStr = chr($length | 0x80) . chr($length >> 7);
        } elseif ($length < 0x200000) {
            $lengthStr = chr($length | 0x80) . chr(($length >> 7) | 0x80) . chr($length >> 14);
        } elseif ($length < 0x10000000) {
            $lengthStr = chr($length | 0x80) . chr(($length >> 7) | 0x80) . chr(($length >> 14) | 0x80) . chr($length >> 21);
        } else {
            $lengthStr = chr(0x80) . chr(0x80) . chr(0x80) . chr(0x80) . chr($length >> 28) . chr($length >> 21) . chr($length >> 14) . chr($length >> 7) . chr($length);
        }
        
        fwrite($this->socket, $lengthStr . $string);
    }
    
    private function readResponse() {
        $response = [];
        
        while (true) {
            $length = $this->readLength();
            
            if ($length === 0) {
                break;
            }
            
            $data = fread($this->socket, $length);
            $response[] = $this->parseResponse($data);
        }
        
        return $response;
    }
    
    private function readLength() {
        $byte = ord(fread($this->socket, 1));
        
        if ($byte & 0x80) {
            if (($byte & 0xC0) == 0x80) {
                $length = (($byte & ~0xC0) << 8) + ord(fread($this->socket, 1));
            } elseif (($byte & 0xE0) == 0xC0) {
                $length = (($byte & ~0xE0) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
            } else {
                $length = (($byte & ~0xF0) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
            }
        } else {
            $length = $byte;
        }
        
        return $length;
    }
    
    private function parseResponse($data) {
        $parsed = [];
        $lines = explode("\n", $data);
        
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $parsed[$key] = $value;
            }
        }
        
        return $parsed;
    }
    
    private function formatDuration($minutes) {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf("%02d:%02d:00", $hours, $mins);
    }
    
    private function generatePassword($length = 8) {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, $length);
    }
    
    public function __destruct() {
        if ($this->socket) {
            fclose($this->socket);
        }
    }
}