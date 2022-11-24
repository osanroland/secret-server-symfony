<?php

namespace App\Controller;

use App\Entity\Secret;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SecretController extends AbstractController
{
    #[Route('/secret', name: 'create_secret', methods:['POST'])]
    public function creaetSecret(ManagerRegistry $doctrine ,Request $request): JsonResponse
    {

        $entityManager = $doctrine->getManager();

        $hash = bin2hex(random_bytes(20));
        $secretText = $request->request->get('secretText');
        $expireAfter = New DateTime($request->request->get('expireAfter'));
        $expireAfterViews = $request->request->get('expireAfterViews');

        $secret = new Secret();
        $secret->setHash($hash);
        $secret->setSecretText($secretText);
        $secret->setCreatedAt(New DateTime('now'));
        $secret->setExpiresAt($expireAfter);
        $secret->setRemainingViews($expireAfterViews);

        $entityManager->persist($secret);

        $entityManager->flush();

        $data = [
            'hash' => $secret->getHash(),
            'secretText' => $secret->getSecretText(),
            'createdAt' => $secret->getCreatedAt(),
            'expires_at' => $secret->getExpiresAt(),
            'remainingViews' => $secret->getRemainingViews()
        ];
        
        $response = new JsonResponse();
        $response->setData($data);
        
        return $response;
    }

    #[Route('/secret/{hash}', name: 'show_secret')]
    public function showSecret(ManagerRegistry $doctrine, string $hash): JsonResponse
    {
        $secret = $doctrine->getRepository(Secret::class)
        ->findOneBy(
            ['hash' => $hash]
        );

        if (!$secret) {
            throw $this->createNotFoundException(
                'No product found for id '.$hash
            );
        }

        $data = [
            'hash' => $secret->getHash(),
            'secretText' => $secret->getSecretText(),
            'createdAt' => $secret->getCreatedAt(),
            'expires_at' => $secret->getExpiresAt(),
            'remainingViews' => $secret->getRemainingViews()
        ];
         
        $response = new JsonResponse();
        $response->setData($data);
        
        return $response;
    }
}
