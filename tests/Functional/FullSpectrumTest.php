<?php

namespace UMA\Tests\Psr7HmacBundle;

use GuzzleHttp\Psr7\ServerRequest;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TestProject\AppBundle\Entity\TestUser;
use UMA\Psr7Hmac\Signer;

class FullSpectrumTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function authenticatedRequest()
    {
        $request = new ServerRequest('GET', 'http://testproject.com', ['Api-Key' => TestUser::TEST_APIKEY]);

        $signedRequest = (new Signer(TestUser::TEST_SECRET))->sign($request);

        $sfRequest = (new HttpFoundationFactory())
            ->createRequest($signedRequest);

        $sfResponse = $this->doRequest($sfRequest);

        $this->assertSame(200, $sfResponse->getStatusCode());
        $this->assertSame(sprintf("Successfully authenticated as user '%s'", TestUser::TEST_APIKEY), $sfResponse->getContent());
    }

    /**
     * @test
     * @dataProvider unauthorizedRequestProvider
     */
    public function unauthorizedRequest(ServerRequest $request)
    {
        $sfRequest = (new HttpFoundationFactory())
            ->createRequest($request);

        $sfResponse = $this->doRequest($sfRequest);

        $this->assertSame(401, $sfResponse->getStatusCode());
    }

    public function unauthorizedRequestProvider()
    {
        $baseRequest = new ServerRequest('GET', 'localhost:8000');
        $withApiKey = $baseRequest->withHeader('Api-Key', TestUser::TEST_APIKEY);
        $withBadApiKey = $baseRequest->withHeader('Api-Key', 'made-up-key');
        $signedBaseRequest = (new Signer(TestUser::TEST_SECRET))->sign($baseRequest);

        return [
            'no Api-Key header nor signed' => [$baseRequest],
            'no Api-Key header, signed' => [$signedBaseRequest],
            'with Api-Key header but not signed' => [$withApiKey],
            'with bogus Api-Key' => [$withBadApiKey],
        ];
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    private function doRequest(Request $request)
    {
        Debug::enable();
        $kernel = new \TestKernel();

        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return $response;
    }
}
