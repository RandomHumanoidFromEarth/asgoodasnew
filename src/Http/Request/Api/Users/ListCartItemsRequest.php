<?php

namespace App\Http\Request\Api\Users;

use App\Entity\Product;
use App\Entity\User;
use App\Http\Request\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ListCartItemsRequest extends Request
{

    #[NotNull(message: 'The user was not found.')]
    protected ?User $user = null;

    protected ValidatorInterface $validator;

    public function __construct(RequestStack $requestStack, ?EntityManagerInterface $em, ValidatorInterface $validator)
    {
        parent::__construct($requestStack, $validator);
        $user = $this->request->get('user');
        if (!is_null($user)) {
            $this->user = $em->getRepository(User::class)->find($user);
        }
    }


    public function getUser(): ?User
    {
        return $this->user;
    }

}