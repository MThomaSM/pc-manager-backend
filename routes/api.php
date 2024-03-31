<?php

use App\Controller\ComputerController;
use App\Controller\ConnectionController;
use App\Controller\DeviceController;
use App\Controller\StartlistController;
use App\Controller\UserController;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->group('/api', function (RouteCollectorProxy $group) {
        $group->get("/getIp", [UserController::class, "getIp"]);
        $group->post("/signup", [UserController::class, "post"]);
        $group->post("/auth", [UserController::class, "auth"]);
        $group->patch("/users", [UserController::class, "patch"]);
        $group->post("/users/password-request", [UserController::class, "sendResetPasswordEmail"]);
        $group->get("/users/password-reset/{token}", [UserController::class, "getUserByPasswordResetToken"]);
        $group->patch("/users/password-reset/{token}", [UserController::class, "updateUserPassword"]);

        $group->get("/devices[/{id}]", [DeviceController::class, "index"]);
        $group->post("/devices", [DeviceController::class, "post"]);
        $group->patch("/devices/{id}", [DeviceController::class, "patch"]);
        $group->delete("/devices/{id}", [DeviceController::class, "delete"]);

        $group->get("/computers[/{id}]", [ComputerController::class, "index"]);
        $group->get("/devices/{deviceId}/computers", [ComputerController::class, "getByDeviceId"]);
        $group->get("/computers/{id}/download", [ConnectionController::class, "generateClientDownload"]);
        $group->post("/computers", [ComputerController::class, "post"]);
        $group->patch("/computers/{id}", [ComputerController::class, "patch"]);
        $group->delete("/computers/{id}", [ComputerController::class, "delete"]);

        $group->get("/startlist/{deviceId}", [StartlistController::class, "index"]);
        $group->get("/startlist/{deviceId}/maclist", [StartlistController::class, "maclist"]);
        $group->post("/startlist/{deviceId}/wake/{computerId}", [StartlistController::class, "wake"]);
        $group->post("/startlist/{deviceId}/bulk", [StartlistController::class, "bulk"]);
        $group->delete("/startlist/{id}", [StartlistController::class, "delete"]);
        $group->delete("/startlist/{deviceId}/{computerId}", [StartlistController::class, "deleteByComputer"]);
        $group->delete("/startlist/{deviceId}/mac/{macAddress}", [StartlistController::class, "maclistDeleteByMacAddress"]);
        $group->get("/startlist/{deviceId}/mac/{macAddress}/remove", [StartlistController::class, "maclistDeleteByMacAddress"]);

        $group->get("/computers/{computerId}/connections", [ConnectionController::class, "index"]);
        $group->get("/connections/{id}", [ConnectionController::class, "connectionById"]);
        $group->get("/connections/{id}/ip-whitelist", [ConnectionController::class, "getWhitelistedIps"]);
        $group->post("/connections/{id}/ip-whitelist", [ConnectionController::class, "setWhitelistedIps"]);
        $group->post("/connections/{computerId}", [ConnectionController::class, "createConnection"]);
        $group->patch("/connections/{computerId}/{id}", [ConnectionController::class, "updateConnection"]);
        $group->delete("/connections/{computerId}/{id}", [ConnectionController::class, "deleteConnection"]);

    });
};