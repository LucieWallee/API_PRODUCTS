<?php

namespace App\Controller;

use App\Repository\DiscountRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

class DiscountController extends AbstractController
{
    #[Route('api/discounts', name: 'discounts')]
    public function getDiscountList(SerializerInterface $serializer, DiscountRepository $discountRepository): JsonResponse
    {
        $discountList = $discountRepository->findAll();
        $jsonDiscountList = $serializer->serialize($discountList, 'json');

        return new JsonResponse($jsonDiscountList, Response::HTTP_OK, [], true);
    }
}
