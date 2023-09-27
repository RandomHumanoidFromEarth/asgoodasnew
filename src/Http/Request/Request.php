<?php

namespace App\Http\Request;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class Request
{

    protected SymfonyRequest $request;

    protected ValidatorInterface $validator;

    /** @var array{string: array{int: string}} */
    private array $violations = [];

    public function __construct(RequestStack $requestStack, ValidatorInterface $validator)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->validator = $validator;
    }

    public function validate(): bool
    {
        $this->violations = [];
        $violations = $this->validator->validate($this);
        foreach ($violations as $violation) {
            $this->addViolation($violation->getPropertyPath(), $violation->getMessage());
        }
        return 0 === $violations->count();
    }

    private function addViolation(string $name, string $message): void
    {
        if (!array_key_exists($name, $this->violations)) {
            $this->violations[$name] = [];
        }
        $this->violations[$name][] = $message;
    }

    public function getViolations(): array
    {
        return $this->violations;
    }

}