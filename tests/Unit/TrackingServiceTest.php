<?php

namespace Tests\Unit;

use App\Services\TrackingService;
use Tests\TestCase;

class TrackingServiceTest extends TestCase
{
    private TrackingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TrackingService();
    }

    public function test_ip_truncation_removes_last_octet(): void
    {
        $method = new \ReflectionMethod(TrackingService::class, 'truncateIp');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, '192.168.1.123');
        $this->assertEquals('192.168.1.0', $result);
    }

    public function test_field_type_detection_identifies_password_fields(): void
    {
        $method = new \ReflectionMethod(TrackingService::class, 'detectFieldType');
        $method->setAccessible(true);

        $this->assertEquals('password', $method->invoke($this->service, 'password'));
        $this->assertEquals('password', $method->invoke($this->service, 'user_pwd'));
        $this->assertEquals('email', $method->invoke($this->service, 'email'));
        $this->assertEquals('email', $method->invoke($this->service, 'user_email'));
        $this->assertEquals('username', $method->invoke($this->service, 'username'));
        $this->assertEquals('text', $method->invoke($this->service, 'company_name'));
    }
}
