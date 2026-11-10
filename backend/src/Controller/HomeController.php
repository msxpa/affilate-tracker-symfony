<?php
#backend/src/Controller/HomeController.php
declare(strict_types=1);
namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController
{
    #[Route('/', name: 'api_home', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'name' => 'Affiliate Tracker API',
            'status' => 'running',
            'version' => '1.0.0',
            'Worker PID' => getmypid()
        ]);
    }
}