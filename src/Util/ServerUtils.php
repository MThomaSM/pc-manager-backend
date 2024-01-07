<?php

namespace App\Util;

use App\Repository\ConnectionRepository;
use App\Repository\ServerRepository;
use Cocur\Slugify\Slugify;
use Monolog\Logger;

class ServerUtils
{
    public static function runServer(string $computerId, array $user, ServerRepository $serverRepository, ConnectionRepository $connectionRepository, Logger $logger): string|false|null{
        $server = $serverRepository->getServerForUser($user);
        $connections = $connectionRepository->getConnectionsByServer($user["id"], $computerId, $server["id"]);

        $slugify = new Slugify();
        $containerName = $user["id"]."_".$slugify->slugify($server["name"], "_");
        $config_name = $containerName.".toml";

        $ports_str = " -p {$server["serverFrpPort"]}:{$server["serverFrpPort"]}";

        foreach ($connections as $connection){
            $ports_str.= " -p {$connection["remotePort"]}:{$connection["remotePort"]}";
        }

        $dockerDir = dirname(__DIR__)."/../docker";
        $configPath = $dockerDir."/config/".$config_name;
        $imageName = "moj-frp-server";
        $config_str = self::generateServerTomlConfig($server, $connections);
        file_put_contents($configPath, $config_str);
        $configPath = realpath($configPath);
        $cmdStart = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? "" : "sudo "; //on linux --- need to add www-data to sudoers for this to work --- temp fix --- need to find better solution

        if(shell_exec($cmdStart."docker ps -all | grep {$containerName}") !== null){
            $logger->info("Stopping and removing container {$containerName}", );
            shell_exec($cmdStart."docker stop {$containerName}");
            shell_exec($cmdStart."docker rm {$containerName}");
        }

        $dockerCommand = $cmdStart."docker run -d --restart=always --name {$containerName} -v {$configPath}:/app/frps.toml {$ports_str} {$imageName}";
        $shellOutput = shell_exec($cmdStart."{$dockerCommand}");
        $logger->info("Running container {$containerName} with command {$dockerCommand}", [$shellOutput === null ? "fail" : "success", $shellOutput ?? $dockerCommand]);
        return $shellOutput === null ? $dockerCommand : $shellOutput;
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