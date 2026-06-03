<?php

namespace Tests\Unit\Enums;

use App\Enums\DeploymentEnvironment;
use Tests\TestCase;

class DeploymentEnvironmentTest extends TestCase
{
    public function test_isCommercial_returns_true_for_commercial_case()
    {
        $this->assertTrue(DeploymentEnvironment::Commercial->isCommercial());
    }

    public function test_isCommercial_returns_false_for_selfhosted_case()
    {
        $this->assertFalse(DeploymentEnvironment::SelfHosted->isCommercial());
    }

    public function test_isSelfHosted_returns_true_for_selfhosted_case()
    {
        $this->assertTrue(DeploymentEnvironment::SelfHosted->isSelfHosted());
    }

    public function test_isSelfHosted_returns_false_for_commercial_case()
    {
        $this->assertFalse(DeploymentEnvironment::Commercial->isSelfHosted());
    }
}
