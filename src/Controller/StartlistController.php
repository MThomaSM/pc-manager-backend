<?php

namespace App\Controller;

use App\Repository\DeviceRepository;
use App\Repository\StartlistRepository;
use App\Util\Util;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Valitron\Validator;

class StartlistController extends AbstractController
{
    public function index(Request $request, Response $response, StartlistRepository $startlistRepository,  array $user = [], string $deviceId = null): Response
    {
        $page = $request->getQueryParams()["page"] ?? 1;
        $itemsPerPage = $request->getQueryParams()["itemsPerPage"] ?? 10;
        $data = $startlistRepository->getStartlist($deviceId, $user, $page, $itemsPerPage);
        return $this->data($response, $data["data"], StatusCodeInterface::STATUS_OK, $data["meta"]);
    }

    public function maclist(Request $request, Response $response, StartlistRepository $startlistRepository, array $user = [], string $deviceId = null, string $computerId = null): Response
    {
        return $this->json($response, $startlistRepository->getMaclist($deviceId, $user));
    }

    public function deleteByComputer(Request $request, Response $response, StartlistRepository $startlistRepository, array $user = [], string $deviceId = null, string $computerId = null): Response
    {

        $v = new Validator(["computerId" => $computerId, "deviceId" => $deviceId]);
        $v->rules([
            "required" => [
                ["computerId"], ["deviceId"]
            ],
            "entityExist" => [
                ["computerId", "startlist", ["computerId" => "#VALUE#", "userId" => $user["id"]]], // making sure device with that exist, #VALUE# will be replaced with validator value
                ["deviceId", "startlist", ["deviceId" => "#VALUE#", "userId" => $user["id"]]], // making sure device with that exist, #VALUE# will be replaced with validator value
            ],
        ]);

        if (!$v->validate()) {
            return $this->error($response, Util::flattenValidationErrors($v->errors()));
        }

        $startlistRepository->deleteStartlistByComputer($deviceId, $computerId, $user);
        return $this->data($response, ["success" => true], StatusCodeInterface::STATUS_NO_CONTENT);
    }

    public function delete(Request $request, Response $response, StartlistRepository $startlistRepository, array $user = [], string $id = null){
        $v = new Validator(["id" => $id]);
        $v->rules([
            "required" => [
                ["id"]
            ],
            "entityExist" => [
                ["id", "startlist", ["id" => "#VALUE#", "userId" => $user["id"]]], // making sure device with that exist, #VALUE# will be replaced with validator value
            ],
        ]);

        if (!$v->validate()) {
            return $this->error($response, Util::flattenValidationErrors($v->errors()));
        }

        $startlistRepository->deleteStartlist($id, $user);
        return $this->data($response, ["success" => true], StatusCodeInterface::STATUS_NO_CONTENT);
    }

    public function maclistDeleteByMacAddress(Request $request, Response $response, StartlistRepository $startlistRepository, array $user = [], string $deviceId = null, string $macAddress = null): Response
    {
        $v = new Validator(["macAddress" => $macAddress, "deviceId" => $deviceId]);
        $v->rules([
            "required" => [
                ["macAddress"], ["deviceId"]
            ],
            "entityExist" => [
                ["macAddress", "startlist", ["computer.macAddress" => "#VALUE#", "startlist.userId" => $user["id"]], ["[>]computer" => ["computerId" => "id"]]], // making sure device with that exist, #VALUE# will be replaced with validator value
                ["deviceId", "startlist", ["deviceId" => "#VALUE#", "userId" => $user["id"]]], // making sure device with that exist, #VALUE# will be replaced with validator value
            ],
        ]);

        if (!$v->validate()) {
            return $this->error($response, Util::flattenValidationErrors($v->errors()));
        }

        $startlistRepository->deleteStartlistByMacAddress($deviceId, $macAddress, $user);

        return $this->data($response, ["success" => true], StatusCodeInterface::STATUS_NO_CONTENT);

    }
    public function wake(Request $request, Response $response, StartlistRepository $startlistRepository, array $user = [], string $deviceId = null, string $computerId = null): Response
    {
        $v = new Validator(["computerId" => $computerId, "deviceId" => $deviceId]);
        $v->rules([
            "required" => [
                ["computerId"], ["deviceId"]
            ],
            "entityExist" => [
                ["computerId", "computer", ["id" => "#VALUE#", "userId" => $user["id"]]], // making sure device with that exist, #VALUE# will be replaced with validator value
                ["deviceId", "device", ["id" => "#VALUE#", "userId" => $user["id"]]], // making sure device with that exist, #VALUE# will be replaced with validator value
            ],
        ]);

        if (!$v->validate()) {
            return $this->error($response, Util::flattenValidationErrors($v->errors()));
        }

        $startlist = $startlistRepository->createStartlist($deviceId, $computerId, $user);
        return $this->data($response, $startlist, StatusCodeInterface::STATUS_CREATED);
    }

    public function bulk(Request $request, Response $response,DeviceRepository $deviceRepository, StartlistRepository $startlistRepository, array $user = [], string $deviceId = null): Response
    {
        $body = $request->getParsedBody();
        $errors = [];
        $inserted = [];

        if($deviceRepository->getDevice($deviceId, $user) === null) {
            return $this->error($response, "Device not found", StatusCodeInterface::STATUS_NOT_FOUND);
        }

        foreach ($body as $item) {
            $v = new Validator($item);
            $v->rules([
                "required" => [
                    ["computerId"], ["startAt"]
                ],
                "entityExist" => [
                    ["computerId", "computer", ["id" => "#VALUE#", "userId" => $user["id"]]], // making sure device with that exist, #VALUE# will be replaced with validator value
                ],
            ]);

            if (!$v->validate()) {
                $errors[] = Util::flattenValidationErrors($v->errors());
            }

            $inserted[] = $startlistRepository->createStartlist($deviceId, $item["computerId"], $user, $item["startAt"]);
        }

        if(sizeof($errors) > 0) {
            return $this->error($response, Util::flattenValidationErrors($errors));
        }

        return $this->data($response, $inserted, StatusCodeInterface::STATUS_CREATED);
    }

}