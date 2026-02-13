<?php

use App\Entity\Bulletin;

require __DIR__ . '/vendor/autoload.php';

class MockViolationBuilder {
    private $message;
    private $path;
    private $parent;

    public function __construct($message, $parent) {
        $this->message = $message;
        $this->parent = $parent;
    }

    public function atPath($path) {
        $this->path = $path;
        return $this;
    }

    public function addViolation() {
        $this->parent->addStoredViolation($this->message, $this->path);
    }
}

class MyMockContext {
    public $violations = [];

    public function buildViolation($message) {
        return new MockViolationBuilder($message, $this);
    }

    public function addStoredViolation($message, $path) {
        $this->violations[] = ['msg' => $message, 'path' => $path];
    }
}

function testValidation($year, $scenario) {
    echo "--- Testing: $scenario ($year) ---\n";
    $b = new Bulletin();
    $b->setAcademicYear($year);
    $ctx = new MyMockContext();
    
    // Call the validation method directly
    // Note: We need to cast our mock to ExecutionContextInterface if we want strictness, 
    // but for internal logic test we can just call it if we use loose typing or mock properly.
    // In Bulletin.php it's typed. Let's see if we can bypass the type hint for testing.
    try {
        $reflection = new ReflectionMethod(Bulletin::class, 'validateAcademicYear');
        $reflection->invoke($b, $ctx);
    } catch (Throwable $e) {
        echo "FAIL: " . $e->getMessage() . "\n";
        return;
    }
    
    if (empty($ctx->violations)) {
        echo "✅ OK: No violations detected.\n";
    } else {
        foreach ($ctx->violations as $v) {
            echo "❌ ERROR [{$v['path']}]: {$v['msg']}\n";
        }
    }
    echo "\n";
}

testValidation('2025/2026', 'Valid year');
testValidation('2025-2026', 'Invalid format');
testValidation('2023/2025', 'Invalid range (gap)');
testValidation('2025/2024', 'Reversed range');
testValidation('2027/2028', 'Future year (>2026)');
testValidation('2026/2027', 'Current edge year');
