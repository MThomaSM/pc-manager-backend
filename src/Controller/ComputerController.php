<?php

namespace App\Controller;

use App\Controller\AbstractController;
use App\Repository\ComputerRepository;
use App\Util\Util;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;
use Valitron\Validator;

class ComputerController extends AbstractController
{
    public function index(Request $request, Response $response, ComputerRepository $computerRepository,  string $id = null, array $user = []): Response
    {
        if ($id) return $this->data($response, $computerRepository->getComputer($id, $user));
        return $this->data($response, $computerRepository->getComputers($user));
    }

    public function post(Request $request, Response $response, ComputerRepository $computerRepository, array $user = []): Response
    {
        $uuid = Uuid::uuid4()->toString();
        $body = [...$request->getParsedBody(), "id" => $uuid];

        $v = new Validator($body);

        $v->rules([
            "required" => [
                ["name"],["deviceId"],["macAddress"]
            ],
            "macAddress" => [
                ["macAddress"]
            ],
            "entityExist" => [
                ["deviceId", "device", ["id" => "#VALUE#", "userId" => $user["id"]]], // making sure device with that exist, #VALUE# will be replaced with validator value
            ],
            "entityDoesntExist" => [
                ["id", "computer", ["id" => "#VALUE#", "userId" => $user["id"]]] //making sure with that id doesnt exist,so it can continue it, #VALUE# will be replaced with validator value
            ]
        ]);

        if (!$v->validate()) {
            return $this->error($response, Util::flattenValidationErrors($v->errors()));
        }

        $computer = $computerRepository->createComputer($body, $user);
        return $this->data($response, $computer, StatusCodeInterface::STATUS_CREATED);
    }

    public function patch(Request $request, Response $response, ComputerRepository $computerRepository, string $id = null, array $user = []): Response
    {
        $body = $request->getParsedBody();

        $v = new Validator([...$body, "id" => $id]);
        $v->rules([
            "macAddress" => [
                ["macAddress"]
            ],
            "entityExist" => [
                ["deviceId", "device", ["id" => "#VALUE#", "userId" => $user["id"]]], // making sure device with that exist
                ["id", "computer", ["id" => "#VALUE#", "userId" => $user["id"]]] // making sure device with that exist
            ],
        ]);

        if (!$v->validate()) {
            return $this->error($response, Util::flattenValidationErrors($v->errors()));
        }

        $computer = $this->db->get("computer", "*", ["id" => $id, "userId" => $user["id"]]);
        $body = array_merge($computer, $body);

        $computer = $computerRepository->updateComputer($id, $body, $user);
        return $this->data($response, $computer);
    }
    public function delete(Request $request, Response $response, ComputerRepository $computerRepository, string $id = null, array $user = []): Response
    {
        $v = new Validator(["id" => $id]);
        $v->rules([
            "required" => [
                ["id"]
            ],
            "entityExist" => [
                ["id", "computer", ["id" => "#VALUE#", "userId" => $user["id"]]], // making sure device with that exist
            ],
        ]);

        if (!$v->validate()) {
            return $this->error($response, Util::flattenValidationErrors($v->errors()));
        }

        $computerRepository->deleteComputer($id, $user);
        return $this->data($response, ["success" => true], StatusCodeInterface::STATUS_NO_CONTENT);
    }

    public function getByDeviceId(Request $request, Response $response, ComputerRepository $computerRepository, string $deviceId = null, array $user = []): Response
    {
        $v = new Validator(["deviceId" => $deviceId]);
        $v->rules([
            "required" => [
                ["deviceId"]
            ],
        ]);

        if (!$v->validate()) {
            return $this->error($response, Util::flattenValidationErrors($v->errors()));
        }

        $computers = $computerRepository->getComputersByDeviceId($deviceId, $user);
        return $this->data($response, $computers);
    }
}