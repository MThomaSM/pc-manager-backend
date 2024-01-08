<?php

namespace App\Controller;

use App\Controller\AbstractController;
use App\Repository\UserRepository;
use App\Util\Util;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Firebase\JWT\JWT;
use Monolog\Logger;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;
use Valitron\Validator;
use function DI\string;

class UserController extends AbstractController
{
    /**
     * @throws Exception
     */
    public function post(Request $request, Response $response, UserRepository $userRepository): Response
    {
        $body = $request->getParsedBody();

        $v = new Validator($body);
        $v->rules([
            "required" => [
                ["email"], ["firstName"], ["lastName"], ["password"], ["passwordConfirm"]
            ],
            "equals" => [
                ["password", "passwordConfirm"]
            ],
            "entityDoesntExist" => [
                ["email", "user", ["email" => "#VALUE#"]], //making sure with that id doesnt exist,so it can continue it, #VALUE# will be replaced with validator value
            ],
        ]);

        $v->labels([
            "passwordConfirm" => "Confirmation password"
        ]);

        $v->message("Account with that {field} already exists");

        if (!$v->validate()) {
            return $this->error($response, Util::flattenValidationErrors($v->errors()));
        }

        $user = $userRepository->createUser($body);

        return $this->data($response, [...$user, "jwt" => $this->generateJWT($user["id"])]);
    }

    public function auth(Request $request, Response $response, UserRepository $userRepository): Response
    {
        $body = $request->getParsedBody();
        $v = new Validator($body);
        $v->rules([
            "required" => [
                ["email"], ["password"]
            ],
        ]);

        if (!$v->validate()) {
            return $this->error($response, Util::flattenValidationErrors($v->errors()));
        }

        $user = $userRepository->getUserByEmail($body["email"]);

        if(!$user || !password_verify($body["password"], $user["password"])){
            return $this->error($response, "Bad credentials");
        }

        $userRepository->cleanUser($user);

        return $this->data($response, [...$user, "jwt" => $this->generateJWT($user["id"])]);
    }

    /**
     * @throws Exception
     */
    public function patch(Request $request, Response $response, UserRepository $userRepository, array $user = []): Response
    {
        $body = $request->getParsedBody();
        $updatePassword = false;

        if(isset($body["password"])){
            $v = new Validator($body);
            $v->rules([
                "required" => [
                    ["password"], ["passwordConfirm"]
                ],
                "equals" => [
                    ["password", "passwordConfirm"]
                ],
                "lengthMin" => [
                    ["username", 4]
                ]
            ]);

            $v->labels([
                "passwordConfirm" => "Confirmation password"
            ]);

            if (!$v->validate()) {
                return $this->error($response, Util::flattenValidationErrors($v->errors()));
            }

            $updatePassword = true;
        }

        $body = array_merge($user, $body);
        $user = $userRepository->updateUser($body, $updatePassword);

        return $this->data($response, [...$user, "jwt" => $this->generateJWT($user["id"])]);
    }

    public function getUserByPasswordResetToken(Request $request, Response $response, UserRepository $userRepository, string $token): Response
    {
        $user = $userRepository->getUserByResetPasswordToken($token);

        if(!$user){
            return $this->error($response, "Invalid token", StatusCodeInterface::STATUS_NOT_FOUND);
        }

        $userRepository->cleanUser($user);

        return $this->data($response, $user);
    }

    public function updateUserPassword(Request $request, Response $response, UserRepository $userRepository, string $token): Response
    {
        $body = $request->getParsedBody();

        $v = new Validator($body);
        $v->rules([
            "required" => [
                ["password"], ["passwordConfirm"]
            ],
            "equals" => [
                ["password", "passwordConfirm"]
            ],
        ]);

        $v->labels([
            "passwordConfirm" => "Confirmation password"
        ]);

        if (!$v->validate()) {
            return $this->error($response, Util::flattenValidationErrors($v->errors()));
        }

        $user = $userRepository->getUserByResetPasswordToken($token);

        if(!$user){
            return $this->error($response, "Invalid token", StatusCodeInterface::STATUS_NOT_FOUND);
        }

        $userRepository->updateUserPassword($user["email"], $body["password"]);

        return $this->data($response, "Password updated");
    }

    /**
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function sendResetPasswordEmail(Request $request, Response $response, UserRepository $userRepository, PHPMailer $mailer): Response
    {
        $body = $request->getParsedBody();

        $v = new Validator($body);
        $v->rules([
            "required" => [
                ["email"]
            ],
            "email" => [
                ["email"]
            ],
            "entityExist" => [
                ["email", "user", ["email" => "#VALUE#"]], //making sure with that id doesnt exist,so it can continue it, #VALUE# will be replaced with validator value
            ],
        ]);

        $v->message("Account with that {field} doesnt exists");

        if (!$v->validate()) {
            return $this->error($response, Util::flattenValidationErrors($v->errors()));
        }

        $user = $userRepository->getUserByEmail($body["email"]);

        $token = Uuid::uuid4()->toString();

        $subject = "Reset your password";
        $body = "Click on the link to reset your password: " . $this->settings["app"]["url"] . "/system/changepassword/" . $token;

        $sended = Util::sendEmail($mailer, $user["email"], $subject, $body);
        if (!$sended) {
            return $this->error($response, "Something went wrong");
        }

        $userRepository->updateUserPasswordToken($user["email"], $token);
        return $this->data($response, $sended);
    }

    private function generateJWT(string $userId): array
    {
        $now = time();
        $jwtPayload = [
            'id' => $userId,
            "iat" => $now,
            "nbf" => $now,
            'exp' => $now + intval($this->settings["jwt"]["expiration"])
        ];

        return ["expiresAt" => $jwtPayload["exp"], "token" => JWT::encode($jwtPayload, $this->settings["jwt"]["secret"], $this->settings["jwt"]["algorithm"])];
    }

}