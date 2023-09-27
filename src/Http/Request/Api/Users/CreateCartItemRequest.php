<?php

namespace App\Http\Request\Api\Users;

use App\Entity\Product;
use App\Entity\User;
use App\Http\Request\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CreateCartItemRequest extends Request
{

    #[NotNull(message: 'The user was not found.')]
    protected ?User $user = null;

    #[NotNull(message: 'The product was not found')]
    protected ?Product $product = null;

    #[GreaterThan(0, message: 'The quantity must be grater than zero.')]
    protected int $quantity = 1;

    public function __construct(RequestStack $requestStack, ValidatorInterface $validator, ?EntityManagerInterface $em)
    {
        parent::__construct($requestStack, $validator);

        $user = $this->request->get('user');
        if (!is_null($user)) {
            $this->user = $em->getRepository(User::class)->find($user);
        }

        $body = json_decode($this->request->getContent(), true);

        if (is_null($body)) {
            return;
        }

        if (array_key_exists('product', $body)) {
            $this->product = $em->getRepository(Product::class)->find($body['product']);
        }

        if (array_key_exists('quantity', $body)) {
            $this->quantity = $body['quantity'];
        }
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

}