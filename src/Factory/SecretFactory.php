<?php
namespace App\Factory;

use App\Entity\Secret;
use DateTime;

class SecretFactory
{
    public static function create(string $secretText, string $expireAfter, int $expireAfterViews): Secret
    {
        $hash = bin2hex(random_bytes(20));

        $secretObject = new Secret();
        $secretObject->setHash($hash);
        $secretObject->setSecretText($secretText);
        $secretObject->setCreatedAt(New DateTime());
        $secretObject->setExpiresAt(New DateTime($expireAfter));
        $secretObject->setRemainingViews($expireAfterViews);

        return $secretObject;
    }
}