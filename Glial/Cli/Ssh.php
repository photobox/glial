<?php

namespace Glial\Cli;

class Ssh
{

    const DEBUG_ON = 1;
    const DEBUG_OFF = 0;
    const DEBUG_PARTIAL = 2; //only display cmd

    
    const SSH_PROMPT_STD = 1;
    const SSH_PROMPT_FOUND = 2;
    const SSH_PROMPT_TIME_OUT = 3;
    
    // debug

    private $debug = 0;
    // SSH Host 
    private $ssh_host;
    // SSH Port 
    private $ssh_port;
    // SSH Server Fingerprint 
    private $ssh_server_fp = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
    // SSH Username 
    private $ssh_auth_user;
    // SSH Private Key Passphrase (null == no passphrase) 
    private $ssh_auth_pass;
    // SSH Public Key File 
    private $ssh_auth_pub = '/home/username/.ssh/id_rsa.pub';
    // SSH Private Key File 
    private $ssh_auth_priv = '/home/username/.ssh/id_rsa';
    // SSH Connection 
    private $connection;
    private $stdio;
    
    //time out for wait prompt if no new answer in second
    private $idle_time_out = 5;
    private $wait_time = 500000;
    

    static public function testAccount($host, $port, $login, $password)
    {
        $connection = ssh2_connect($host, $port);

        if (ssh2_auth_password($connection, $login, $password)) {
            return true;
        } else {
            return false;
        }
    }

    public function __construct($host, $port, $login, $password)
    {
        $this->ssh_host = $host;
        $this->ssh_port = $port;
        $this->ssh_auth_user = $login;
        $this->ssh_auth_pass = $password;
    }

    public function connect($debug = self::DEBUG_OFF)
    {

        $this->debug = $debug;

        if (!($this->connection = @ssh2_connect($this->ssh_host, $this->ssh_port))) {

            return false;
            //throw new \Exception('Cannot connect to server');
        }

        /*
          $fingerprint = ssh2_fingerprint($this->connection, SSH2_FINGERPRINT_MD5 | SSH2_FINGERPRINT_HEX);

          if (strcmp($this->ssh_server_fp, $fingerprint) !== 0) {
          throw new Exception('Unable to verify server identity!');
          } */

        /*
          if (!ssh2_auth_pubkey_file($this->connection, $this->ssh_auth_user, $this->ssh_auth_pub, $this->ssh_auth_priv, $this->ssh_auth_pass)) {
          throw new Exception('Autentication rejected by server');
          } */

        if (!@ssh2_auth_password($this->connection, $this->ssh_auth_user, $this->ssh_auth_pass)) {
            return false;
        }
        
        return true;
    }

    public function exec($cmd)
    {
        if (!($stream = @ssh2_exec($this->connection, $cmd))) {

            return false;
            //throw new \Exception('GLI-885 : SSH command failed');
        }
        stream_set_blocking($stream, true);
        $data = "";
        while ($buf = fread($stream, 4096)) {
            $data .= $buf;
        }
        fclose($stream);
        return $data;
    }

    public function disconnect()
    {
        $this->exec('echo "EXITING" && exit;');
        $this->connection = null;
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    public function scp($cmd, $password, $password2)
    {
        
    }

    public function getconnection()
    {
        return $this->connection;
    }

    public function shellCmd($cmd)
    {
        if ($this->debug === self::DEBUG_PARTIAL) {
            echo $cmd."\n";
        }

        fwrite($this->stdio, $cmd . "\n");
    }

    static public function getRegexPrompt()
    {
        // best tools => http://www.regexper.com/
        //[alequoy@SEQ-DWS-02 ~]
        
        
        //return '/[\w-\d_-]+@[\w\d_-]+:[\~]?(?:\/[\w-\d_-]+)*(?:\$|\#)[\s]?/';
        
        //return '/(?:\$|\#)/';
        return '/(?:[\w-\d_-]+@[\w\d_-]+:[\~]?(?:\/[\w-\d_-]+)*(?:\$|\#)[\s]?)|^(?:\$|\#)\s+$|(?:\[(?:[\d\w-_]+)@(?:[\d\w-_]+)\s+\~?\](?:\$|\#)\s*)/Um';

    }
    
    

    public function waitPrompt(&$output,$testPhrase = '')
    {
        
        $output = '';
        $regex = self::getRegexPrompt();
        $wait = true;
        
        $waiting = ['/','-','\\','|'];
        $i = 0;
        
        
        $wait_time_max = $this->idle_time_out * 1000000;
        $waited = 0;
        
        do {
            $i++;
            $buffer = fgets($this->stdio);
            // add pause if nothing chose waiting prompt
            if (empty($buffer)) {

                if ($this->debug === self::DEBUG_ON) {
                    
                    $mod = $i % 4;
                    echo " ".$waiting[$mod];
                    echo "\033[2D";
                }
                
                $waited += $this->wait_time;
                usleep($this->wait_time);
                
                if ($waited >= $wait_time_max)
                {
                    //echo Color::getColoredString(" Time out exceded ", "red");
                    return self::SSH_PROMPT_TIME_OUT;
                }
                
                continue;
            }
            
            $waited = 0;
            $output .= $buffer;
            
            if ($this->debug === self::DEBUG_ON) {
                echo $buffer;
            }

            \preg_match_all(self::getRegexPrompt(), $buffer, $output_array);

            if (count($output_array[0]) === 1) {
                return self::SSH_PROMPT_STD;
            }

            if (!empty($testPhrase)) {
                \preg_match_all("/" . $testPhrase . "/", $buffer, $output_array);

                if (count($output_array[0]) === 1) {
                    return self::SSH_PROMPT_FOUND;
                }
            }
        } while ($wait);
    }

    public function whereis($cmd)
    {
        $paths = $this->exec("whereis " . $cmd);
        
        
        debug($paths);
        
        $tmp = trim(explode(" ", trim(explode(":", $paths)[1]))[0]);
        
        if (empty($tmp))
        {
            throw new \Exception("GLI-059 : Impossible to find : ".$cmd."\n return : ".$paths);
        }
        
        return $tmp;
    }

    
    /*
    public function testPrompt($line)
    {
        $output_array = [];

        \preg_match_all(self::getRegexPrompt(), $line, $output_array);

        if (count($output_array[0]) === 1) {
            return true;
        } else {
            return false;
        }
    }*/

    public function userAdd($stdio, $login, $password)
    {

        $cmd = "useradd -ou 0 -g 0 pmacontrol";
        $cmd = "passwd pmacontrol";
    }

    public function openShell()
    {

        if (!($stdio = ssh2_shell($this->connection, "xterm"))) {
            echo "[FAILED] to open a virtual shell\n";
            exit(1);
        }

        echo "Virtual shell opened\n";

        $this->stdio = $stdio;
        
        
    }

    public function test()
    {
        
    }

}
