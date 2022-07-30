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
     * @Route("/secret", name="secret_new", methods={"POST"})
     */

    // New secret adding 
    public function new(ManagerRegistry $doctrine, Request $request): Response
    {

        $secret = new Secret();
        $time = new \DateTime();
        $time2 = new \DateTime();

    
        // Type of header makeing from cutting first of Accept header
        $typeOfHeaderAccept = substr($request->headers->get('Accept'), 12);

        // Request the secret Text data from post and generate a hash of it
        $secretText = $request->request->get('secretText');
        
        
        // Request the expireAfter data from post and add time to current date
        $expireAfter = $request->request->get('expireAfter');
        
        
        
    

        if ($secretText == "" || is_numeric($expireAfter) == false  || $request->request->get('remainingViews') == 0 || is_numeric($request->request->get('remainingViews')) == False ) {
            $secret = 'Invalid input ' . header("Status: 404 Invalid input");

            $secretController = new SecretController();

            return $secretController->resposeSecretByHeaderAcceptType($secret, $typeOfHeaderAccept);
        } else {

            $expiresTime = $time->add(new DateInterval('PT' . $expireAfter . 'M'));
            $entityManager = $doctrine->getManager();
            
            // To get unique hash to identify the secret we generate it from all secret data
            $hash = $secretText.$time2->format('Y-m-d\TH:i:sp').$expireAfter.$expiresTime->format('Y-m-d\TH:i:sp').$expireAfter;
            $hash =  hash('sha1', $hash);
            
            // Setting datas to $secret
            $secret->setSecretText($secretText);
            $secret->setHash($hash . '');
            $secret->setRemainingViews($request->request->get('remainingViews'));
            $secret->setCreatedAt($time2);
            $secret->setExpiresAt($expiresTime);

            //Push data what in secret to database
            $entityManager->persist($secret);
            $entityManager->flush();

            $secretController = new SecretController();

            header("Status: 200 successful operation");
            return $secretController->resposeSecretByHeaderAcceptType($secret, $typeOfHeaderAccept);
        }
    }

    /**
     * @Route("/secret/{hash}", name="secret_hash", methods={"GET"})
     */

    // Get secret datas
    public function show(string $hash, ManagerRegistry $doctrine, Request $request): Response
    {
        $entityManager = $doctrine->getManager();

        // Type of header makeing from cutting first of Accept header
        $typeOfHeaderAccept = substr($request->headers->get('Accept'), 12);

        $secret = $doctrine->getRepository(Secret::class)->findOneBy(['hash' => $hash]);

        //Date expire calculator
        $timenow = new \DateTime();
        $dateExpiresAt = $secret->getExpiresAt();

        //View counter calculator
        $views = $secret->getRemainingViews();
        $secret->setRemainingViews($views - 1);

        if ($timenow > $dateExpiresAt || $views <= 0 ) {
            $secret = 'Secret not found ' . header("Status: 404 Secret not found");

            $secretController = new SecretController();

            return $secretController->resposeSecretByHeaderAcceptType($secret, $typeOfHeaderAccept);
        } else {
            //Push changed $secret datas to database
            $entityManager->persist($secret);
            $entityManager->flush();

            $secretController = new SecretController();

            header("Status: 200 successful operation");
            return $secretController->resposeSecretByHeaderAcceptType($secret, $typeOfHeaderAccept);
        }
    }
    // Header accept input check
    public function resposeSecretByHeaderAcceptType($secret, $typeOfHeaderAccept)
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
                    $secret,
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
                    $secret,
                    'xml',
                    ['attributes' =>
                    ['hash', 'secretText', 'createdAt', 'expiresAt', 'remainingViews']]
                );
                $response = new Response($serializedSecret);
                $response->headers->set('Content-Type', $typeOfHeaderAccept);

                return $response;
                break;

            case $typeOfHeaderAccept == "yaml":
                $secret = "Upcoming   ";

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
                    $secret,
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
