<?php
 
namespace App\Factory;
 
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\SerializerInterface;

class Factory
{
    // public function __construct(private SerializerInterface $serializer)
    // {
    // }
 
    // public function create(object $data, string $xmlRoot, int $status = 200, array $headers = []): Response
    // {
    //     return new Response(
    //         $this->serializer->serialize(
    //             $data,
    //             XmlEncoder::FORMAT,
    //             [
    //                 XmlEncoder::ROOT_NODE_NAME => $xmlRoot,
    //                 XmlEncoder::ENCODING => 'UTF-8',
    //             ]
    //         ),
    //         $status,
    //         array_merge($headers, ['Content-Type' => 'application/xml;charset=UTF-8'])
    //     );
    // }
}