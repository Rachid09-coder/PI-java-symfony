<?php

namespace App\Service;

use App\Repository\UserRepository;
use App\Entity\User;

class FaceRecognitionService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Find a matching user by comparing Euclidean distance between descriptors.
     * Returns the matched User or null.
     *
     * @param array $descriptor Received descriptor (array of floats)
     * @param float $threshold distance threshold (default 0.6)
     * @return User|null
     */
    public function findMatchingUser(array $descriptor, float $threshold = 0.6): ?User
    {
        $users = $this->userRepository->createQueryBuilder('u')
            ->where('u.faceDescriptor IS NOT NULL')
            ->getQuery()
            ->getResult();

        foreach ($users as $user) {
            $stored = $user->getFaceDescriptor();
            if (!is_array($stored)) {
                continue;
            }

            if (count($stored) !== count($descriptor)) {
                continue;
            }

            $dist = $this->euclideanDistance($stored, $descriptor);
            if ($dist < $threshold) {
                return $user;
            }
        }

        return null;
    }

    private function euclideanDistance(array $a, array $b): float
    {
        $sum = 0.0;
        $n = count($a);
        for ($i = 0; $i < $n; $i++) {
            $diff = (float)$a[$i] - (float)$b[$i];
            $sum += $diff * $diff;
        }

        return sqrt($sum);
    }
}
