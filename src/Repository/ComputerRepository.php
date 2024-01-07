<?php

namespace App\Repository;

use Medoo\Medoo;

class ComputerRepository
{
    public function __construct(private readonly Medoo $db)
    {
    }

    public function getComputer(string $id, array $user): ?array
    {
        return $this->db->get("computer",
            [
                "[>]device" => ["deviceId" => "id"],
            ],
            [
                "computer.id",
                "computer.name",
                "computer.deviceId",
                "computer.macAddress",
                "device.name(deviceName)",
                "device.id(deviceId)",
            ],
            [
                "computer.id" => $id,
                "computer.userId" => $user["id"],
            ]);
    }


    public function getComputers(array $user): array
    {
        return $this->db->select("computer",
            [
                "[>]device" => ["deviceId" => "id"],
            ],
            [
                "computer.id",
                "computer.name",
                "computer.deviceId",
                "computer.macAddress",
                "device.name(deviceName)",
                "device.id(deviceId)",
            ],
            [
                "computer.userId" => $user["id"],
            ]);
    }

    public function createComputer(array $body, array $user): array
    {
        $this->db->insert("computer", [
            "id" => $body["id"],
            "name" => $body['name'],
            "deviceId" => $body['deviceId'],
            "macAddress" => $body['macAddress'],
            "userId" => $user["id"]
        ]);

        return $this->getComputer($body["id"], $user);
    }

    public function updateComputer(string $id, array $body, array $user): array
    {
        $this->db->update("computer", [
            "name" => $body['name'],
            "deviceId" => $body['deviceId'],
            "macAddress" => $body['macAddress'],
        ], ["id" => $id, "userId" => $user["id"]]);

        return $this->getComputer($id, $user);
    }

    public function deleteComputer(string $id, array $user): void
    {
        $this->db->delete("computer", ["id" => $id, "userId" => $user["id"]]);
    }

    public function getComputersByDeviceId(string $deviceId, array $user): array
    {
        return $this->db->select("computer", "*", ["deviceId" => $deviceId, "userId" => $user["id"]]);
    }
}