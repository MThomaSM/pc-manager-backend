<?php

namespace App\Controller;

use App\Controller\AbstractController;
use App\Repository\DeviceRepository;
use App\Util\Util;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;
use Valitron\Validator;

class DeviceController extends AbstractController
{
    public function index(Request $request, Response $response, DeviceRepository $deviceRepository, string $id = null, array $user = []): Response
    {
        if ($id) return $this->data($response, $deviceRepository->getDevice($id, $user));
        return $this->data($response, $deviceRepository->getDevices($user));
    }

    public function post(Request $request, Response $response, DeviceRepository $deviceRepository, array $user = []): Response
    {
        $body = $request->getParsedBody();

        $v = new Validator($body);
        $v->rules([
            "required" => [
                ["name"], ["id"]
            ],
            "entityDoesntExist" => [
                ["id", "device", ["id" => "#VALUE#"]] //making sure with that id doesnt exist,so it can continue it, #VALUE# will be replaced with validator value
            ],
            "uuidv4" => [
                ["id"]
            ]
        ]);

        if (!$v->validate()) {
            return $this->error($response, Util::flattenValidationErrors($v->errors()));
        }

        $createdDevice = $deviceRepository->createDevice($body, $user);

        return $this->data($response, $createdDevice, StatusCodeInterface::STATUS_CREATED);
    }

    public function patch(Request $request, Response $response, DeviceRepository $deviceRepository, string $id = null, array $user = []): Response
    {
        $body = $request->getParsedBody();

        $v = new Validator([...$body, "id" => $id]);
        $v->rules([
            "required" => [
                ["name"]
            ],
            "entityExist" => [
                ["id", "device", ["id" => "#VALUE#", "userId" => $user["id"]]], // making sure device with that exist, #VALUE# will be replaced with validator value
            ],
        ]);

        if (!$v->validate()) {
            return $this->error($response, Util::flattenValidationErrors($v->errors()));
        }

        $device = $this->db->get("device", "*", ["id" => $id, "userId" => $user["id"]]);

        $body = array_merge($device, $body);

        $updatedDevice = $deviceRepository->updateDevice($id, $body, $user);

        return $this->data($response, $updatedDevice);
    }

    public function delete(Request $request, Response $response, DeviceRepository $deviceRepository, string $id = null, array $user = []): Response
    {
        $v = new Validator(["id" => $id]);
        $v->rules([
            "required" => [
                ["id"]
            ],
            "entityExist" => [
                ["id", "device", ["id" => "#VALUE#", "userId" => $user["id"]]], // making sure device with that exist
            ],
        ]);

        if (!$v->validate()) {
            return $this->error($response, Util::flattenValidationErrors($v->errors()));
        }

        $deviceRepository->deleteDevice($id, $user);
        return $this->data($response, ["success" => true], StatusCodeInterface::STATUS_NO_CONTENT);
    }

}