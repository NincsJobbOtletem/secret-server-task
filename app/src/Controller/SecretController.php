<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Secret;
use DateTime;
use DateInterval;
use Symfony\Component\VarDumper\Cloner\Data;

// /**
//  * @Route("/api", name="api_")
//  */
class SecretController extends AbstractController
{
    /**
     * @Route("/secret", name="secret_index", methods={"GET"})
     */
    // public function index(ManagerRegistry $doctrine): Response
    // {
    //     $secrets = $doctrine
    //         ->getRepository(Secret::class)
    //         ->findAll();

    //     $data = [];

    //     foreach ($secrets as $secret) {
    //         $data[] = [
    //             'id' => $secret->getId(),
    //             'hash' => $secret->getHash(),
    //             'secretText' => $secret->getSecretText(),
    //             'expiresAt' => $secret->getExpiresAt(),
    //             'remainingViews' => $secret->getRemainingViews(),
    //             'createdAt' => $secret->getCreatedAt(),
    //         ];
    //     }


    //     return $this->json($data);
    // }

    /**
     * @Route("/secret", name="secret_new", methods={"POST"})
     */
    public function new(ManagerRegistry $doctrine, Request $request): Response
    {

        $secret = new Secret();

        $time = new \DateTime();
        $time2 = new \DateTime();

        $message = $request->request->get('secretText');
        $hash =  hash('sha256', $message);
        $expireAfter = $request->request->get('expireAfter');

        $expiresTime = $time->add(new DateInterval('PT' . $expireAfter . 'M'));


        $entityManager = $doctrine->getManager();


        // $secret->setHash($request->request->get('hash'));
        $secret->setSecretText($request->request->get('secretText'));
        $secret->setHash($hash . '');
        $secret->setRemainingViews($request->request->get('remainingViews'));
        // $secret->setCreatedAt(new \DateTime($time2->format('H:i:s Y-m-d')));
        $secret->setCreatedAt($time2);
        $secret->setExpiresAt($expiresTime);

        // echo($secret->setCreatedAt());

        // var_dump($request->request->get('expiresAt'));
        // $time = new \DateTime();
        // echo $time->format('H:i:s \O\n Y-m-d');

        $entityManager->persist($secret);
        $entityManager->flush();

        return $this->json('Created new secret successfully with id ' . $secret->getId());
    }

    /**
     * @Route("/secret/{hash}", name="secret_show", methods={"GET"})
     */
    public function show(string $hash, ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        
        

        $secret = $doctrine->getRepository(Secret::class)->findOneBy(['hash' => $hash]);


        $timenow = new \DateTime();
        $dateExpiresAt = $secret->getExpiresAt();

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
        
        

        
        $entityManager->persist($secret);
        $entityManager->flush();


        
        
        $data =  [

            'hash' => $secret->getHash(),
            'secretText' => $secret->getSecretText(),
            'createdAt' => $secret->getCreatedAt(),
            'expiresAt' => $secret->getExpiresAt(),
            'remainingViews' => $secret->getRemainingViews(),
        ];

        return $this->json($data);
    }

    // /**
    //  * @Route("/secret/{id}", name="secret_edit", methods={"PUT"})
    //  */
    // public function edit(Request $request, int $id, ManagerRegistry $doctrine): Response
    // {
    //     $entityManager = $doctrine->getManager();
    //     $secret = $entityManager->getRepository(Secret::class)->find($id);

    //     if (!$secret) {
    //         return $this->json('No secret found for id' . $id, 404);
    //     }

    //     $secret->setHash($request->request->get('hash'));
    //     $secret->setSecretText($request->request->get('secretText'));
    //     $secret->setRemainingViews($request->request->get('remainingViews'));
    //     $entityManager->flush();

    //     $data =  [
    //         'id' => $secret->getId(),
    //         'hash' => $secret->getHash(),
    //         'secretText' => $secret->getSecretText(),
    //         'remainingViews' => $secret->getRemainingViews(),
    //     ];

    //     return $this->json($data);
    // }

    // /**
    //  * @Route("/secret/{id}", name="secret_delete", methods={"DELETE"})
    //  */
    // public function delete(int $id, ManagerRegistry $doctrine): Response
    // {
    //     $entityManager = $doctrine->getManager();
    //     $secret = $entityManager->getRepository(Secret::class)->find($id);

    //     if (!$secret) {
    //         return $this->json('No secret found for id' . $id, 404);
    //     }

    //     $entityManager->remove($secret);
    //     $entityManager->flush();

    //     return $this->json('Deleted a secret successfully with id ' . $id);
    // }
}
