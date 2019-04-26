<?php

namespace Islandora\Crayfish\Commons\Syn\tests;

use Islandora\Crayfish\Commons\Syn\JwtAuthenticator;
use Islandora\Crayfish\Commons\Syn\JwtFactory;
use Islandora\Crayfish\Commons\Syn\JwtUser;
use Islandora\Crayfish\Commons\Syn\SettingsParser;
use Namshi\JOSE\SimpleJWS;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class JwtAuthenticatorTest extends \PHPUnit_Framework_TestCase
{
    public function getParser($site = null, $token = null)
    {
        if ($site === null) {
            $site = [
                'https://foo.com' => ['algorithm' => '', 'key' => '' , 'url' => 'https://foo.com']
            ];
        }
        if ($token === null) {
            $token = [
                'testtoken' => ['user' => 'test', 'roles' => ['1', '2'], 'token' => 'testToken']
            ];
        }
        $prophet = $this->prophesize(SettingsParser::class);
        $prophet->getStaticTokens()->willReturn($token);
        $prophet->getSites()->willReturn($site);
        return $prophet->reveal();
    }

    public function getJwtFactory($jwt)
    {
        $prophet = $this->prophesize(JwtFactory::class);
        $prophet->load(Argument::any())->willReturn($jwt);
        return $prophet->reveal();
    }

    public function getUserProvider()
    {
        $prophet = $this->prophesize(UserProviderInterface::class);
        return $prophet->reveal();
    }

    public function getSimpleAuth()
    {
        $jwt = $this->prophesize(SimpleJWS::class)->reveal();
        $parser = $this->getParser();
        $jwtFactory = $this->getJwtFactory($jwt);
        return new JwtAuthenticator($parser, $jwtFactory);
    }

    public function testAuthenticationFailure()
    {
        $auth = $this->getSimpleAuth();

        $request = $this->prophesize(Request::class)->reveal();
        $exception = $this->prophesize(AuthenticationException::class)->reveal();

        $response = $auth->onAuthenticationFailure($request, $exception);
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testAuthenticationStart()
    {
        $auth = $this->getSimpleAuth();

        $request = $this->prophesize(Request::class)->reveal();
        $exception = $this->prophesize(AuthenticationException::class)->reveal();

        $response = $auth->start($request, $exception);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testAuthenticationSuccess()
    {
        $auth = $this->getSimpleAuth();

        $request = $this->prophesize(Request::class)->reveal();
        $token = $this->prophesize(TokenInterface::class)->reveal();

        $response = $auth->onAuthenticationSuccess($request, $token, null);
        $this->assertNull($response);
    }

    public function testRememberMe()
    {
        $auth = $this->getSimpleAuth();
        $this->assertFalse($auth->supportsRememberMe());
    }

    public function headerHelper($request)
    {
        $auth = $this->getSimpleAuth();
        $credentials = $auth->getCredentials($request);
        return $credentials;
    }

    public function testNoHeader()
    {
        $request = new Request();
        $this->assertNull($this->headerHelper($request));
    }

    public function testHeaderNoBearer()
    {
        $request = new Request();
        $request->headers->set("Authorization", "foo");
        $this->assertNull($this->headerHelper($request));
    }

    public function testHeaderBadToken()
    {
        $request = new Request();
        $request->headers->set("Authorization", "Bearer foo");
        $this->assertNull($this->headerHelper($request));
    }

    public function headerTokenHelper($data, $expired = false)
    {
        $parser = $this->getParser();
        $request = new Request();
        $request->headers->set("Authorization", "Bearer foo");
        $prophet = $this->prophesize(SimpleJWS::class);
        $prophet->getPayload()->willReturn($data);
        $prophet->isExpired()->willReturn($expired);
        $jwt = $prophet->reveal();
        $jwtFactory = $this->getJwtFactory($jwt);
        $auth = new JwtAuthenticator($parser, $jwtFactory);
        $credentials = $auth->getCredentials($request);
        return $credentials;
    }

    public function testHeaderTokenFields()
    {
        $data = [
            'webid' => 1,
            'iss' => 'https://foo.com',
            'sub' => 'charlie',
            'roles' => ['bartender', 'exterminator'],
            'iat' => 1,
            'exp' => 1,
        ];
        $this->assertTrue(is_array($this->headerTokenHelper($data)));

        $missing = $data;
        unset($missing['webid']);
        $this->assertNull($this->headerTokenHelper($missing));

        $missing = $data;
        unset($missing['iss']);
        $this->assertNull($this->headerTokenHelper($missing));

        $missing = $data;
        unset($missing['sub']);
        $this->assertNull($this->headerTokenHelper($missing));

        $missing = $data;
        unset($missing['roles']);
        $this->assertNull($this->headerTokenHelper($missing));

        $missing = $data;
        unset($missing['iat']);
        $this->assertNull($this->headerTokenHelper($missing));

        $missing = $data;
        unset($missing['exp']);
        $this->assertNull($this->headerTokenHelper($missing));

        $this->assertNull($this->headerTokenHelper($data, true));
    }

    public function jwtAuthHelper($data, $parser, $valid = true)
    {
        $request = new Request();
        $request->headers->set("Authorization", "Bearer foo");

        $prophet = $this->prophesize(SimpleJWS::class);
        $prophet->getPayload()->willReturn($data);
        $prophet->isExpired()->willReturn(false);
        $prophet->isValid(Argument::any(), Argument::any())->willReturn($valid);
        $jwt = $prophet->reveal();
        $jwtFactory = $this->getJwtFactory($jwt);
        $auth = new JwtAuthenticator($parser, $jwtFactory);
        $credentials = $auth->getCredentials($request);
        $this->assertNotNull($credentials);
        $this->assertEquals('charlie', $credentials['name']);
        $this->assertEquals('foo', $credentials['token']);
        $this->assertTrue(in_array('bartender', $credentials['roles']));
        $this->assertTrue(in_array('exterminator', $credentials['roles']));

        $user = $auth->getUser($credentials, $this->getUserProvider());
        $this->assertInstanceOf(JwtUser::class, $user);
        $this->assertEquals('charlie', $user->getUsername());
        $this->assertEquals(['bartender', 'exterminator'], $user->getRoles());
        return $auth->checkCredentials($credentials, $user);
    }

    public function testJwtAuthentication()
    {
        $data = [
            'webid' => 1,
            'iss' => 'https://foo.com',
            'sub' => 'charlie',
            'roles' => ['bartender', 'exterminator'],
            'iat' => 1,
            'exp' => 1,
        ];
        $parser = $this->getParser();
        $this->assertTrue($this->jwtAuthHelper($data, $parser));
    }

    public function testJwtAuthenticationInvalidJwt()
    {
        $data = [
            'webid' => 1,
            'iss' => 'https://foo.com',
            'sub' => 'charlie',
            'roles' => ['bartender', 'exterminator'],
            'iat' => 1,
            'exp' => 1,
        ];
        $parser = $this->getParser();
        $this->assertFalse($this->jwtAuthHelper($data, $parser, false));
    }

    public function testJwtAuthenticationNoSite()
    {
        $data = [
            'webid' => 1,
            'iss' => 'https://www.pattyspub.ca/',
            'sub' => 'charlie',
            'roles' => ['bartender', 'exterminator'],
            'iat' => 1,
            'exp' => 1,
        ];
        $parser = $this->getParser();
        $this->assertFalse($this->jwtAuthHelper($data, $parser));
    }

    public function testJwtAuthenticationDefaultSite()
    {
        $data = [
            'webid' => 1,
            'iss' => 'https://www.pattyspub.ca/',
            'sub' => 'charlie',
            'roles' => ['bartender', 'exterminator'],
            'iat' => 1,
            'exp' => 1,
        ];
        $site = [
            'default' => ['algorithm' => '', 'key' => '' , 'url' => 'default']
        ];
        $parser = $this->getParser($site);
        $this->assertTrue($this->jwtAuthHelper($data, $parser));
    }

    public function testStaticToken()
    {
        $auth = $this->getSimpleAuth();
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer testtoken');
        $credentials = $auth->getCredentials($request);
        $this->assertNotNull($credentials);
        $this->assertEquals('test', $credentials['name']);
        $this->assertEquals(['1', '2'], $credentials['roles']);
        $this->assertEquals('testToken', $credentials['token']);

        $user = $auth->getUser($credentials, $this->getUserProvider());
        $this->assertInstanceOf(JwtUser::class, $user);
        $this->assertEquals('test', $user->getUsername());
        $this->assertEquals(['1', '2'], $user->getRoles());

        $this->assertTrue($auth->checkCredentials($credentials, $user));
    }
}
