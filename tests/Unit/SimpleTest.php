<?php


namespace Tests\Unit;

use Tests\TestCase;

class SimpleTest extends TestCase
{
    public function test_basic_application_works()
    {
        $this->assertTrue(true);
    }
    
    public function test_environment_is_testing()
    {
        $this->assertEquals('testing', app()->environment());
    }
}