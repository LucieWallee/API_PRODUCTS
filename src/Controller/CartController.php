<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Entity\Cart;
use App\Repository\DiscountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;

class CartController extends AbstractController
{
    #[Route('/api/carts/{id}', name: 'detailCart', methods: ['GET'])]
    public function getDetailCart(Cart $cart, SerializerInterface $serializer): JsonResponse
    {
        $jsonCart = $serializer->serialize($cart, 'json');
        return new JsonResponse($jsonCart, Response::HTTP_OK, ['accept' => 'json'], true);
    }
    
    #[Route('/api/cart', name: 'createCart', methods: ['POST'])]
    public function createCart(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $cart = $serializer->deserialize($request->getContent(), Cart::class, 'json');
        $cart->setTotalAmount(0);
        $em->persist($cart);
        $em->flush();

        $jsonCart = $serializer->serialize($cart, 'json', ['groups' => 'getDetailCart']);

        $location = $urlGenerator->generate('detailCart', ['id' => $cart->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonCart, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/api/cart/add-product/{id}', name: 'addProductToCart', methods: ['POST'])]
    public function AddProductCart(Cart $cart, Request $request, SerializerInterface $serializer, ProductRepository $productRepository, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $content = $request->toArray();

        $idproduct = $content['idProduct'] ?? -1;

        $product = $productRepository->find($idproduct);
        
        $cart->addProduct($product);
        $cart->setTotalAmount($this->totalAmountCalculate($cart));

        $em->persist($cart);
        $em->flush();

        $jsonCart = $serializer->serialize($cart, 'json', ['groups' => 'getDetailProduct']);

        $location = $urlGenerator->generate('detailCart', ['id' => $cart->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonCart, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/api/cart/add-discount/{id}', name: 'addDiscountToCart', methods: ['POST'])]
    public function AddDiscountCart(Cart $cart, Request $request, SerializerInterface $serializer, DiscountRepository $discountRepository, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $content = $request->toArray();
        $codeDiscount = $content['codeDiscount'] ?? -1;
        $discount = $discountRepository->findByCode($codeDiscount)[0];
        
        $cart->setDiscount($discount);
        $cart->setTotalAmount($this->totalAmountCalculate($cart));

        $em->persist($cart);
        $em->flush();

        $jsonCart = $serializer->serialize($cart, 'json', ['groups' => 'getDetailProduct']);

        $location = $urlGenerator->generate('detailCart', ['id' => $cart->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonCart, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    public function totalAmountCalculate(Cart $cart): int
    {
        $discount = $cart->getDiscount();
        $totalAmount = 0;
        if ($discount != null) {
            switch ($discount->getType()) {
                case 'all' :
                    foreach($cart->getProducts() as $product){
                        $totalAmount += $product->getPrice();
                    }
                    $totalAmount = $this->calculByType($discount->getTypeReduction(), $totalAmount, $discount->getAmount());
                    break;
                case 'quantity' :
                    $quantityProducts = count($cart->getProducts());
                    $quantityFree = intval($quantityProducts / $discount->getLine());
                    $priceList = array();
                    foreach($cart->getProducts() as $product){
                        array_push($priceList, $product->getPrice());
                    }
                    rsort($priceList);
                    for ($i = 0; $i< ($quantityProducts-$quantityFree); $i++) {
                        $totalAmount += $priceList[$i];
                    }
                    break;
            }
        }
        else {
            foreach($cart->getProducts() as $product){
                $totalAmount += $product->getPrice();
            }
        }
        return $totalAmount;
    }

    public function calculByType(String $type, int $amount, int $discountAmount): int
    {
        if($type == 'percent'){
            $amount = $amount * $discountAmount / 100;
        }
        return $amount;
    }
}
