<?php

namespace App\Repository;

use Medoo\Medoo;
use Ramsey\Uuid\Uuid;

class ServerRepository
{
    public function __construct(private readonly Medoo $db)
    {
    }

    public function getServer(array $user, string $id): ?array
    {
        return $this->db->get("server", "*", ["id" => $id, "userId" => $user["id"]]);
    }

    public function getServerForUser(array $user): ?array
    {
        $existingServer = $this->db->get("server", "*", ["userId" => $user["id"]]);
        if($existingServer) return $existingServer;

        $serverIp = "127.0.0.1";
        $uuid = Uuid::uuid4()->toString();
        $this->db->insert("server",
            [
                "id" => $uuid,
                "userId" => $user["id"],
                "name" => $user["firstName"] . " ". $user["lastName"],
                "serverIp" => $serverIp,
                "serverFrpPort" => $this->generateRandomPort(),
                "password" => substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 12),
            ]
        );

        return $this->getServerForUser($user);
    }

    private function generateRandomPort(): ?int
    {
        $attempts = 0;
        $maxAttempts = 25;

        while ($attempts < $maxAttempts) {
            $randomPort = rand(7000, 9999);

            $existingPort = $this->db->get("server", "*", ["serverFrpPort" => $randomPort]);

            if (!$existingPort) {
                return $randomPort;
            }

            $attempts++;
        }
        return null;
    }
}