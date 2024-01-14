<?php

namespace App\Util;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

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

    public static function replaceValueInArrayRecursively(array $array, string $find, string $replacement): array
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
}