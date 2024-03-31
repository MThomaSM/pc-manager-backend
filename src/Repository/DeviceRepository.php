<?php

namespace App\Repository;

use Medoo\Medoo;

class DeviceRepository
{
    public function __construct(private readonly Medoo $db)
    {
    }

    public function getDevice(string $id, array $user): ?array
    {
        return $this->db->get("device", "*", ["id" => $id, "userId" => $user["id"]]);
    }

    public function getDevices(array $user): array
    {
        return $this->db->select("device", "*", ["userId" => $user["id"]]);
    }
    public function createDevice(array $body, array $user): array
    {
        $this->db->insert("device", [
            "id" => $body["id"],
            "name" => $body['name'],
            "userId" => $user["id"]
        ]);

        return $this->getDevice($body["id"], $user);
    }

    public function updateDevice(string $id, array $body, array $user): array
    {
        $this->db->update("device", [
            "name" => $body['name'],
        ], ["id" => $id, "userId" => $user["id"]]);

        return $this->getDevice($id, $user);
    }

    public function updateLastActiveAt(string $id): void
    {
        $this->db->update("device", ["lastActiveAt" => date("Y-m-d H:i:s")], ["id" => $id]);
    }

    public function deleteDevice(string $id, array $user): void
    {
        $this->db->delete("device", ["id" => $id, "userId" => $user["id"]]);
    }

}