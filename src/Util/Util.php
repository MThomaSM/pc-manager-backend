<?php

namespace App\Util;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class Util
{
    public static function flattenValidationErrors(array $errors): array
    {
        $flattenedErrors = [];
        foreach ($errors as $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $flattenedErrors[] = $error;
            }
        }
        return $flattenedErrors;
    }

    public static function replaceValueInArrayRecursively(array $array, mixed $find, mixed $replacement): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::replaceValueInArrayRecursively($value, $find, $replacement);
            } elseif ($value === $find) {
                $array[$key] = $replacement;
            }
        }
        return $array;
    }

    /**
     * @throws Exception
     */
    public static function sendEmail(PHPMailer $mailer, string $to, string $subject, string $body): bool
    {
        try {
            $mailer->addAddress($to);
            $mailer->Subject = $subject;
            $mailer->Body    = $body;

            $mailer->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @throws TransportExceptionInterface
     */
    public static function verifyRecaptcha(string $recaptchaSecret, string $recaptchaResponse): bool
    {
        $httpClient = HttpClient::create();
        $response = $httpClient->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [ //v3
            'body' => [
                'secret' => $recaptchaSecret,
                'response' => $recaptchaResponse
            ],
            "verify_peer" => __DIR__."/data/cacert.pem",
        ]);
        $body = $response->toArray();
        return $body['success'];
    }
}