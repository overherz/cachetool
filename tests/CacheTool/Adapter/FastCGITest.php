<?php

namespace CacheTool\Adapter;

use CacheTool\Code;

class FastCGITest extends \PHPUnit_Framework_TestCase
{
    public function testRun()
    {
        $fcgi = new FastCGI();
        $fcgi->setTempDir(sys_get_temp_dir());
        $fcgi->setLogger($this->getMockBuilder('Monolog\Logger')->disableOriginalConstructor()->getMock());

        $code = Code::fromString('return true;');

        try {
            $result = $fcgi->run($code);
            $this->assertTrue($result);
        } catch (\RuntimeException $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }

    public function testGetScriptFileNameWithChroot()
    {
        $tmpdir = sys_get_temp_dir();
        $fcgi = new FastCGI(null, $tmpdir);
        $class = new \ReflectionClass($fcgi);
        $method = $class->getMethod('getScriptFileName');
        $method->setAccessible(true);

        self::assertEquals('/test.php', $method->invoke($fcgi, "{$tmpdir}/test.php"));
    }

    public function testGetScriptFileNameWithoutChroot()
    {
        $fcgi = new FastCGI(null);
        $class = new \ReflectionClass($fcgi);
        $method = $class->getMethod('getScriptFileName');
        $method->setAccessible(true);

        self::assertEquals('/tmp/test.php', $method->invoke($fcgi, '/tmp/test.php'));
    }

    public function testRunWithChroot()
    {
        $fcgi = $this->getMockBuilder('\CacheTool\Adapter\FastCGI')
            ->setMethods(array('getScriptFileName'))
            ->setConstructorArgs(array(null, sys_get_temp_dir()))
            ->getMock();

        $reflection = new \ReflectionClass($fcgi);
        $reflectionClient = $reflection->getProperty('client');
        $reflectionClient->setAccessible(true);

        $clientMock = $this->getMockBuilder('\Adoy\FastCGI\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $reflectionClient->setValue($fcgi, $clientMock);

        $fileName = '/tmp/testRunWithChroot/test.php';
        $fcgi->expects(self::once())
            ->method('getScriptFileName')
            ->willReturn($fileName);

        $clientMock->expects(self::once())
            ->method('request')
            ->with(
                array(
                    'REQUEST_METHOD'  => 'POST',
                    'REQUEST_URI'     => '/',
                    'SCRIPT_FILENAME' => $fileName
                ),
                ''
            )
            ->willReturn("Content-type: text/html; charset=UTF-8\r\n\r\na:2:{s:6:\"result\";b:1;s:6:\"errors\";a:0:{}}");

        $fcgi->setTempDir(sys_get_temp_dir());
        $fcgi->setLogger($this->getMockBuilder('Monolog\Logger')->disableOriginalConstructor()->getMock());

        $code = Code::fromString('return true;');

        try {
            $result = $fcgi->run($code);
            $this->assertTrue($result);
        } catch (\RuntimeException $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }
}
