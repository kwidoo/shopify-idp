<?php

namespace Tests\Unit;

use App\Contracts\ShopifyResponseService;
use App\Data\ShopifyResponseData;
use App\Exceptions\ShopifyApiException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Mockery;
use Spatie\LaravelData\Data;
use Tests\TestCase;

class ShopifyResponseServiceTest extends TestCase
{
    private ShopifyResponseService $shopifyResponseService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shopifyResponseService = app(ShopifyResponseService::class);
    }

    public function testIsSuccessfulWithValidResponse()
    {
        $mockResponse = $this->createMockResponse(200, ['data' => ['test' => true]]);

        $result = $this->shopifyResponseService->isSuccessful($mockResponse);

        $this->assertTrue($result);
    }

    public function testIsSuccessfulWithErrorInJson()
    {
        $mockResponse = $this->createMockResponse(200, ['errors' => ['Something went wrong']]);

        $result = $this->shopifyResponseService->isSuccessful($mockResponse);

        $this->assertFalse($result);
    }

    public function testIsSuccessfulWithHttpError()
    {
        $mockResponse = $this->createMockResponse(422, ['message' => 'Validation failed']);
        $mockResponse->shouldReceive('successful')->andReturn(false);

        $result = $this->shopifyResponseService->isSuccessful($mockResponse);

        $this->assertFalse($result);
    }

    public function testGetErrorMessagesWithStandardErrors()
    {
        $errors = ['field' => 'This field is required'];
        $mockResponse = $this->createMockResponse(422, ['errors' => $errors]);

        $result = $this->shopifyResponseService->getErrorMessages($mockResponse);

        $this->assertEquals($errors, $result);
    }

    public function testGetErrorMessagesWithStringError()
    {
        $mockResponse = $this->createMockResponse(400, ['error' => 'Invalid request']);

        $result = $this->shopifyResponseService->getErrorMessages($mockResponse);

        $this->assertEquals(['error' => 'Invalid request'], $result);
    }

    public function testHandleErrorsThrowsException()
    {
        $mockResponse = $this->createMockResponse(400, ['errors' => ['field' => 'Invalid value']]);
        $mockResponse->shouldReceive('successful')->andReturn(false);
        $mockResponse->shouldReceive('effectiveUri')->andReturn('https://test-store.myshopify.com/api/endpoint');

        // Expect Log::error to be called
        Log::shouldReceive('error')->once();

        $this->expectException(ShopifyApiException::class);

        $this->shopifyResponseService->handleErrors($mockResponse);
    }

    public function testHandleErrorsReturnsErrorsWithoutThrowing()
    {
        $errors = ['field' => 'Invalid value'];
        $mockResponse = $this->createMockResponse(400, ['errors' => $errors]);
        $mockResponse->shouldReceive('successful')->andReturn(false);
        $mockResponse->shouldReceive('effectiveUri')->andReturn('https://test-store.myshopify.com/api/endpoint');

        // Expect Log::error to be called
        Log::shouldReceive('error')->once();

        $result = $this->shopifyResponseService->handleErrors($mockResponse, false);

        $this->assertEquals($errors, $result);
    }

    public function testTransformResponse()
    {
        // Create a test class that extends Spatie Data for testing
        $testData = ['name' => 'Test Product', 'price' => 19.99];
        $mockResponse = $this->createMockResponse(200, $testData);

        // Mock ShopifyResponseData::from call
        $testDataObject = ShopifyResponseData::success($testData);

        // Mock the Data::from static method to return our prepared object
        $dataClass = new class($testDataObject) {
            private $testObject;

            public function __construct($testObject)
            {
                $this->testObject = $testObject;
            }

            public static function from($data, $context = null)
            {
                return Mockery::mock(Data::class);
            }
        };

        $result = $this->shopifyResponseService->transformResponse($mockResponse, get_class($dataClass));

        $this->assertInstanceOf(Data::class, $result);
    }

    /**
     * Create a mock Response with the given status code and data
     */
    private function createMockResponse(int $status, array $data): Response
    {
        $mock = Mockery::mock(Response::class);
        $mock->shouldReceive('status')->andReturn($status);
        $mock->shouldReceive('json')->andReturn($data);
        $mock->shouldReceive('successful')->andReturn($status < 400);

        return $mock;
    }
}
