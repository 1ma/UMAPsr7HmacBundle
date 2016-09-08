<?php

namespace UMA\Tests\Psr7HmacBundle;

use GuzzleHttp\Psr7\ServerRequest;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TestProject\AppBundle\Entity\TestUser;
use UMA\Psr7Hmac\Signer;
use UMA\Psr7Hmac\Specification;

class FullSpectrumTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function authenticatedRequest()
    {
        $request = new ServerRequest('GET', 'http://testproject.com', ['X-Api-Key' => TestUser::TEST_APIKEY]);

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

        $this->assertSame(402, $sfResponse->getStatusCode());
        $this->assertSame('"This is a custom error response defined by the bundle user"', $sfResponse->getContent());
    }

    public function unauthorizedRequestProvider()
    {
        $baseRequest = new ServerRequest('GET', 'http://testproject.com');
        $withApiKey = $baseRequest->withHeader('X-Api-Key', TestUser::TEST_APIKEY);
        $withBadApiKey = $baseRequest->withHeader('X-Api-Key', 'made-up-key');
        $signedBaseRequest = (new Signer(TestUser::TEST_SECRET))->sign($baseRequest);
        $brokenSignature = (new Signer(TestUser::TEST_SECRET))->sign($withApiKey)
            ->withHeader(Specification::AUTH_HEADER, 'HMAC-SHA256 AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');

        return [
            'no Api-Key header nor signed' => [$baseRequest],
            'no Api-Key header, signed' => [$signedBaseRequest],
            'with Api-Key header but not signed' => [$withApiKey],
            'with Api-Key header and broken signature' => [$brokenSignature],
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
