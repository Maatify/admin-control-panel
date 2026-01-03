<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(Request $request, Response $response): Response
    {
        $stmt = $this->pdo->prepare("INSERT INTO admins (created_at) VALUES (NOW())");
        $stmt->execute();

        $id = (int)$this->pdo->lastInsertId();
        
        $stmt = $this->pdo->prepare("SELECT created_at FROM admins WHERE id = ?");
        $stmt->execute([$id]);
        $createdAt = $stmt->fetchColumn();

        $payload = json_encode([
            'admin_id' => $id,
            'created_at' => $createdAt
        ]);

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
