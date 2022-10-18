<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use  Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class ProductController extends AbstractController
{
    #[Route('/api/products', name: 'products', methods: ['GET'])]
    public function getProductList(SerializerInterface $serializer, ProductRepository $productRepository): JsonResponse
    {
        $productList = $productRepository->findAll();
        $jsonProductList = $serializer->serialize($productList, 'json');

        return new JsonResponse($jsonProductList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/products/{id}', name: 'detailProduct', methods: ['GET'])]
    public function getDetailProduct(Product $product, SerializerInterface $serializer): JsonResponse
    {
        $jsonProduct = $serializer->serialize($product, 'json');
        return new JsonResponse($jsonProduct, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[Route('/api/enabled/products', name: 'enabledProducts', methods: ['GET'])]
    public function getEnabledProductList(SerializerInterface $serializer, ProductRepository $productRepository): JsonResponse
    {
        $productList = $productRepository->findBy(array('enabled' => true));
        $jsonProduct = $serializer->serialize($productList, 'json');
        return new JsonResponse($jsonProduct, Response::HTTP_OK, [], true);
    }

    #[Route('/api/products/{id}', name: 'updateProduct', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier un produit')]
    public function updateProduct(Request $request, SerializerInterface $serializer, Product $currentProduct, EntityManagerInterface $em): JsonResponse
    {
        $updatedProduct = $serializer->deserialize($request->getContent(),
            Product::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentProduct]);
        $em->persist($updatedProduct);
        $em->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/api/stock/products/{id}', name: 'updateStockProduct', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier le stock d\'un produit')]
    public function updateStockProduct(SerializerInterface $serializer, Product $currentProduct, EntityManagerInterface $em): JsonResponse
    {
        $currentProduct->setStock($currentProduct->getStock()-1);
        $em->persist($currentProduct);
        $em->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
