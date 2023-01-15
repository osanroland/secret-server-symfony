<?php

namespace App\Controller;

use App\Entity\Secret;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Factory\SecretFactory;
use App\Repository\SecretRepository;
use Symfony\Component\HttpFoundation\Response;
use SimpleXMLElement;

class SecretController extends AbstractController
{

    private $secretRepository;

    public function __construct(SecretRepository $secretRepository)
    {
        $this->secretRepository = $secretRepository;
    }
    #[Route('/secret', name: 'create_secret', methods:['POST'])]
    public function creaetSecret(Request $request): Response
    {

        $acceptType = $request->headers->get('Accept');
        $contentType = $request->getContentType();

        switch ($contentType){
            case 'form':
                if (empty($request->request->get('secretText')) || empty($request->request->get('expireAfter')) || empty($request->request->get('expireAfterViews'))) {
                    throw $this->createNotFoundException('Please set all fields!');
                }

                $secretText = $request->request->get('secretText');
                $expireAfter = $request->request->get('expireAfter');
                $expireAfterViews = $request->request->get('expireAfterViews');
            break;
            case 'json':
                $data = json_decode($request->getContent(), true);

                if (empty($data['secretText']) || empty($data['expireAfter']) || empty($data['expireAfterViews'])) {
                    throw $this->createNotFoundException('Please set all fields!');
                }
                $secretText = $data['secretText'];
                $expireAfter = $data['expireAfter'];
                $expireAfterViews = $data['expireAfterViews'];
        }

        $secret = SecretFactory::create(
            $secretText,
            $expireAfter,
            $expireAfterViews
        );

        $this->secretRepository->save($secret);

        $data = $this->setResponseBody($secret);

        $response = new Response();
        $response->setStatusCode(201, 'Created');

        $this->setResponseByAcceptType($acceptType, $data, $response);
        
        return $response;
    }

    #[Route('/secret/{hash}', name: 'show_secret', methods:['GET'])]
    public function showSecret(string $hash, Request $request): Response
    {
        $acceptType = $request->headers->get('Accept');

        $secret = $this->secretRepository->findOneByHash($hash);

        if (!$secret) {
            throw $this->createNotFoundException(
                'No product found for id ' . $hash
            );
        }

        $isExpired = $this->validateSecret($secret);

        if ($isExpired) {
            throw $this->createNotFoundException(
                'Secret is no longer availabe'
            );
        }

        $this->decreaseRemainingViews($secret);

        $data = $this->setResponseBody($secret);

        $response = new Response();

        $this->setResponseByAcceptType($acceptType, $data, $response);
    
        return $response;
    }

    public function validateSecret(Secret $secret): bool
    {
        $now = new DateTime();

        if ($secret->getExpiresAt() < $now || $secret->getRemainingViews() === 0) {
            return true;
        }

        return false;
    }

    public function decreaseRemainingViews (Secret $secret) 
    {
        $secret->setRemainingViews($secret->getRemainingViews()-1);

        $this->secretRepository->save($secret);

    }

    public function setResponseBody(Secret $secret): array
    {
        $data = [
            'hash' => $secret->getHash(),
            'secretText' => $secret->getSecretText(),
            'createdAt' => $secret->getCreatedAt()->format('Y-m-d H:i:s'),
            'expiresAt' => $secret->getExpiresAt()->format('Y-m-d H:i:s'),
            'remainingViews' => $secret->getRemainingViews()
        ];

        return $data;
    }

    public function setResponseByAcceptType(string $acceptType, array $data, Response $response): Response
    {

        $resp = $response;

        switch ($acceptType) {
            case 'text/json':
                $response->setContent(json_encode($data));
                $response->headers->set('Content-Type', 'application/json');
                break;
            case 'text/xml':
                $data = array_flip($data);
                $xml = new SimpleXMLElement('<secret/>');
                array_walk_recursive($data, array($xml, 'addChild'));
                $response->setContent($xml->asXML());
                $response->headers->set('Content-Type', 'application/xml');
                break;
            default:
                $response->setContent(json_encode($data));
                $response->headers->set('Content-Type', 'application/json');
        }

        return $resp;
    }

}
