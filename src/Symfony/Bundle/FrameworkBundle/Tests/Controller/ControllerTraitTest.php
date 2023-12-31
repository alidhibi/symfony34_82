<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\Controller;

use Doctrine\Common\Persistence\ManagerRegistry as LegacyManagerRegistry;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Serializer\SerializerInterface;

abstract class ControllerTraitTest extends TestCase
{
    abstract protected function createController();

    public function testForward(): void
    {
        $request = Request::create('/');
        $request->setLocale('fr');
        $request->setRequestFormat('xml');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')->getMock();
        $kernel->expects($this->once())->method('handle')->willReturnCallback(static fn(Request $request): \Symfony\Component\HttpFoundation\Response => new Response($request->getRequestFormat().'--'.$request->getLocale()));

        $container = new Container();
        $container->set('request_stack', $requestStack);
        $container->set('http_kernel', $kernel);

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->forward('a_controller');
        $this->assertEquals('xml--fr', $response->getContent());
    }

    public function testGetUser(): void
    {
        $user = new User('user', 'pass');
        $token = new UsernamePasswordToken($user, 'pass', 'default', ['ROLE_USER']);

        $controller = $this->createController();
        $controller->setContainer($this->getContainerWithTokenStorage($token));

        $this->assertSame($controller->getUser(), $user);
    }

    public function testGetUserAnonymousUserConvertedToNull(): void
    {
        $token = new AnonymousToken('default', 'anon.');

        $controller = $this->createController();
        $controller->setContainer($this->getContainerWithTokenStorage($token));

        $this->assertNull($controller->getUser());
    }

    public function testGetUserWithEmptyTokenStorage(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->getContainerWithTokenStorage(null));

        $this->assertNull($controller->getUser());
    }

    public function testGetUserWithEmptyContainer(): void
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('The SecurityBundle is not registered in your application.');
        $controller = $this->createController();
        $controller->setContainer(new Container());

        $controller->getUser();
    }

    /**
     * @param $token
     *
     * @return Container
     */
    private function getContainerWithTokenStorage($token = null)
    {
        $tokenStorage = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage')->getMock();
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $container = new Container();
        $container->set('security.token_storage', $tokenStorage);

        return $container;
    }

    public function testJson(): void
    {
        $controller = $this->createController();
        $controller->setContainer(new Container());

        $response = $controller->json([]);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals('[]', $response->getContent());
    }

    public function testJsonWithSerializer(): void
    {
        $container = new Container();

        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $serializer
            ->expects($this->once())
            ->method('serialize')
            ->with([], 'json', ['json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS])
            ->willReturn('[]');

        $container->set('serializer', $serializer);

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->json([]);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals('[]', $response->getContent());
    }

    public function testJsonWithSerializerContextOverride(): void
    {
        $container = new Container();

        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $serializer
            ->expects($this->once())
            ->method('serialize')
            ->with([], 'json', ['json_encode_options' => 0, 'other' => 'context'])
            ->willReturn('[]');

        $container->set('serializer', $serializer);

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->json([], 200, [], ['json_encode_options' => 0, 'other' => 'context']);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals('[]', $response->getContent());
        $response->setEncodingOptions(\JSON_FORCE_OBJECT);
        $this->assertEquals('{}', $response->getContent());
    }

    public function testFile(): void
    {
        $container = new Container();
        $kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')->getMock();
        $container->set('http_kernel', $kernel);

        $controller = $this->createController();
        $controller->setContainer($container);

        /* @var BinaryFileResponse $response */
        $response = $controller->file(new File(__FILE__));
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        if ($response->headers->get('content-type')) {
            $this->assertSame('text/x-php', $response->headers->get('content-type'));
        }

        $this->assertStringContainsString(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $response->headers->get('content-disposition'));
        $this->assertStringContainsString(basename(__FILE__), $response->headers->get('content-disposition'));
    }

    public function testFileAsInline(): void
    {
        $controller = $this->createController();

        /* @var BinaryFileResponse $response */
        $response = $controller->file(new File(__FILE__), null, ResponseHeaderBag::DISPOSITION_INLINE);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        if ($response->headers->get('content-type')) {
            $this->assertSame('text/x-php', $response->headers->get('content-type'));
        }

        $this->assertStringContainsString(ResponseHeaderBag::DISPOSITION_INLINE, $response->headers->get('content-disposition'));
        $this->assertStringContainsString(basename(__FILE__), $response->headers->get('content-disposition'));
    }

    public function testFileWithOwnFileName(): void
    {
        $controller = $this->createController();

        /* @var BinaryFileResponse $response */
        $fileName = 'test.php';
        $response = $controller->file(new File(__FILE__), $fileName);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        if ($response->headers->get('content-type')) {
            $this->assertSame('text/x-php', $response->headers->get('content-type'));
        }

        $this->assertStringContainsString(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $response->headers->get('content-disposition'));
        $this->assertStringContainsString($fileName, $response->headers->get('content-disposition'));
    }

    public function testFileWithOwnFileNameAsInline(): void
    {
        $controller = $this->createController();

        /* @var BinaryFileResponse $response */
        $fileName = 'test.php';
        $response = $controller->file(new File(__FILE__), $fileName, ResponseHeaderBag::DISPOSITION_INLINE);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        if ($response->headers->get('content-type')) {
            $this->assertSame('text/x-php', $response->headers->get('content-type'));
        }

        $this->assertStringContainsString(ResponseHeaderBag::DISPOSITION_INLINE, $response->headers->get('content-disposition'));
        $this->assertStringContainsString($fileName, $response->headers->get('content-disposition'));
    }

    public function testFileFromPath(): void
    {
        $controller = $this->createController();

        /* @var BinaryFileResponse $response */
        $response = $controller->file(__FILE__);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        if ($response->headers->get('content-type')) {
            $this->assertSame('text/x-php', $response->headers->get('content-type'));
        }

        $this->assertStringContainsString(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $response->headers->get('content-disposition'));
        $this->assertStringContainsString(basename(__FILE__), $response->headers->get('content-disposition'));
    }

    public function testFileFromPathWithCustomizedFileName(): void
    {
        $controller = $this->createController();

        /* @var BinaryFileResponse $response */
        $response = $controller->file(__FILE__, 'test.php');

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        if ($response->headers->get('content-type')) {
            $this->assertSame('text/x-php', $response->headers->get('content-type'));
        }

        $this->assertStringContainsString(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $response->headers->get('content-disposition'));
        $this->assertStringContainsString('test.php', $response->headers->get('content-disposition'));
    }

    public function testFileWhichDoesNotExist(): void
    {
        $this->expectException('Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException');
        $controller = $this->createController();

        $controller->file('some-file.txt', 'test.php');
    }

    public function testIsGranted(): void
    {
        $authorizationChecker = $this->getMockBuilder('Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface')->getMock();
        $authorizationChecker->expects($this->once())->method('isGranted')->willReturn(true);

        $container = new Container();
        $container->set('security.authorization_checker', $authorizationChecker);

        $controller = $this->createController();
        $controller->setContainer($container);

        $this->assertTrue($controller->isGranted('foo'));
    }

    public function testdenyAccessUnlessGranted(): void
    {
        $this->expectException('Symfony\Component\Security\Core\Exception\AccessDeniedException');
        $authorizationChecker = $this->getMockBuilder('Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface')->getMock();
        $authorizationChecker->expects($this->once())->method('isGranted')->willReturn(false);

        $container = new Container();
        $container->set('security.authorization_checker', $authorizationChecker);

        $controller = $this->createController();
        $controller->setContainer($container);

        $controller->denyAccessUnlessGranted('foo');
    }

    public function testRenderViewTwig(): void
    {
        $twig = $this->getMockBuilder('Twig\Environment')->disableOriginalConstructor()->getMock();
        $twig->expects($this->once())->method('render')->willReturn('bar');

        $container = new Container();
        $container->set('twig', $twig);

        $controller = $this->createController();
        $controller->setContainer($container);

        $this->assertEquals('bar', $controller->renderView('foo'));
    }

    public function testRenderTwig(): void
    {
        $twig = $this->getMockBuilder('Twig\Environment')->disableOriginalConstructor()->getMock();
        $twig->expects($this->once())->method('render')->willReturn('bar');

        $container = new Container();
        $container->set('twig', $twig);

        $controller = $this->createController();
        $controller->setContainer($container);

        $this->assertEquals('bar', $controller->render('foo')->getContent());
    }

    public function testStreamTwig(): void
    {
        $twig = $this->getMockBuilder('Twig\Environment')->disableOriginalConstructor()->getMock();

        $container = new Container();
        $container->set('twig', $twig);

        $controller = $this->createController();
        $controller->setContainer($container);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\StreamedResponse', $controller->stream('foo'));
    }

    public function testRedirectToRoute(): void
    {
        $router = $this->getMockBuilder('Symfony\Component\Routing\RouterInterface')->getMock();
        $router->expects($this->once())->method('generate')->willReturn('/foo');

        $container = new Container();
        $container->set('router', $router);

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->redirectToRoute('foo');

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertSame('/foo', $response->getTargetUrl());
        $this->assertSame(302, $response->getStatusCode());
    }

    /**
     * @runInSeparateProcess
     */
    public function testAddFlash(): void
    {
        $flashBag = new FlashBag();
        $session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')->disableOriginalConstructor()->getMock();
        $session->expects($this->once())->method('getFlashBag')->willReturn($flashBag);

        $container = new Container();
        $container->set('session', $session);

        $controller = $this->createController();
        $controller->setContainer($container);
        $controller->addFlash('foo', 'bar');

        $this->assertSame(['bar'], $flashBag->get('foo'));
    }

    public function testCreateAccessDeniedException(): void
    {
        $controller = $this->createController();

        $this->assertInstanceOf('Symfony\Component\Security\Core\Exception\AccessDeniedException', $controller->createAccessDeniedException());
    }

    public function testIsCsrfTokenValid(): void
    {
        $tokenManager = $this->getMockBuilder('Symfony\Component\Security\Csrf\CsrfTokenManagerInterface')->getMock();
        $tokenManager->expects($this->once())->method('isTokenValid')->willReturn(true);

        $container = new Container();
        $container->set('security.csrf.token_manager', $tokenManager);

        $controller = $this->createController();
        $controller->setContainer($container);

        $this->assertTrue($controller->isCsrfTokenValid('foo', 'bar'));
    }

    public function testGenerateUrl(): void
    {
        $router = $this->getMockBuilder('Symfony\Component\Routing\RouterInterface')->getMock();
        $router->expects($this->once())->method('generate')->willReturn('/foo');

        $container = new Container();
        $container->set('router', $router);

        $controller = $this->createController();
        $controller->setContainer($container);

        $this->assertEquals('/foo', $controller->generateUrl('foo'));
    }

    public function testRedirect(): void
    {
        $controller = $this->createController();
        $response = $controller->redirect('https://dunglas.fr', 301);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertSame('https://dunglas.fr', $response->getTargetUrl());
        $this->assertSame(301, $response->getStatusCode());
    }

    public function testRenderViewTemplating(): void
    {
        $templating = $this->getMockBuilder(\Symfony\Bundle\FrameworkBundle\Templating\EngineInterface::class)->getMock();
        $templating->expects($this->once())->method('render')->willReturn('bar');

        $container = new Container();
        $container->set('templating', $templating);

        $controller = $this->createController();
        $controller->setContainer($container);

        $this->assertEquals('bar', $controller->renderView('foo'));
    }

    public function testRenderTemplating(): void
    {
        $templating = $this->getMockBuilder(\Symfony\Bundle\FrameworkBundle\Templating\EngineInterface::class)->getMock();
        $templating->expects($this->once())->method('render')->willReturn('bar');

        $container = new Container();
        $container->set('templating', $templating);

        $controller = $this->createController();
        $controller->setContainer($container);

        $this->assertEquals('bar', $controller->render('foo')->getContent());
    }

    public function testStreamTemplating(): void
    {
        $templating = $this->getMockBuilder(\Symfony\Bundle\FrameworkBundle\Templating\EngineInterface::class)->getMock();

        $container = new Container();
        $container->set('templating', $templating);

        $controller = $this->createController();
        $controller->setContainer($container);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\StreamedResponse', $controller->stream('foo'));
    }

    public function testCreateNotFoundException(): void
    {
        $controller = $this->createController();

        $this->assertInstanceOf('Symfony\Component\HttpKernel\Exception\NotFoundHttpException', $controller->createNotFoundException());
    }

    public function testCreateForm(): void
    {
        $form = new Form($this->getMockBuilder(FormConfigInterface::class)->getMock());

        $formFactory = $this->getMockBuilder('Symfony\Component\Form\FormFactoryInterface')->getMock();
        $formFactory->expects($this->once())->method('create')->willReturn($form);

        $container = new Container();
        $container->set('form.factory', $formFactory);

        $controller = $this->createController();
        $controller->setContainer($container);

        $this->assertEquals($form, $controller->createForm('foo'));
    }

    public function testCreateFormBuilder(): void
    {
        $formBuilder = $this->getMockBuilder('Symfony\Component\Form\FormBuilderInterface')->getMock();

        $formFactory = $this->getMockBuilder('Symfony\Component\Form\FormFactoryInterface')->getMock();
        $formFactory->expects($this->once())->method('createBuilder')->willReturn($formBuilder);

        $container = new Container();
        $container->set('form.factory', $formFactory);

        $controller = $this->createController();
        $controller->setContainer($container);

        $this->assertEquals($formBuilder, $controller->createFormBuilder('foo'));
    }

    public function testGetDoctrine(): void
    {
        $doctrine = $this->getMockBuilder(interface_exists(ManagerRegistry::class) ? ManagerRegistry::class : LegacyManagerRegistry::class)->getMock();

        $container = new Container();
        $container->set('doctrine', $doctrine);

        $controller = $this->createController();
        $controller->setContainer($container);

        $this->assertEquals($doctrine, $controller->getDoctrine());
    }
}

trait TestControllerTrait
{
    use ControllerTrait {
        generateUrl as public;
        redirect as public;
        forward as public;
        getUser as public;
        json as public;
        file as public;
        isGranted as public;
        denyAccessUnlessGranted as public;
        redirectToRoute as public;
        addFlash as public;
        isCsrfTokenValid as public;
        renderView as public;
        render as public;
        stream as public;
        createNotFoundException as public;
        createAccessDeniedException as public;
        createForm as public;
        createFormBuilder as public;
        getDoctrine as public;
    }
}
