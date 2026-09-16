<?php

declare(strict_types=1);

namespace Tracking\Tests\Feature;

use Illuminate\Container\Container;
use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tracking\TrackingProvider;

class ServiceProviderTest extends TestCase
{
    /**
     * Get the service provider class.
     */
    protected function getServiceProviderClass(): string
    {
        return TrackingProvider::class;
    }

    /**
     * @test
     */
    public function test_it_is_a_service_provider()
    {
        $class = $this->getServiceProviderClass();

        $reflection = new ReflectionClass($class);

        $provider = new ReflectionClass(ServiceProvider::class);

        $msg = "Expected class '{$class}' to be a service provider.";

        $this->assertTrue($reflection->isSubclassOf($provider), $msg);
    }

    /**
     * @test
     */
    public function test_it_has_package_name()
    {
        $class = $this->getServiceProviderClass();
        $provider = new $class(new Container);

        $this->assertSame('tracking', $provider->packageName);
    }
}
