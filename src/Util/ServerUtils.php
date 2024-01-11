<?php

namespace App\Util;

use App\Repository\ConnectionRepository;
use App\Repository\ServerRepository;
use Cocur\Slugify\Slugify;
use Monolog\Logger;

class ServerUtils
{
    public static function runServer(string $computerId, array $user, ServerRepository $serverRepository, ConnectionRepository $connectionRepository, Logger $logger, bool $remove = false): bool{
        $server = $serverRepository->getServerForUser($user);
        $connections = $connectionRepository->getConnectionsByServer($user["id"], $computerId, $server["id"]);

        $slugify = new Slugify();
        $containerName = $user["id"]."_".$slugify->slugify($server["name"], "_");
        $config_name = $containerName.".toml";
        $cmdStart = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? "" : "sudo "; //on linux --- need to add www-data to sudoers for this to work --- temp fix --- need to find better solution

        $ports_str = " -p {$server["serverFrpPort"]}:{$server["serverFrpPort"]}";
        $ipTablesStr = "";

        foreach ($connections as $connection){
            $connection["type"] = mb_strtolower($connection["type"]);
            $ports_str.= " -p {$connection["remotePort"]}:{$connection["remotePort"]}";

            $ipTablesStr .= $cmdStart."iptables -S DOCKER-USER | grep -- \"--dport {$connection["remotePort"]}\" | sed 's/-A/-D/' | while read line; do sudo iptables \$line; done;";
            if($remove) continue;

            $whitelistedIp = array_filter(explode(",", $connection["ipWhitelist"] ?? ""));

            if(!empty($whitelistedIp)) {
                $ipTablesStr.= $cmdStart."iptables -I DOCKER-USER -p {$connection["type"]} --dport {$connection["remotePort"]} -j DROP;";
                foreach ($whitelistedIp as $ip){
                    $ipTablesStr .= $cmdStart."iptables -I DOCKER-USER -p {$connection["type"]} --dport {$connection["remotePort"]} -s {$ip} -j ACCEPT;";
                }
            }

        }


        $dockerDir = dirname(__DIR__)."/../docker";
        $configPath = $dockerDir."/config/".$config_name;
        $imageName = "moj-frp-server";

        if(is_dir($dockerDir."/config") === false){
            mkdir($dockerDir."/config");
        }

        $config_str = self::generateServerTomlConfig($server, $connections);
        file_put_contents($configPath, $config_str);
        $configPath = realpath($configPath);

        if(shell_exec($cmdStart."docker ps -all | grep {$containerName}") !== null){
            $logger->info("Stopping and removing container {$containerName}", );
            shell_exec($cmdStart."docker stop {$containerName}");
            shell_exec($cmdStart."docker rm {$containerName}");
        }

        shell_exec($ipTablesStr);
        if($remove && sizeof($connections)-1 < 1){
            if(file_exists($configPath)){
                unlink($configPath);
            }
            return true;
        }

        $dockerCommand = $cmdStart."docker run -d --restart=always --name {$containerName} -v {$configPath}:/app/frps.toml {$ports_str} {$imageName}";
        $shellOutput = shell_exec($cmdStart."{$dockerCommand}");
        $logger->info("Running container {$containerName} with command {$dockerCommand}", [$shellOutput === null ? "fail" : "success", $shellOutput ?? $dockerCommand]);
        return !($shellOutput === null);
    }

    public static function generateServerTomlConfig(array $server, array $connections): string
    {
        $config_str = "bindPort = {$server["serverFrpPort"]}\n";
        $config_str .= 'auth.method = "token"'.PHP_EOL;
        $config_str .= "auth.token = \"{$server["password"]}\"\n";
        $config_str .= "\n";

        foreach ($connections as $connection){
            $connection["type"] = mb_strtolower($connection["type"]);
            $config_str .= "[[proxies]]\n";
            $config_str .= "name = \"{$connection["name"]}\"\n";
            $config_str .= "type = \"{$connection["type"]}\"\n";
            $config_str .= "remotePort = {$connection["remotePort"]}\n"; //on server local port is remote port
            $config_str .= "localPort = {$connection["remotePort"]}\n"; //on server local port is remote port
            $config_str .= "\n";
        }

        return $config_str;
    }
}