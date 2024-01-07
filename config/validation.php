<?php


declare(strict_types=1);

use App\Util\Util;
use DI\NotFoundException;
use Medoo\Medoo;
use Ramsey\Uuid\Uuid;
use Slim\App;
use Valitron\Validator;

return function (App $app){
    if (!($container = $app->getContainer())) {
        throw new NotFoundException('Could not get the container.');
    }

    Validator::addRule("entityExist", function($field, $value, array $params) use ($container) { //check if entity exist in db
        $db = $container->get(Medoo::class);
        [$table, $condition] = $params;
        $condition = Util::replaceValueInArrayRecursively($condition, "#VALUE#", $value);
        return isset($params[2]) ? $db->has($table, $params[2], $condition) : $db->has($table, $condition);
    }, 'doesnt exist');

    Validator::addRule("entityDoesntExist", function($field, $value, array $params) use ($container) { //check if entity exist in db
        $db = $container->get(Medoo::class);
        [$table, $condition] = $params;
        $condition = Util::replaceValueInArrayRecursively($condition, "#VALUE#", $value);
        return isset($params[2]) ? !$db->has($table, $params[2], $condition) : !$db->has($table, $condition);
    }, 'already exist');

    Validator::addRule("macAddress", function($field, $value, array $params) {
        return $value  && preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $value);
    }, 'is not a valid MAC address');

    Validator::addRule("uuidv4", function($field, $value, array $params) {
        return $value && Uuid::isValid($value) && Uuid::fromString($value)->getVersion() === 4;
    }, 'is not a valid UUID');
};