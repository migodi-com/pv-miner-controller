<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use phpseclib3\Net\SSH2;
use phpseclib3\Net\SFTP;
use Yosymfony\Toml\Toml;
use Yosymfony\Toml\TomlBuilder;

class DeviceController extends Controller
{
    public function adjust()
    {
        $power = request()->post('power');
        
        if(!$power) {
            return back();
        }
        
        $availablePower = $power - 80;
        $powerPerHashboard = 100;
        $maxHashboards = floor($availablePower / $powerPerHashboard);
        
        /**
         * Connect SFTP
         */
        $sftp = new SFTP(config('device.credentials.host'), config('device.credentials.port'));
        if(config('device.host_key')) {
            if (config('device.host_key') != $sftp->getServerPublicHostKey()) {
                throw new \Exception('Host key verification failed');
            }
        }
        if (!$sftp->login(config('device.credentials.username'), config('device.credentials.password'))) {
            throw new \Exception('Login failed');
        }
        
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
        
        /**
         * Adjust config
         */
        $bosminerConfig = $sftp->get('/etc/bosminer.toml');
        
        $bosminerConfig = preg_replace(">\npsu_power_limit = \d+>", "\npsu_power_limit = ".$availablePower, $bosminerConfig);
        
        if($maxHashboards == 1) {
            // 1 an
            $bosminerConfig = preg_replace(">\[hash_chain\.6\]\s*\n\s*enabled = false>", "[hash_chain.6]\nenabled = true", $bosminerConfig);
            $bosminerConfig = preg_replace(">\[hash_chain\.7\]\s*\n\s*enabled = true>", "[hash_chain.7]\nenabled = false", $bosminerConfig);
            $bosminerConfig = preg_replace(">\[hash_chain\.8\]\s*\n\s*enabled = true>", "[hash_chain.8]\nenabled = false", $bosminerConfig);
        } elseif($maxHashboards == 2) {
            // 2 an
            $bosminerConfig = preg_replace(">\[hash_chain\.6\]\s*\n\s*enabled = false>", "[hash_chain.6]\nenabled = true", $bosminerConfig);
            $bosminerConfig = preg_replace(">\[hash_chain\.7\]\s*\n\s*enabled = false>", "[hash_chain.7]\nenabled = true", $bosminerConfig);
            $bosminerConfig = preg_replace(">\[hash_chain\.8\]\s*\n\s*enabled = true>", "[hash_chain.8]\nenabled = false", $bosminerConfig);
        } elseif($maxHashboards >= 3) {
            // 3 an
            $bosminerConfig = preg_replace(">\[hash_chain\.6\]\s*\n\s*enabled = false>", "[hash_chain.6]\nenabled = true", $bosminerConfig);
            $bosminerConfig = preg_replace(">\[hash_chain\.7\]\s*\n\s*enabled = false>", "[hash_chain.7]\nenabled = true", $bosminerConfig);
            $bosminerConfig = preg_replace(">\[hash_chain\.8\]\s*\n\s*enabled = false>", "[hash_chain.8]\nenabled = true", $bosminerConfig);
        } else {
            // alle aus
            $bosminerConfig = preg_replace(">\[hash_chain\.6\]\s*\n\s*enabled = true>", "[hash_chain.6]\nenabled = false", $bosminerConfig);
            $bosminerConfig = preg_replace(">\[hash_chain\.7\]\s*\n\s*enabled = true>", "[hash_chain.7]\nenabled = false", $bosminerConfig);
            $bosminerConfig = preg_replace(">\[hash_chain\.8\]\s*\n\s*enabled = true>", "[hash_chain.8]\nenabled = false", $bosminerConfig);
        }
        
        $sftp->put('/etc/bosminer.toml', $bosminerConfig);
        
        $ssh->exec('/etc/init.d/bosminer restart');
        
        
        return redirect()->route('dashboard')->with('status', 'Device configuration successfully adjusted.');
    }
    
//     protected function buildConfig($config, $data)
//     {
//         if(is_array($data)) {
//             foreach($data as $key => $value) {
//                 if(is_array($value) && isset($value[0])) {
//                     $config->addArrayOfTable($key);
//                     $this->buildConfig($config, $value);
//                     /**
//                      * ...
//                      */
//                 }
//             }
//         }
//         return $config;
//     }
}
