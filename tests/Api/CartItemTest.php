<?php

namespace App\Tests\Api;

use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CartItemRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CartItemTest extends WebTestCase
{

    /** @var EntityManagerInterface */
    protected EntityManagerInterface $em;

    /** @var UserRepository  */
    protected UserRepository $userRepository;

    /** @var ProductRepository */
    protected ProductRepository $productRepository;

    /** @var CartItemRepository */
    protected CartItemRepository $cartItemRepository;

    /** @var User  */
    protected User $userTylor;


    /** @var User  */
    protected User $userMarla;

    /** @var KernelBrowser  */
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $container = $this->getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->productRepository = $container->get(ProductRepository::class);
        $this->cartItemRepository = $container->get(CartItemRepository::class);
        $this->userTylor = $this->userRepository->findOneBy(['firstName' => 'Tylor']);
        $this->userMarla = $this->userRepository->findOneBy(['firstName' => 'Marla']);
    }

    protected function resetCartItems(): void
    {
        $this->userTylor = $this->userRepository->find($this->userTylor->getId());
        foreach ($this->userTylor->getCartItems() as $toRemove) {
            $this->em->remove($toRemove);
        }
        $this->em->flush();
    }

    public function test_list_empty(): void
    {
        $this->resetCartItems();
        $this->client->request('get', "/api/users/{$this->userTylor->getId()}/cart-items");
        $response = $this->client->getResponse();

        static::assertEquals(200, $response->getStatusCode(), 'The response code should be 200.');

        $body = json_decode($response->getContent(), true);
        static::assertNotNull($body, 'Could not parse response.');
        static::assertIsArray($body, 'The body should be an array.');
        static::assertCount(0, $body, 'The list should contain zero items.');
    }

    public function test_list_user_not_found(): void
    {
        $this->client->request('get', "/api/users/9999/cart-items");
        $response = $this->client->getResponse();

        static::assertEquals(400, $response->getStatusCode(), 'The response code should be 400.');

        $body = json_decode($response->getContent(), true);
        static::assertResponseError($body, 'user');
    }

    public function test_list_single_item(): void
    {
        $this->resetCartItems();
        $product = $this->productRepository->findOneBy(['name' => 'paper']);
        $quantity = 3;

        $this->client->request('post', "/api/users/{$this->userTylor->getId()}/cart-items", content: json_encode([
            'product' => $product->getId(),
            'quantity' => $quantity,
        ]));

        $response = $this->client->getResponse();
        static::assertEquals(201, $response->getStatusCode(), 'The response code should be 201 Created.');

        $body = json_decode($response->getContent(), true);
        static::assertResponseCartItem($body, $product->getPrice(), $quantity);

        $item = $this->cartItemRepository->find($body['id']);
        static::assertDatabaseCartItem($item, $this->userTylor, $product, $quantity);

        $this->client->request('get', "/api/users/{$this->userTylor->getId()}/cart-items");
        $response = $this->client->getResponse();

        static::assertEquals(200, $response->getStatusCode(), 'The response code should be 200.');

        $body = json_decode($response->getContent(), true);
        static::assertNotNull($body, 'Could not parse response.');
        static::assertIsArray($body, 'The body should be an array.');
        static::assertCount(1, $body, 'The list should contain one item.');

        $responseItem = $body[0];
        static::assertResponseCartItem($responseItem, $product->getPrice(), $quantity);
        static::assertDatabaseCartItem($item, $this->userTylor, $product, $quantity);
    }

    public function test_create(): void
    {
        $this->resetCartItems();
        $product = $this->productRepository->findOneBy(['name' => 'pen']);

        $this->client->request('post', "/api/users/{$this->userTylor->getId()}/cart-items", content: json_encode([
                'product' => $product->getId(),
        ]));

        $response = $this->client->getResponse();
        static::assertEquals(201, $response->getStatusCode(), 'The response code should be 201 Created.');

        $body = json_decode($response->getContent(), true);
        static::assertResponseCartItem($body, $product->getPrice(), 1);

        $item = $this->cartItemRepository->find($body['id']);
        static::assertDatabaseCartItem($item, $this->userTylor, $product, 1);
    }

    public function test_create_with_quantity(): void
    {
        $this->resetCartItems();

        $quantity = 2;
        $product = $this->productRepository->findOneBy(['name' => 'pen']);

        $this->client->request('post', "/api/users/{$this->userTylor->getId()}/cart-items", content: json_encode([
            'product' => $product->getId(),
            'quantity' => $quantity,
        ]));

        $response = $this->client->getResponse();
        static::assertEquals(201, $response->getStatusCode(), 'The response code should be 201 Created.');

        $body = json_decode($response->getContent(), true);
        static::assertResponseCartItem($body, $product->getPrice(), $quantity);

        $item = $this->cartItemRepository->find($body['id']);
        static::assertDatabaseCartItem($item, $this->userTylor, $product, $quantity);
    }

    public function test_create_user_not_found(): void
    {
        $this->resetCartItems();
        $product = $this->productRepository->findOneBy(['name' => 'pen']);

        $this->client->request('post', "/api/users/9999/cart-items", content: json_encode([
            'product' => $product->getId(),
        ]));

        $response = $this->client->getResponse();
        static::assertEquals(400, $response->getStatusCode(), 'The response code should be 400.');

        $body = json_decode($response->getContent(), true);
        static::assertResponseError($body, 'user');
    }

    public function test_create_product_not_found(): void
    {
        $this->resetCartItems();

        $this->client->request('post', "/api/users/{$this->userTylor->getId()}/cart-items", content: json_encode([
            'product' => 9999,
        ]));

        $response = $this->client->getResponse();
        static::assertEquals(400, $response->getStatusCode(), 'The response code should be 400.');

        $body = json_decode($response->getContent(), true);
        static::assertResponseError($body, 'product');
    }

    public function test_edit(): void
    {
        $this->resetCartItems();

        $quantity = 2;
        $updatedQuantity = 5;
        $product = $this->productRepository->findOneBy(['name' => 'paper']);

        $this->client->request('post', "/api/users/{$this->userTylor->getId()}/cart-items", content: json_encode([
            'product' => $product->getId(),
            'quantity' => $quantity,
        ]));

        $response = $this->client->getResponse();
        static::assertEquals(201, $response->getStatusCode(), 'The response code should be 201 Created.');

        $body = json_decode($response->getContent(), true);
        static::assertResponseCartItem($body, $product->getPrice(), $quantity);
        $item = $this->cartItemRepository->find($body['id']);
        static::assertDatabaseCartItem($item, $this->userTylor, $product, $quantity);

        $this->client->request('put', "/api/users/{$this->userTylor->getId()}/cart-items/{$body['id']}", content: json_encode([
            'quantity' => $updatedQuantity,
        ]));

        $item = $this->cartItemRepository->find($body['id']);
        static::assertDatabaseCartItem($item, $this->userTylor, $product, $updatedQuantity);

        $response = $this->client->getResponse();
        static::assertEquals(200, $response->getStatusCode(), 'The response code should be 200.');

        $body = json_decode($response->getContent(), true);
        static::assertResponseCartItem($body, $product->getPrice(), $updatedQuantity);
    }

    public function test_edit_user_not_found(): void
    {
        $this->resetCartItems();

        $quantity = 2;
        $updatedQuantity = 5;
        $product = $this->productRepository->findOneBy(['name' => 'paper']);

        $this->client->request('post', "/api/users/{$this->userTylor->getId()}/cart-items", content: json_encode([
            'product' => $product->getId(),
            'quantity' => $quantity,
        ]));

        $response = $this->client->getResponse();
        static::assertEquals(201, $response->getStatusCode(), 'The response code should be 201 Created.');

        $body = json_decode($response->getContent(), true);
        static::assertResponseCartItem($body, $product->getPrice(), $quantity);
        $item = $this->cartItemRepository->find($body['id']);
        static::assertDatabaseCartItem($item, $this->userTylor, $product, $quantity);

        $this->client->request('put', "/api/users/9999/cart-items/{$body['id']}", content: json_encode([
            'quantity' => $updatedQuantity,
        ]));

        $response = $this->client->getResponse();
        static::assertEquals(400, $response->getStatusCode(), 'The response code should be 400.');

        $body = json_decode($response->getContent(), true);
        static::assertResponseError($body, 'user');
    }

    public function test_edit_user_different(): void
    {
        $this->resetCartItems();

        $quantity = 2;
        $updatedQuantity = 5;
        $product = $this->productRepository->findOneBy(['name' => 'paper']);

        $this->client->request('post', "/api/users/{$this->userTylor->getId()}/cart-items", content: json_encode([
            'product' => $product->getId(),
            'quantity' => $quantity,
        ]));

        $response = $this->client->getResponse();
        static::assertEquals(201, $response->getStatusCode(), 'The response code should be 201 Created.');

        $body = json_decode($response->getContent(), true);
        static::assertResponseCartItem($body, $product->getPrice(), $quantity);
        $item = $this->cartItemRepository->find($body['id']);
        static::assertDatabaseCartItem($item, $this->userTylor, $product, $quantity);

        $this->client->request('put', "/api/users/{$this->userMarla->getId()}/cart-items/{$body['id']}", content: json_encode([
            'quantity' => $updatedQuantity,
        ]));

        $response = $this->client->getResponse();
        static::assertEquals(400, $response->getStatusCode(), 'The response code should be 400.');

        $body = json_decode($response->getContent(), true);
        static::assertResponseError($body, 'item');
    }

    public function test_edit_item_not_found(): void
    {
        $this->resetCartItems();

        $quantity = 2;
        $updatedQuantity = 5;
        $product = $this->productRepository->findOneBy(['name' => 'paper']);

        $this->client->request('post', "/api/users/{$this->userTylor->getId()}/cart-items", content: json_encode([
            'product' => $product->getId(),
            'quantity' => $quantity,
        ]));

        $response = $this->client->getResponse();
        static::assertEquals(201, $response->getStatusCode(), 'The response code should be 201 Created.');

        $body = json_decode($response->getContent(), true);
        static::assertResponseCartItem($body, $product->getPrice(), $quantity);
        $item = $this->cartItemRepository->find($body['id']);
        static::assertDatabaseCartItem($item, $this->userTylor, $product, $quantity);

        $this->client->request('put', "/api/users/{$this->userTylor->getId()}/cart-items/9999", content: json_encode([
            'quantity' => $updatedQuantity,
        ]));

        $response = $this->client->getResponse();
        static::assertEquals(400, $response->getStatusCode(), 'The response code should be 400.');

        $body = json_decode($response->getContent(), true);
        static::assertResponseError($body, 'item');
    }

    public function test_delete(): void
    {
        $this->resetCartItems();

        $product = $this->productRepository->findOneBy(['name' => 'pen']);

        $this->client->request('post', "/api/users/{$this->userTylor->getId()}/cart-items", content: json_encode([
            'product' => $product->getId(),
        ]));

        $response = $this->client->getResponse();
        static::assertEquals(201, $response->getStatusCode(), 'The response code should be 201 Created.');

        $body = json_decode($response->getContent(), true);
        static::assertResponseCartItem($body, $product->getPrice(), 1);
        $item = $this->cartItemRepository->find($body['id']);
        static::assertDatabaseCartItem($item, $this->userTylor, $product, 1);

        $this->client->request('delete', "/api/users/{$this->userTylor->getId()}/cart-items/{$body['id']}");

        $item = $this->cartItemRepository->find($body['id']);
        static::assertNull($item, 'The cart-item should be deleted.');

        $response = $this->client->getResponse();
        static::assertEquals(200, $response->getStatusCode(), 'The response code should be 200.');

        /**
         * Actually I expected `$this->json(null)` to be null,
         * since `null` is valid json.
         */
        $body = json_decode($response->getContent(), true);
        static::assertIsArray($body, 'The response should be an array.');
        static::assertCount(0, $body, 'The cart-item should not be shown in the response.');
    }

    public function test_delete_user_not_found(): void
    {
        $this->resetCartItems();

        $product = $this->productRepository->findOneBy(['name' => 'pen']);

        $this->client->request('post', "/api/users/{$this->userTylor->getId()}/cart-items", content: json_encode([
            'product' => $product->getId(),
        ]));

        $response = $this->client->getResponse();
        static::assertEquals(201, $response->getStatusCode(), 'The response code should be 201 Created.');

        $body = json_decode($response->getContent(), true);
        static::assertResponseCartItem($body, $product->getPrice(), 1);
        $item = $this->cartItemRepository->find($body['id']);
        static::assertDatabaseCartItem($item, $this->userTylor, $product, 1);

        $this->client->request('delete', "/api/users/9999/cart-items/{$body['id']}");

        $response = $this->client->getResponse();
        static::assertEquals(400, $response->getStatusCode(), 'The response code should be 400.');

        $body = json_decode($response->getContent(), true);
        static::assertResponseError($body, 'user');
    }

    public function test_delete_user_different(): void
    {
        $this->resetCartItems();

        $product = $this->productRepository->findOneBy(['name' => 'pen']);

        $this->client->request('post', "/api/users/{$this->userTylor->getId()}/cart-items", content: json_encode([
            'product' => $product->getId(),
        ]));

        $response = $this->client->getResponse();
        static::assertEquals(201, $response->getStatusCode(), 'The response code should be 201 Created.');

        $body = json_decode($response->getContent(), true);
        static::assertResponseCartItem($body, $product->getPrice(), 1);
        $item = $this->cartItemRepository->find($body['id']);
        static::assertDatabaseCartItem($item, $this->userTylor, $product, 1);

        $this->client->request('delete', "/api/users/{$this->userMarla->getId()}/cart-items/{$body['id']}");

        $response = $this->client->getResponse();
        static::assertEquals(400, $response->getStatusCode(), 'The response code should be 400.');

        $body = json_decode($response->getContent(), true);
        static::assertResponseError($body, 'item');
    }

    public function test_delete_item_not_found(): void
    {
        $this->resetCartItems();

        $product = $this->productRepository->findOneBy(['name' => 'pen']);

        $this->client->request('post', "/api/users/{$this->userTylor->getId()}/cart-items", content: json_encode([
            'product' => $product->getId(),
        ]));

        $response = $this->client->getResponse();
        static::assertEquals(201, $response->getStatusCode(), 'The response code should be 201 Created.');

        $body = json_decode($response->getContent(), true);
        static::assertResponseCartItem($body, $product->getPrice(), 1);
        $item = $this->cartItemRepository->find($body['id']);
        static::assertDatabaseCartItem($item, $this->userTylor, $product, 1);

        $this->client->request('delete', "/api/users/{$this->userTylor->getId()}/cart-items/9999");

        $response = $this->client->getResponse();
        static::assertEquals(400, $response->getStatusCode(), 'The response code should be 400.');

        $body = json_decode($response->getContent(), true);
        static::assertResponseError($body, 'item');
    }

    protected static function assertDatabaseCartItem(CartItem $item, User $expectedUser, Product $expectedProduct, int $expectedQuantity): void
    {
        static::assertNotNull($item, 'The cart-item should exist in the database.');
        static::assertEquals($expectedQuantity, $item->getQuantity(), 'The cart-item quantity should be updated.');
        static::assertEquals($expectedUser->getId(), $item->getUser()->getId(), 'The cart-item user should be identical.');
        static::assertEquals($expectedProduct->getId(), $item->getProduct()->getId(), 'The cart-item product should be identical.');
    }

    protected static function assertResponseCartItem(?array $json, float $itemPrice, int $expectedQuantity): void
    {
        static::assertNotNull($json, 'Could not parse response.');
        static::assertIsArray($json, 'The body should be an array.');
        static::assertArrayHasKey('id', $json, 'The cart-item should have an id.');
        static::assertArrayHasKey('product', $json, 'The cart-item should have a product');
        static::assertArrayHasKey('id', $json['product'], 'The cart-items product should have an id.');
        static::assertArrayHasKey('price', $json, 'The cart-item should have a total price.');
        static::assertArrayHasKey('quantity', $json, 'The cart-item should have a quantity.');
        static::assertEquals($expectedQuantity, $json['quantity'], "The cart-item quantity should be {$expectedQuantity}.");
        static::assertEquals($itemPrice * $expectedQuantity, $json['price'], "The price should be quantity({$expectedQuantity}) times the product price.");
    }

    protected static function assertResponseError(?array $json, string $error): void
    {
        static::assertNotNull($json, 'Could not parse response.');
        static::assertIsArray($json, 'The body should be an array.');
        static::assertArrayHasKey($error, $json, "There should be a  {$error} in errors");
    }

}