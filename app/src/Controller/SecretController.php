<?php
// src/Controller/SecretController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Secret;
use DateInterval;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

// Secret Controller class
class SecretController extends AbstractController
{

    /**
     * @Route("/Secret", name="secret_addSecret", methods={"POST"})
     */

    // New secret adding 
    public function addSecret(ManagerRegistry $doctrine, Request $request): Response
    {

        $secretInText = new Secret();
        $time = new \DateTime();
        $currentTime = new \DateTime();

    
        // Type of header makeing from cutting first of Accept header
        $typeOfHeaderAccept = substr($request->headers->get('Accept'), 12);

        // Request the secret Text data from post and generate a hash of it
        $secret = $request->request->get('secret');
        
        
        // Request the expireAfter data from post 
        $expireAfter = $request->request->get('expireAfter');
        
        
        
    
        //Error handling
        if ($secret == "" || is_numeric($expireAfter) == false  || $request->request->get('expireAfterViews') == 0 || is_numeric($request->request->get('expireAfterViews')) == False ) {
            $secretInText = 'Invalid input ' . header("Status: 405 Invalid input");

            $secretController = new SecretController();

            return $secretController->resposeSecretByHeaderAcceptType($secretInText, $typeOfHeaderAccept);
        } else {
            //add expiresTime to current date
            $expiresTime = $time->add(new DateInterval('PT' . $expireAfter . 'M'));
            $entityManager = $doctrine->getManager();
            
            // To get unique hash to identify the secret we generate it from all secret data
            $hash = $secret.$currentTime->format('Y-m-d\TH:i:sp').$expireAfter.$expiresTime->format('Y-m-d\TH:i:sp').$expireAfter;
            $hash =  hash('sha1', $hash);
            
            // Setting datas to secret
            $secretInText->setSecretText($secret);
            $secretInText->setHash($hash . '');
            $secretInText->setRemainingViews($request->request->get('expireAfterViews'));
            $secretInText->setCreatedAt($currentTime);
            $secretInText->setExpiresAt($expiresTime);

            //Push data what in secret to database
            $entityManager->persist($secretInText);
            $entityManager->flush();

            $secretController = new SecretController();

            header("Status: 200 successful operation");
            return $secretController->resposeSecretByHeaderAcceptType($secretInText, $typeOfHeaderAccept);
        }
    }

    /**
     * @Route("/Secret/{hash}", name="secret_getSecretByHash", methods={"GET"})
     */

    // Get secret datas
    public function getSecretByHash(string $hash, ManagerRegistry $doctrine, Request $request): Response
    {
        $entityManager = $doctrine->getManager();

        // Type of header makeing from cutting first of Accept header
        $typeOfHeaderAccept = substr($request->headers->get('Accept'), 12);

        $secretInText = $doctrine->getRepository(Secret::class)->findOneBy(['hash' => $hash]);

        //Date expire calculator
        $timenow = new \DateTime();
        $dateExpiresAt = $secretInText->getExpiresAt();

        //View counter calculator
        $views = $secretInText->getRemainingViews();
        $secretInText->setRemainingViews($views - 1);

        if ($timenow > $dateExpiresAt || $views <= 0 ) {
            $secretInText = 'Secret not found ' . header("Status: 404 Secret not found");

            $secretController = new SecretController();

            return $secretController->resposeSecretByHeaderAcceptType($secretInText, $typeOfHeaderAccept);
        } else {
            //Push changed $secret datas to database
            $entityManager->persist($secretInText);
            $entityManager->flush();

            $secretController = new SecretController();

            header("Status: 200 successful operation");
            return $secretController->resposeSecretByHeaderAcceptType($secretInText, $typeOfHeaderAccept);
        }
    }
    // Header accept input check
    public function resposeSecretByHeaderAcceptType($secretInText, $typeOfHeaderAccept)
    {
        switch ($typeOfHeaderAccept) {
            case $typeOfHeaderAccept == "json":
                $encoder2 = new JsonEncoder();
                $normalizer = new GetSetMethodNormalizer();
                $serializer = new Serializer(
                    array(
                        new DateTimeNormalizer(array('datetime_format' => 'Y-m-d\TH:i:sp')),
                        $normalizer
                    ),
                    [$encoder2]
                );
                $serializedSecret = $serializer->serialize(
                    $secretInText,
                    'json',
                    ['attributes' =>
                    ['hash', 'secretText', 'createdAt', 'expiresAt', 'remainingViews']]
                );

                $response = new Response($serializedSecret);
                $response->headers->set('Content-Type', $typeOfHeaderAccept);

                return $response;
                break;

            case $typeOfHeaderAccept == "xml":
                $encoder = new XmlEncoder();
                $normalizer = new GetSetMethodNormalizer();

                $serializer = new Serializer(
                    array(
                        new DateTimeNormalizer(array('datetime_format' => 'Y-m-d\TH:i:sp')),
                        $normalizer
                    ),
                    [$encoder]

                );
                $serializedSecret = $serializer->serialize(
                    $secretInText,
                    'xml',
                    ['attributes' =>
                    ['hash', 'secretText', 'createdAt', 'expiresAt', 'remainingViews']]
                );
                $response = new Response($serializedSecret);
                $response->headers->set('Content-Type', $typeOfHeaderAccept);

                return $response;
                break;

            case $typeOfHeaderAccept == "yaml":
                $secretInText = "Upcoming   ";

                $encoder = new YamlEncoder();
                $normalizer = new GetSetMethodNormalizer();

                $serializer = new Serializer(
                    array(
                        new DateTimeNormalizer(array('datetime_format' => 'Y-m-d\TH:i:sp')),
                        $normalizer
                    ),
                    [$encoder]

                );
                $serializedSecret = $serializer->serialize(
                    $secretInText,
                    'yaml',
                    ['attributes' =>
                    ['hash', 'secretText', 'createdAt', 'expiresAt', 'remainingViews']]
                );

                $response = new Response($serializedSecret);
                $response->headers->set('Content-Type', $typeOfHeaderAccept);

                return $response;
                break;
        }
    }
}
