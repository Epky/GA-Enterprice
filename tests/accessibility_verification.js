/**
 * Accessibility Verification Script for Inline Creator
 * 
 * This script can be run in the browser console to verify
 * accessibility features are properly implemented.
 * 
 * Usage:
 * 1. Open the product create/edit page
 * 2. Open browser console (F12)
 * 3. Copy and paste this script
 * 4. Run: verifyAccessibility('category-modal')
 */

function verifyAccessibility(modalId) {
    console.log('🔍 Starting Accessibility Verification...\n');
    
    const modal = document.getElementById(modalId);
    if (!modal) {
        console.error('❌ Modal not found:', modalId);
        return;
    }
    
    const results = {
        passed: [],
        failed: [],
        warnings: []
    };
    
    // Test 1: Modal has proper ARIA attributes
    console.log('Testing modal ARIA attributes...');
    if (modal.getAttribute('role') === 'dialog') {
        results.passed.push('✅ Modal has role="dialog"');
    } else {
        results.failed.push('❌ Modal missing role="dialog"');
    }
    
    if (modal.getAttribute('aria-modal') === 'true') {
        results.passed.push('✅ Modal has aria-modal="true"');
    } else {
        results.failed.push('❌ Modal missing aria-modal="true"');
    }
    
    if (modal.getAttribute('aria-labelledby')) {
        results.passed.push('✅ Modal has aria-labelledby');
    } else {
        results.failed.push('❌ Modal missing aria-labelledby');
    }
    
    if (modal.getAttribute('aria-describedby')) {
        results.passed.push('✅ Modal has aria-describedby');
    } else {
        results.warnings.push('⚠️ Modal missing aria-describedby (optional but recommended)');
    }
    
    // Test 2: Form inputs have labels
    console.log('Testing form labels...');
    const form = modal.querySelector('form');
    if (form) {
        const inputs = form.querySelectorAll('input:not([type="hidden"]), textarea, select');
        let allLabeled = true;
        
        inputs.forEach(input => {
            const id = input.id;
            const label = form.querySelector(`label[for="${id}"]`);
            
            if (!label && !input.getAttribute('aria-label')) {
                results.failed.push(`❌ Input "${id}" has no associated label`);
                allLabeled = false;
            }
        });
        
        if (allLabeled) {
            results.passed.push('✅ All form inputs have associated labels');
        }
    }
    
    // Test 3: Required fields have aria-required
    console.log('Testing required field attributes...');
    const requiredInputs = modal.querySelectorAll('[required]');
    let allHaveAriaRequired = true;
    
    requiredInputs.forEach(input => {
        if (input.getAttribute('aria-required') !== 'true') {
            results.failed.push(`❌ Required input "${input.id}" missing aria-required="true"`);
            allHaveAriaRequired = false;
        }
    });
    
    if (allHaveAriaRequired && requiredInputs.length > 0) {
        results.passed.push('✅ All required fields have aria-required="true"');
    }
    
    // Test 4: Error containers have ARIA live regions
    console.log('Testing ARIA live regions...');
    const errorContainer = modal.querySelector('[id$="-error-container"]');
    if (errorContainer) {
        if (errorContainer.getAttribute('role') === 'alert' && 
            errorContainer.getAttribute('aria-live') === 'assertive') {
            results.passed.push('✅ Error container has proper ARIA live region');
        } else {
            results.failed.push('❌ Error container missing proper ARIA attributes');
        }
    }
    
    const successContainer = modal.querySelector('[id$="-success-container"]');
    if (successContainer) {
        if (successContainer.getAttribute('role') === 'status' && 
            successContainer.getAttribute('aria-live') === 'polite') {
            results.passed.push('✅ Success container has proper ARIA live region');
        } else {
            results.failed.push('❌ Success container missing proper ARIA attributes');
        }
    }
    
    // Test 5: Buttons have accessible names
    console.log('Testing button accessibility...');
    const buttons = modal.querySelectorAll('button');
    let allButtonsAccessible = true;
    
    buttons.forEach(button => {
        const hasText = button.textContent.trim().length > 0;
        const hasAriaLabel = button.getAttribute('aria-label');
        const hasAriaLabelledby = button.getAttribute('aria-labelledby');
        
        if (!hasText && !hasAriaLabel && !hasAriaLabelledby) {
            results.failed.push(`❌ Button has no accessible name`);
            allButtonsAccessible = false;
        }
    });
    
    if (allButtonsAccessible) {
        results.passed.push('✅ All buttons have accessible names');
    }
    
    // Test 6: Icons are marked as decorative
    console.log('Testing decorative icons...');
    const icons = modal.querySelectorAll('svg');
    let allIconsMarked = true;
    
    icons.forEach(icon => {
        if (!icon.getAttribute('aria-hidden')) {
            results.warnings.push('⚠️ SVG icon not marked with aria-hidden="true"');
            allIconsMarked = false;
        }
    });
    
    if (allIconsMarked && icons.length > 0) {
        results.passed.push('✅ All decorative icons marked with aria-hidden');
    }
    
    // Test 7: Focus management
    console.log('Testing focus management...');
    const focusableElements = modal.querySelectorAll(
        'a, button, input:not([type="hidden"]), textarea, select, [tabindex]:not([tabindex="-1"])'
    );
    
    if (focusableElements.length > 0) {
        results.passed.push(`✅ Modal has ${focusableElements.length} focusable elements`);
    } else {
        results.failed.push('❌ Modal has no focusable elements');
    }
    
    // Test 8: Check for sr-only class
    console.log('Testing screen reader only content...');
    const srOnlyElements = document.querySelectorAll('.sr-only');
    if (srOnlyElements.length > 0) {
        results.passed.push(`✅ Found ${srOnlyElements.length} screen reader only elements`);
    } else {
        results.warnings.push('⚠️ No screen reader only content found (may be intentional)');
    }
    
    // Print results
    console.log('\n' + '='.repeat(60));
    console.log('📊 ACCESSIBILITY VERIFICATION RESULTS');
    console.log('='.repeat(60) + '\n');
    
    console.log('✅ PASSED (' + results.passed.length + ')');
    results.passed.forEach(msg => console.log('  ' + msg));
    
    if (results.warnings.length > 0) {
        console.log('\n⚠️  WARNINGS (' + results.warnings.length + ')');
        results.warnings.forEach(msg => console.log('  ' + msg));
    }
    
    if (results.failed.length > 0) {
        console.log('\n❌ FAILED (' + results.failed.length + ')');
        results.failed.forEach(msg => console.log('  ' + msg));
    }
    
    console.log('\n' + '='.repeat(60));
    
    const totalTests = results.passed.length + results.failed.length;
    const passRate = ((results.passed.length / totalTests) * 100).toFixed(1);
    
    console.log(`\n📈 Pass Rate: ${passRate}% (${results.passed.length}/${totalTests})`);
    
    if (results.failed.length === 0) {
        console.log('\n🎉 All accessibility tests passed!');
    } else {
        console.log('\n⚠️  Some tests failed. Please review the issues above.');
    }
    
    return {
        passed: results.passed.length,
        failed: results.failed.length,
        warnings: results.warnings.length,
        passRate: passRate
    };
}

// Test keyboard navigation
function testKeyboardNavigation(modalId) {
    console.log('🎹 Testing Keyboard Navigation...\n');
    
    const modal = document.getElementById(modalId);
    if (!modal) {
        console.error('❌ Modal not found:', modalId);
        return;
    }
    
    console.log('Instructions:');
    console.log('1. Open the modal by clicking "Add New" button');
    console.log('2. Press Tab key multiple times');
    console.log('3. Verify focus moves through all elements');
    console.log('4. Press Shift+Tab to move backwards');
    console.log('5. Press Escape to close modal');
    console.log('6. Verify focus returns to trigger button');
    console.log('\nManual verification required.');
}

// Export functions for use
window.verifyAccessibility = verifyAccessibility;
window.testKeyboardNavigation = testKeyboardNavigation;

console.log('✅ Accessibility verification script loaded!');
console.log('Run: verifyAccessibility("category-modal") or verifyAccessibility("brand-modal")');
console.log('Run: testKeyboardNavigation("category-modal") for keyboard testing instructions');
