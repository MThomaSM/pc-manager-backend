<?php

namespace App\Repository;

use Medoo\Medoo;
use Ramsey\Uuid\Uuid;

class StartlistRepository
{
    public function __construct(private readonly Medoo $db)
    {
    }

    public function getStartlist(string $deviceId, array $user, int $page = 1, int $itemsPerPage = 10): array
    {
        $offset = ($page - 1) * $itemsPerPage;

       $totalRecords = $this->db->count(
            "startlist",
            [
                "startlist.deviceId" => $deviceId,
                "startlist.userId" => $user["id"]
            ]
        );

        $maxPages = ceil($totalRecords / $itemsPerPage);

        $data = $this->db->select(
            "startlist",
            [
                "[>]device" => ["deviceId" => "id"],
                "[>]computer" => ["computerId" => "id"]
            ],
            [
                "startlist.id",
                "startlist.startAt",
                "startlist.executedAt",
                "computer.id(computerId)",
                "computer.name(computerName)",
                "computer.macAddress",
                "device.name(deviceName)",
                "device.id(deviceId)",
                "device.updatedAt"
            ],
            [
                "startlist.deviceId" => $deviceId,
                "startlist.userId" => $user["id"],
                "ORDER" => [
                    "startlist.executedAt" => "DESC",
                    "startlist.startAt" => "DESC",
                    "startlist.createdAt" => "DESC"
                ],
                "LIMIT" => [$offset, $itemsPerPage]
            ]
        );

        return ["data" => $data, "meta" => ["total" => $totalRecords, "page" => $page, "maxPages" => $maxPages, "perPage" => $itemsPerPage]];
    }

    public function getMaclist(string $deviceId, array $user): array
    {
        return $this->db->select(
            "startlist", // Hlavná tabuľka
            [
                "[>]computer" => ["computerId" => "id"] // LEFT JOIN s tabuľkou computer
            ],
            "@computer.macAddress", //@ - DISTINCT
            [
                "startlist.deviceId" => $deviceId, // Kde klauzula pre ID zariadenia
                "startlist.userId" => $user["id"], // Kde klauzula pre ID používateľa
                "startlist.startAt[<=]" => date('Y-m-d H:i:s'),
                "startlist.executedAt" => null,
                "LIMIT" => 100
            ]
        );
    }

    public function deleteStartlistByComputer(string $deviceId, string $computerId, array $user): void
    {
        $this->db->delete("startlist", [
            "deviceId" => $deviceId,
            "computerId" => $computerId,
            "userId" => $user["id"]
        ]);
    }

    public function deleteStartlist(string $id, array $user): void
    {
        $this->db->delete("startlist", [
            "id" => $id,
            "userId" => $user["id"]
        ]);
    }

    public function deleteStartlistByMacAddress(string $deviceId, string $macAddress, array $user): void
    {
        $now = date('Y-m-d H:i:s'); // Aktuálny čas vo formáte Y-m-d H:i:s

        $startlistIds = $this->db->select(
            "startlist",
            [
                "[>]computer" => ["computerId" => "id"],
                "[>]device" => ["deviceId" => "id"]
            ],
            "startlist.id",
            [
                "computer.macAddress" => $macAddress,
                "device.id" => $deviceId,
                "startlist.executedAt" => null,
                "startlist.startAt[<=]" => $now
            ]
        );

        if (!empty($startlistIds)) {
            $this->db->update(
                "startlist",
                ["executedAt" => $now],
                ["id" => $startlistIds] // Použite zoznam ID pre UPDATE
            );
        }
    }

    public function createStartlist(string $deviceId, string $computerId, array $user, $startAt = null): array
    {
        $uuid = Uuid::uuid4()->toString();
        $this->db->insert("startlist", [
            "id" => $uuid,
            "deviceId" => $deviceId,
            "computerId" => $computerId,
            "userId" => $user["id"],
            "startAt" => $startAt ?? date('Y-m-d H:i:s')
        ]);

        return $this->db->get("startlist", "*", ["id" => $uuid]);
    }
}