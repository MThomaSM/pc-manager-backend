<?php

namespace App\Repository;

use Medoo\Medoo;
use Ramsey\Uuid\Uuid;

class ConnectionRepository
{
    public function __construct(
        private readonly Medoo $db,
        private readonly ServerRepository $serverRepository
    )
    {
    }

    public function getConnections(string $userId, string $computerId)
    {
        return $this->db->select("connection", [
            "[>]computer" => ["computerId" => "id"],
            "[>]server" => ["serverId" => "id"],
        ],
        [
            "connection.id",
            "connection.type",
            "connection.name",
            "server.serverIp",
            "server.name(serverName)",
            "server.serverFrpPort",
            "connection.remotePort",
            "connection.localPort",
            "connection.localIp",
            "computer.id(computerId)",
            "connection.ipWhitelist",
//            "computer.name(computerName)",
//            "computer.macAddress",
        ],
        [
            "connection.userId" => $userId,
            "connection.computerId" => $computerId,
        ]);
    }

    public function getConnectionsByServer(string $userId, string $computerId, string $serverId)
    {
        return $this->db->select("connection", [
            "[>]computer" => ["computerId" => "id"],
            "[>]server" => ["serverId" => "id"],
        ],
            [
                "connection.id",
                "connection.type",
                "connection.name",
                "server.serverIp",
                "server.name(serverName)",
                "server.serverFrpPort",
                "connection.remotePort",
                "connection.localPort",
                "connection.localIp",
                "computer.id(computerId)",
                "connection.ipWhitelist",
//            "computer.name(computerName)",
//            "computer.macAddress",
            ],
            [
                "connection.userId" => $userId,
                "connection.computerId" => $computerId,
                "connection.serverId" => $serverId,
            ]);
    }

    public function getConnectionByComputerAndType(string $userId, string $computerId, string $type = "vnc")
    {
        return $this->db->get("connection", [
                "[>]computer" => ["computerId" => "id"],
                "[>]server" => ["serverId" => "id"],
            ],
            [
                "connection.id",
                "connection.type",
                "connection.name",
                "server.serverIp",
                "server.name(serverName)",
                "server.serverFrpPort",
                "connection.remotePort",
                "connection.localPort",
                "connection.localIp",
                "computer.id(computerId)",
                "connection.ipWhitelist",
//                "computer.name(computerName)",
//                "computer.macAddress",
            ],
            [
                "connection.userId" => $userId,
                "connection.computerId" => $computerId,
                "connection.type" => $type,
            ]
        );
    }

    public function getConnection(string $userId, string $id)
    {
        return $this->db->get("connection", [
            "[>]computer" => ["computerId" => "id"],
            "[>]server" => ["serverId" => "id"],
        ],
            [
                "connection.id",
                "connection.type",
                "connection.name",
                "server.serverIp",
                "server.name(serverName)",
                "server.serverFrpPort",
                "connection.remotePort",
                "connection.localPort",
                "connection.localIp",
                "computer.id(computerId)",
                "computer.name(computerName)",
                "computer.macAddress",
                "connection.ipWhitelist",
            ],
            [
                "connection.userId" => $userId,
                "connection.id" => $id,
            ]
        );
    }

    public function createConnection(array $user, string $computerId, array $body)
    {
        $inserted = $this->db->insert("connection", [
            "id" => Uuid::uuid4()->toString(),
            "computerId" => $computerId,
            "userId" => $user["id"],
            "type" => $body["type"],
            "serverId" => $this->serverRepository->getServerForUser($user)["id"],
            "remotePort" => $body["remotePort"],
            "localPort" => $body["localPort"],
            "localIp" => $body["localIp"],
            "name" => $body["name"],
            "ipWhitelist" => $body["ipWhitelist"] ?? null,
        ]);

        return $this->getConnectionByComputerAndType($user["id"], $computerId, $body["type"]);
    }

    public function updateConnection(array $user, string $id, string $computerId, array $body)
    {
        $updated = $this->db->update("connection", [
            "remotePort" => $body["remotePort"],
            "localPort" => $body["localPort"],
            "localIp" => $body["localIp"],
            "name" => $body["name"],
            "type" => $body["type"],
            "ipWhitelist" => $body["ipWhitelist"] ?? null,
        ], [
            "id" => $id,
            "userId" => $user["id"],
            "computerId" => $computerId,
        ]);

        return $this->getConnection($user["id"], $id);
    }

    public function deleteConnection(array $user, string $id, string $computerId)
    {
        $this->db->delete("connection", [
            "id" => $id,
            "userId" => $user["id"],
            "computerId" => $computerId,
        ]);
    }

}