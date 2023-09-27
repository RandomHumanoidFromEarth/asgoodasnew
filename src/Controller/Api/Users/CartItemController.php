<?php

namespace App\Controller\Api\Users;

use App\Entity\CartItem;
use App\Http\Request\Api\Users\CreateCartItemRequest;
use App\Http\Request\Api\Users\DeleteCartItemRequest;
use App\Http\Request\Api\Users\EditCartItemRequest;
use App\Http\Request\Api\Users\ListCartItemsRequest;
use App\Repository\CartItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class CartItemController extends AbstractController
{

    public function list(ListCartItemsRequest $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$request->validate()) {
            return $this->json($request->getViolations())->setStatusCode(400);
        }

        /** @var CartItemRepository $cartItemRepository */
        $cartItemRepository = $em->getRepository(CartItem::class);
        $items = $cartItemRepository->findByUser($request->getUser());
        return $this->json($items);
    }

    public function create(CreateCartItemRequest $request, EntityManagerInterface $em): JsonResponse
    {

        if (!$request->validate()) {
            return $this->json($request->getViolations())->setStatusCode(400);
        }

        /** @var CartItemRepository $cartItemRepository */
        $cartItemRepository = $em->getRepository(CartItem::class);
        $item = $cartItemRepository->findOneByUserAndProduct($request->getUser(), $request->getProduct());

        if (!is_null($item)) {
            return $this->json([
                'item' => ['The cart-item already exists.'],
            ])->setStatusCode(409);
        }

        $item = CartItem::new()
            ->setUser($request->getUser())
            ->setProduct($request->getProduct())
            ->setQuantity($request->getQuantity());

        $em->persist($item);
        $em->flush();

        return $this->json($item)->setStatusCode(201);
    }

    public function edit(EditCartItemRequest $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$request->validate()) {
            return $this->json($request->getViolations())->setStatusCode(400);
        }

        $item = $request->getItem();
        $item->setQuantity($request->getQuantity());
        $em->persist($item);
        $em->flush();
        return $this->json($item);
    }

    public function delete(DeleteCartItemRequest $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$request->validate()) {
            return $this->json($request->getViolations())->setStatusCode(400);
        }

        $em->remove($request->getItem());
        $em->flush();
        return $this->json(null);
    }

}