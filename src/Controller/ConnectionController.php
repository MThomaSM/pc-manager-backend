<?php

namespace App\Controller;

use App\Repository\ConnectionRepository;
use App\Repository\ServerRepository;
use App\Util\ServerUtils;
use App\Util\Util;
use Cocur\Slugify\Slugify;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Valitron\Validator;

class ConnectionController extends AbstractController
{
    public function index(Request $request, Response $response, ConnectionRepository $connectionRepository, string $computerId, array $user = []): Response
    {
        return $this->data($response, $connectionRepository->getConnections($user["id"], $computerId));
    }

    public function connectionById(Request $request, Response $response, ConnectionRepository $connectionRepository, string $id, array $user = []): Response
    {
        return $this->data($response, $connectionRepository->getConnection($user["id"], $id));
    }

    public function createConnection(Request $request, Response $response, ConnectionRepository $connectionRepository, ServerRepository $serverRepository, string $computerId, array $user = []): Response
    {
        $body = $request->getParsedBody();

        $v = new Validator(["computerId" => $computerId, ...$body]);
        $v->rules([
            "required" => [
                ["type"], ["computerId"], ["remotePort"], ["localPort"], ["localIp"], ["name"]
            ],
            "in" => [
                ["type", ["UDP", "TCP"]]
            ],
            "entityExist" => [
                ["computerId", "computer", ["id" => "#VALUE#", "userId" => $user["id"]]],
            ],
            "entityDoesntExist" => [
                ["remotePort", "connection", ["remotePort" => "#VALUE#", "userId" => $user["id"], "computerId" => $computerId]],
                ["localPort", "connection", ["localPort" => "#VALUE#", "userId" => $user["id"], "computerId" => $computerId]],
            ],
            "numeric" => [
                ["remotePort"], ["localPort"]
            ],
            "min" => [
                ["remotePort", 5000],
                ["localPort", 20]
            ],
            "max" => [
                ["remotePort", 65535],
                ["localPort", 65535]
            ],
            "optional" => [
                ["ipWhitelist"]
            ],
            "array" => [
                ["ipWhitelist"]
            ],
        ]);

        if (isset($body['ipWhitelist']) && is_array($body['ipWhitelist'])) {
            foreach ($body['ipWhitelist'] as $key => $ip) {
                $v->rule('ip', "ipWhitelist.{$key}");
            }
        }

        if (!$v->validate()) {
            return $this->error($response, Util::flattenValidationErrors($v->errors()));
        }

        if (isset($body['ipWhitelist']) && is_array($body['ipWhitelist'])) {
            $body['ipWhitelist'] = sizeof($body["ipWhitelist"]) > 0 ? implode(",", $body['ipWhitelist']) : null;
        }

        $connection = $connectionRepository->createConnection($user, $computerId, $body);
        ServerUtils::runServer($computerId, $user, $serverRepository, $connectionRepository, $this->logger);

        return $this->data($response, $connection);
    }

    public function updateConnection(Request $request, Response $response, ConnectionRepository $connectionRepository, ServerRepository $serverRepository, string $computerId, string $id, array $user = []): Response
    {
        $body = $request->getParsedBody();
        $connection = $connectionRepository->getConnection($user["id"], $id);

        $entityDoesntExistValidation = [];
        if($connection["remotePort"] !== $body["remotePort"]){
            $entityDoesntExistValidation[] = ["remotePort", "connection", ["remotePort" => "#VALUE#", "userId" => $user["id"], "computerId" => $computerId]];
        }

        if($connection["localPort"] !== $body["localPort"]){
            $entityDoesntExistValidation[] = ["localPort", "connection", ["localPort" => "#VALUE#", "userId" => $user["id"], "computerId" => $computerId]];
        }

        $v = new Validator(["computerId" => $computerId, "id" => $id, ...$body]);
        $v->rules([
            "required" => [
                ["type"], ["computerId"], ["remotePort"], ["localPort"], ["localIp"], ["name"]
            ],
            "in" => [
                ["type", ["UDP", "TCP"]]
            ],
            "entityExist" => [
                ["computerId", "computer", ["id" => "#VALUE#", "userId" => $user["id"]]],
                ["id", "connection", ["id" => "#VALUE#", "userId" => $user["id"]]],
            ],
            "entityDoesntExist" => [
                ...$entityDoesntExistValidation,
            ],
            "numeric" => [
                ["remotePort"], ["localPort"]
            ],
            "min" => [
                ["remotePort", 5000],
                ["localPort", 20]
            ],
            "max" => [
                ["remotePort", 65535],
                ["localPort", 65535]
            ],
            "optional" => [
                ["ipWhitelist"]
            ],
            "array" => [
                ["ipWhitelist"]
            ],
        ]);

        if (isset($body['ipWhitelist']) && is_array($body['ipWhitelist'])) {
            foreach ($body['ipWhitelist'] as $key => $ip) {
                $v->rule('ip', "ipWhitelist.{$key}");
            }
        }

        if (!$v->validate()) {
            return $this->error($response, Util::flattenValidationErrors($v->errors()));
        }

        if (isset($body['ipWhitelist']) && is_array($body['ipWhitelist'])) {
            $body['ipWhitelist'] = sizeof($body["ipWhitelist"]) > 0 ? implode(",", $body['ipWhitelist']) : null;
        }

        $connection = $connectionRepository->updateConnection($user, $id, $computerId, $body);
        ServerUtils::runServer($computerId, $user, $serverRepository, $connectionRepository, $this->logger);
        return $this->data($response, $connection);
    }

    public function deleteConnection(Request $request, Response $response, ConnectionRepository $connectionRepository, ServerRepository $serverRepository, string $computerId, string $id, array $user = []): Response
    {
        $v = new Validator(["computerId" => $computerId, "id" => $id]);
        $v->rules([
            "required" => [
                ["computerId"], ["id"]
            ],
            "entityExist" => [
                ["computerId", "computer", ["id" => "#VALUE#", "userId" => $user["id"]]],
                ["id", "connection", ["id" => "#VALUE#", "userId" => $user["id"]]],
            ],
        ]);

        if (!$v->validate()) {
            return $this->error($response, Util::flattenValidationErrors($v->errors()));
        }

        ServerUtils::runServer($computerId, $user, $serverRepository, $connectionRepository, $this->logger, true);

        $connectionRepository->deleteConnection($user, $id, $computerId);
        return $this->data($response, ["success" => true], StatusCodeInterface::STATUS_NO_CONTENT);
    }

    public function generateClientDownload(Request $request, Response $response, ConnectionRepository $connectionRepository, ServerRepository $serverRepository, string $id, array $user = []): Response
    {
        $queryParams = $request->getQueryParams();

        $v = new Validator(["id" => $id, ...$queryParams]);
        $v->rules([
            "required" => [
                ["id"], ["type"]
            ],
            "entityExist" => [
                ["id", "computer", ["id" => "#VALUE#", "userId" => $user["id"]]],
            ],
            "in" => [
                ["type", ["config", "client"]]
            ],
        ]);

        if (!$v->validate()) {
            return $this->error($response, Util::flattenValidationErrors($v->errors()));
        }

        $computerId = $id;
        $server = $serverRepository->getServerForUser($user);
        $connections = $connectionRepository->getConnectionsByServer($user["id"], $computerId, $server["id"]);

        $slugify = new Slugify();
        $config_name = "config.toml";

        $downloadFile = "";
        $config_str = $this->generateClientTomlConfig($user, $server, $connections);
        if($queryParams["type"] === "config"){
            $downloadFile = $slugify->slugify($server["name"]).".toml";
            file_put_contents($downloadFile, $config_str);
        } else {
            $zip = new \ZipArchive();
            $downloadFile = "client.zip";
            if($zip->open($downloadFile, \ZipArchive::CREATE) !== true){
                return $this->error($response, "Failed to create zip file");
            }

            $zip->addFromString($config_name, $config_str);

            $dataDir = dirname(__DIR__)."/../data";
            $zip->addFile($dataDir."/client.exe", "data/client.exe");
            $zip->addFile($dataDir."/ManualStart.bat", "data/ManualStart.bat");
            $zip->addFile($dataDir."/PortForwarder.exe", "PortForwarder.exe");
            $zip->close();
        }

        $file = file_get_contents($downloadFile);

        $response->getBody()->write($file);


        $newResponse = $response
            ->withHeader('Content-Type', ($queryParams["type"] === "config" ? "application/octet-stream" : "application/zip"))
            ->withHeader('Content-Disposition', 'attachment; filename="'.$downloadFile.'"')
            ->withHeader('Content-Length', filesize($downloadFile));

        unlink($downloadFile);


        return $newResponse;
    }

    private function generateClientTomlConfig(array $user, array $server, array $connections): string
    {
        $config_str = "serverAddr = \"{$server["serverIp"]}\"\n";
        $config_str .= "serverPort = {$server["serverFrpPort"]}\n";
        $config_str .= 'auth.method = "token"'.PHP_EOL;
        $config_str .= "auth.token = \"{$server["password"]}\"\n";
        $config_str .= "user = \"{$user["firstName"]} {$user["lastName"]}\"\n";
        $config_str .= "\n";

        foreach ($connections as $connection){
            $connection["type"] = mb_strtolower($connection["type"]);
            $config_str .= "[[proxies]]\n";
            $config_str .= "name = \"{$connection["name"]}\"\n";
            $config_str .= "type = \"{$connection["type"]}\"\n";
            $config_str .= "localIP = \"{$connection["localIp"]}\"\n";
            $config_str .= "localPort = {$connection["localPort"]}\n"; //on server local port is remote port
            $config_str .= "remotePort = {$connection["remotePort"]}\n"; //on server local port is remote port
            $config_str .= "\n";
        }

        return $config_str;
    }
}