<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use phpseclib3\Net\SSH2;
use phpseclib3\Net\SFTP;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $minerStatus = $this->getMinerStatus([]);

        return view('dashboard', compact('minerStatus'));
    }
    
    public function getMinerStatus($miner)
    {
        /**
         * Connect SSH
         */
        $ssh = new SSH2(config('device.credentials.host'), config('device.credentials.port'));
        if(config('device.host_key')) {
            if (config('device.host_key') != $ssh->getServerPublicHostKey()) {
                throw new \Exception('Host key verification failed');
            }
        }
        if (!$ssh->login(config('device.credentials.username'), config('device.credentials.password'))) {
            throw new \Exception('Login failed');
        }
        
        $result = [];
        foreach(['stats', 'summary', 'tunerstatus'] as $command) {
            $response = $this->sendMinerCommand($ssh, $command);
            if(!$response) {
                break;
            }
            $result = array_merge($result, $response);
        }
        return empty($result) ? null : $result;
    }
    
    public function sendMinerCommand($ssh, $command)
    {
        $line = $ssh->exec('echo -n \'{"command":"'.$command.'"}\' | nc 127.0.0.1 4028');
        
        if(strlen($line) == 0) {
            return;
        }
        
        $line = trim($line);
        
        if(substr($line, 0, 1) == '{') {
            $line = str_replace('}{', '},{', $line);
            return json_decode($line, true);
        }
        
        $data = [];
        
        $objs = explode('|', $line);
        foreach($objs as $obj) {
            if(strlen($obj) > 0) {
                $items = explode(',', $obj);
                $item = $items[0];
                $id = explode('=', $items[0], 2);
                
                if(count($id) == 1 or !ctype_digit($id[1])) {
                    $name = $id[0];
                } else {
                    $name = $id[0].$id[1];
                }
                
                if(strlen($name) == 0) {
                    $name = 'null';
                }
                
                if(isset($data[$name])) {
                    $num = 1;
                    while(isset($data[$name.$num])) {
                        $num++;
                    }
                    $name .= $num;
                }
                
                $counter = 0;
                foreach($items as $item) {
                    $id = explode('=', $item, 2);
                    if(count($id) == 2) {
                        $data[$name][$id[0]] = $id[1];
                    } else {
                        $data[$name][$counter] = $id[0];
                    }
                    $counter++;
                }
            }
        }
    }
}
