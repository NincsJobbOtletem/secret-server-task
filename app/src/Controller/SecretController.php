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

        // request the secret Text data from post and generate a hash of it
        $secretText = $request->request->get('secretText');
        $hash =  hash('sha256', $secretText);

        // request the expireAfter data from post and add time to current date
        $expireAfter = $request->request->get('expireAfter');
        $expiresTime = $time->add(new DateInterval('PT' . $expireAfter . 'M'));
        $entityManager = $doctrine->getManager();

        // Setting datas
        $secret->setSecretText($secretText);
        $secret->setHash($hash . '');
        $secret->setRemainingViews($request->request->get('remainingViews'));

        $secret->setCreatedAt($time2);
        $secret->setExpiresAt($expiresTime);

        //push data what in secret to database
        $entityManager->persist($secret);
        $entityManager->flush();

        //return succes message
        return $this->json('Created new secret successfully with id ' . $secret->getId());
    }

    /**
     * @Route("/secret/{hash}", name="secret_show", methods={"GET"})
     */

    // Get secret datas
    public function show(string $hash, ManagerRegistry $doctrine, Request $request): Response
    {
        $entityManager = $doctrine->getManager();

        $secret = $doctrine->getRepository(Secret::class)->findOneBy(['hash' => $hash]);

        $timenow = new \DateTime();
        $dateExpiresAt = $secret->getExpiresAt();

        //View counter calculator
        $views = $secret->getRemainingViews();
        $secret->setRemainingViews($views - 1);

        if (!$secret) {

            return $this->json('Invalid input ' . $hash, 404);
        }
        if ($timenow > $dateExpiresAt) {

            return $this->json('im out of time! ' . $hash, 404);
        }
        if ($views <= 0) {

            return $this->json('Youre out of touch! ' . $hash, 404);
        }

        //Push change view data to database
        $entityManager->persist($secret);
        $entityManager->flush();

        //Get the header accept input
        $headerAccept = $request->headers->get('Accept');

        
        if ($headerAccept == "*/*") {
            return $this->json('invalid header input');
        }
        //cutted the first 12 letter of "application/json" to get the end
        $typeOfData = substr($headerAccept, 12);

        $secretController = new SecretController();

        $serializedSecret = $secretController->typeOfAcceptHeader($secret, $typeOfData);

        $response = new Response($serializedSecret);
        $response->headers->set('Content-Type', $typeOfData);

        return $response;
    }
    // Header accept input check
    public function typeOfAcceptHeader($secret, $typeOfData)
    {
        switch ($typeOfData) {
            case $typeOfData == "json":
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

                return $serializedSecret;
                break;

            case $typeOfData == "xml":
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

                return $serializedSecret;
                break;

            case $typeOfData == "yaml":
                echo "Upcoming   ";

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

                return $serializedSecret;
                break;
        }
    }
}
