<?php

namespace App\Http\Request\Api\Users;

use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use App\Http\Request\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class DeleteCartItemRequest extends Request
{

    #[NotNull(message: 'The user was not found.')]
    protected ?User $user = null;

    #[NotNull(message: 'The product was not found')]
    protected ?CartItem $item = null;

    public function __construct(RequestStack $requestStack, ValidatorInterface $validator, ?EntityManagerInterface $em)
    {
        parent::__construct($requestStack, $validator);

        $user = $this->request->get('user');
        if (!is_null($user)) {
            $this->user = $em->getRepository(User::class)->find($user);
        }

        $item = $this->request->get('item');
        if (!is_null($user)) {
            $this->item = $em->getRepository(CartItem::class)->find($item);
        }

        if ($this->item?->getUser()?->getId() !== $this->getUser()?->getId()) {
            $this->item = null;
        }

    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getItem(): ?CartItem
    {
        return $this->item;
    }

}