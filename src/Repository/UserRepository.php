<?php

namespace App\Repository;

use Exception;
use Medoo\Medoo;
use Ramsey\Uuid\Uuid;

class UserRepository
{
    public function __construct(private readonly Medoo $db)
    {
    }

    public function getUser(string $id): ?array
    {
        return $this->db->get("user", "*", ["id" => $id]);
    }

    public function getUserByEmail(string $email): ?array
    {
        return $this->db->get("user", "*", ["email" => $email]);
    }

    /**
     * @throws Exception
     */
    public function createUser(array $body): ?array
    {
        $uuid = Uuid::uuid4()->toString();

        $this->db->insert("user", [
            "id" => $uuid,
            "firstName" => $body["firstName"],
            "lastName" => $body["lastName"],
            "email" => $body["email"],
            "password" => password_hash($body["password"], PASSWORD_BCRYPT),
        ]);

        $inserted = $this->getUser($uuid);
        if (!$inserted) {
            throw new Exception("Something went wrong");
        }

        $this->cleanUser($inserted);

        return $inserted;
    }

    public function updateUserPasswordToken($email, $token): ?array
    {
        $this->db->update("user", [
            "passwordResetToken" => $token,
        ], ["email" => $email]);

        $updated = $this->getUserByEmail($email);

        $this->cleanUser($updated);

        return $updated;
    }

    public function updateUserPassword($email, $password): ?array
    {
        $this->db->update("user", [
            "password" => password_hash($password, PASSWORD_BCRYPT),
            "passwordResetToken" => null,
            "passwordChangedAt" => date("Y-m-d H:i:s"),
        ], ["email" => $email]);

        $updated = $this->getUserByEmail($email);

        $this->cleanUser($updated);

        return $updated;
    }

    public function getUserByResetPasswordToken($token): ?array
    {
        return $this->db->get("user", "*", ["passwordResetToken" => $token]);
    }

    public function updateUser(array $user, $updatePassword = false): array
    {
        $this->db->update("user", [
            "firstName" => $user["firstName"],
            "lastName" => $user["lastName"],
            "email" => $user["email"],
            "password" => $updatePassword ? password_hash($user["password"], PASSWORD_BCRYPT) : $user["password"],
            "passwordChangedAt" => $updatePassword ? date("Y-m-d H:i:s") : null,
        ], ["id" => $user["id"]]);

        $updated = $this->getUser($user["id"]);
        if (!$updated) {
            throw new Exception("Something went wrong");
        }

        $this->cleanUser($updated);

        return $updated;
    }

    public function cleanUser(array &$user): void
    {
        unset($user["password"]);
        unset($user["passwordResetToken"]);
        unset($user["role"]);
    }
}