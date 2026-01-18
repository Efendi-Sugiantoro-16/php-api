<?php
// api/transactions/delete.php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Transaction;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    Response::error('Method not allowed', 405);
}

$userId = Auth::authenticate();
$data = Response::getJsonInput();

// Validate input
Response::validateRequiredFields($data, ['id']);

$transactionId = (int) $data['id'];

try {
    // Get transaction with goal relationship
    $transaction = Transaction::with('goal')
                              ->find($transactionId);
    
    if (!$transaction) {
        Response::error('Transaction not found', 404);
    }
    
    // Check if goal belongs to user
    if ($transaction->goal->user_id != $userId) {
        Response::error('Access denied', 403);
    }
    
    // Delete transaction
    $transaction->delete();
    
    // Update goal amount
    $transaction->goal->subtractAmount($transaction->amount);
    
    Response::success('Transaction deleted successfully');
    
} catch (Exception $e) {
    Response::error('Failed to delete transaction: ' . $e->getMessage(), 500);
}
?>
