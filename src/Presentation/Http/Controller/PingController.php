<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class PingController extends AbstractController
{
    #[Route('/ping', name: 'ping', methods: ['GET'])]
    public function ping(): JsonResponse
    {
        return $this->json([
            'status'      => 'ok',
            'date'        => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
        ]);
    }
}
