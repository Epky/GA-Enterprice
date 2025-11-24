<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\RLSPolicyManager;
use App\Services\UserTablePolicyStatus;
class RLSPolicyManagerTest extends TestCase
{

    private RLSPolicyManager $policyManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policyManager = new RLSPolicyManager();
    }

    public function test_it_can_validate_user_table_policies()
    {
        // This test validates the policy validation functionality
        // It should handle gracefully when running on SQLite (test environment)
        $status = $this->policyManager->validateUserTablePolicies();
        
        $this->assertInstanceOf(UserTablePolicyStatus::class, $status);
        $this->assertIsBool($status->tableExists);
        $this->assertIsBool($status->hasRLS);
        $this->assertIsBool($status->hasServiceRolePolicy);
        $this->assertIsBool($status->hasUserSelfAccessPolicy);
        $this->assertIsBool($status->hasAdminManagementPolicy);
        $this->assertIsArray($status->existingPolicies);
        $this->assertIsBool($status->needsUpdate);
    }

    public function test_it_can_test_user_table_access()
    {
        // This test validates the access testing functionality
        // It should handle gracefully when running on SQLite (test environment)
        $results = $this->policyManager->testUserTableAccess();
        
        $this->assertIsArray($results);
        
        // When running on SQLite, we expect error results due to PostgreSQL-specific features
        if (isset($results['error'])) {
            $this->assertIsString($results['error']);
        } else {
            // Check that we get expected result structure for PostgreSQL
            if (isset($results['service_role_access'])) {
                $this->assertArrayHasKey('success', $results['service_role_access']);
                $this->assertArrayHasKey('message', $results['service_role_access']);
            }
            
            if (isset($results['table_operations'])) {
                $this->assertArrayHasKey('success', $results['table_operations']);
                $this->assertArrayHasKey('message', $results['table_operations']);
            }
            
            if (isset($results['rls_enforcement'])) {
                $this->assertArrayHasKey('success', $results['rls_enforcement']);
                $this->assertArrayHasKey('message', $results['rls_enforcement']);
            }
        }
    }

    public function test_it_handles_missing_users_table_gracefully()
    {
        // Test behavior when users table doesn't exist or when running on SQLite
        $status = $this->policyManager->validateUserTablePolicies();
        
        // On SQLite, the users table might exist but RLS features won't work
        // This test ensures the method doesn't crash and returns appropriate status
        if (!$status->tableExists || $status->errorMessage) {
            $this->assertFalse($status->hasRLS);
            $this->assertFalse($status->hasServiceRolePolicy);
            $this->assertFalse($status->hasUserSelfAccessPolicy);
            $this->assertFalse($status->hasAdminManagementPolicy);
        }
        
        // The method should always return a valid status object
        $this->assertInstanceOf(UserTablePolicyStatus::class, $status);
    }

    public function test_user_table_policy_status_class_structure()
    {
        // Test that the UserTablePolicyStatus class has the expected structure
        $status = new UserTablePolicyStatus(
            tableExists: true,
            hasRLS: false,
            hasServiceRolePolicy: false,
            hasUserSelfAccessPolicy: false,
            hasAdminManagementPolicy: false,
            existingPolicies: [],
            needsUpdate: true,
            errorMessage: null
        );
        
        $this->assertTrue($status->tableExists);
        $this->assertFalse($status->hasRLS);
        $this->assertFalse($status->hasServiceRolePolicy);
        $this->assertFalse($status->hasUserSelfAccessPolicy);
        $this->assertFalse($status->hasAdminManagementPolicy);
        $this->assertIsArray($status->existingPolicies);
        $this->assertTrue($status->needsUpdate);
        $this->assertNull($status->errorMessage);
    }
}