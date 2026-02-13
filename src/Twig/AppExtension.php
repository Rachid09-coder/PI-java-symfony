<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('file_to_base64', [$this, 'fileToBase64']),
        ];
    }

    public function fileToBase64(string $path): string
    {
        if (!file_exists($path)) {
            return '';
        }

        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        
        return base64_encode($data);
    }
}
