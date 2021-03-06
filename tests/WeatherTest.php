<?php

namespace Luffluo\Weather\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Luffluo\Weather\Weather;
use GuzzleHttp\Psr7\Response;
use Mockery\Matcher\AnyArgs;
use PHPUnit\Framework\TestCase;
use Luffluo\Weather\Exceptions\HttpException;
use Luffluo\Weather\Exceptions\InvalidArgumentException;

class WeatherTest extends TestCase
{
    public function testGetWeatherWithInvalidType()
    {
        $weather = new Weather('mock-key');

        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Invalid type value(base/all): foo');

        $weather->getWeather('西安市', 'foo');

        $this->fail('Faild to asset getWeather throw exception with invalid argument.');
    }

    public function testGetWeatherWithInvalidFormat()
    {
        $weather = new Weather('mock-key');

        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Invalid response format: array');

        $weather->getWeather('西安市', 'base', 'array');

        // 如果没有抛出异常，就会运行到这行，标记当前测试没成功
        $this->fail('Faild to asset getWeather throw exception with invalid argument.');
    }

    public function testGetWeather()
    {
        // 创建模拟接口响应值。
        $response = new Response(200, [], '{"success": true}');

        // 创建模拟 http client。
        $client = \Mockery::mock(Client::class);

        // 指定将会产生的形为（在后续的测试中将会按下面的参数来调用）。
        $client->allows()->get('https://restapi.amap.com/v3/weather/weatherInfo', [
            'query' => [
                'key' => 'mock-key',
                'city' => '西安市',
                'output' => 'json',
                'extensions' => 'base',
            ],
        ])->andReturn($response);

        // 将 `getHttpClient` 方法替换为上面创建的 http client 为返回值的模拟方法。
        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client); // $client 为上面创建的模拟实例。

        // 然后调用 `getWeather` 方法，并断言返回值为模拟的返回值。
        $this->assertSame(['success' => true], $w->getWeather('西安市'));

        $response = new Response(200, [], '<hello>content</hello>');
        $client = \Mockery::mock(Client::class);
        $client->allows()->get('https://restapi.amap.com/v3/weather/weatherInfo', [
            'query' => [
                'key' => 'mock-key',
                'city' => '西安市',
                'output' => 'xml',
                'extensions' => 'all',
            ],
        ])->andReturn($response);

        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        $this->assertSame('<hello>content</hello>', $w->getWeather('西安市', 'all', 'xml'));
    }

    public function testGetWeatherWithGuzzleRuntimeException()
    {
        $client = \Mockery::mock(Client::class);
        $client->allows()
            // 由于上面的用例已经验证过参数传递，所以这里就不关心参数了
            ->get(new AnyArgs())
            ->andThrow(new \Exception('request timeout'));

        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('request timeout');

        $w->getWeather('西安市');
    }

    public function testGetHttpClient()
    {
        $w = new Weather('mock-key');

        $this->assertInstanceOf(ClientInterface::class, $w->getHttpClient());
    }

    public function testSetGuzzleOptions()
    {
        $w = new Weather('mock-key');

        $this->assertNull($w->getHttpClient()->getConfig('timeout'));

        $w->setGuzzleOptions(['timeout' => 5000]);

        $this->assertSame(5000, $w->getHttpClient()->getConfig('timeout'));
    }

    public function testGetLiveWeather()
    {
        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->expects()->getWeather('西安市', 'base', 'json')->andReturn(['success' => true]);

        $this->assertSame(['success' => true], $w->getLiveWeather('西安市'));
    }

    public function testGetForecastWeather()
    {
        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->expects()->getWeather('西安市', 'all', 'json')->andReturn(['success' => true]);

        $this->assertSame(['success' => true], $w->getForecastWeather('西安市'));
    }
}
