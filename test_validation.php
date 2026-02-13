<?php

use App\Entity\Bulletin;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

require __DIR__ . '/vendor/autoload.php';

$validator = Validation::createValidatorBuilder()
    ->enableAnnotationMapping()
    ->addDefaultDoctrineAnnotationReader()
    ->getValidator();

function testBulletin(Bulletin $bulletin, string $scenario) {
    global $validator;
    $violations = $validator->validate($bulletin);
    echo "--- Scenario: $scenario ---\n";
    if (count($violations) > 0) {
        foreach ($violations as $violation) {
            echo "Error [{$violation->getPropertyPath()}]: {$violation->getMessage()}\n";
        }
    } else {
        echo "Success: No violations.\n";
    }
    echo "\n";
}

// 1. Valid context
$b1 = new Bulletin();
$b1->setAcademicYear('2025/2026');
$b1->setMention('Bien');
$b1->setClassRank(1);
// Note: Student is ignored for this specific test as it requires a real entity
testBulletin($b1, "Valid Year 2025/2026");

// 2. Invalid format
$b2 = new Bulletin();
$b2->setAcademicYear('2025-2026');
$b2->setMention('Bien');
$b2->setClassRank(1);
testBulletin($b2, "Invalid Format (2025-2026)");

// 3. Invalid range
$b3 = new Bulletin();
$b3->setAcademicYear('2023/2025');
$b3->setMention('Bien');
$b3->setClassRank(1);
testBulletin($b3, "Invalid Range (2023/2025)");

// 4. Reversed range
$b4 = new Bulletin();
$b4->setAcademicYear('2025/2024');
$b4->setMention('Bien');
$b4->setClassRank(1);
testBulletin($b4, "Reversed Range (2025/2024)");

// 5. Future year
$b5 = new Bulletin();
$b5->setAcademicYear('2027/2028');
$b5->setMention('Bien');
$b5->setClassRank(1);
testBulletin($b5, "Future Year (2027/2028)");

// 6. Missing mandatory fields
$b6 = new Bulletin();
$b6->setAcademicYear('2025/2026');
// mention and rank are NULL by default
testBulletin($b6, "Missing Mention and Rank");
