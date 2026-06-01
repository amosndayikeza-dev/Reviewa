<?php
require_once __DIR__ . '/../bootstrap.php';

try {
    $ctx = mgrAuthContext();
    $service = $ctx['service'];
    $departementId = $ctx['departementId'];
    $id = mgrPathId();

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if ($id !== null) {
                $product = $service->getProduct($id);
                if (!$product || (int)$product['departement_id'] !== $departementId) {
                    mgrRespond(404, ['success' => false, 'message' => 'Produit introuvable']);
                }
                mgrRespond(200, ['success' => true, 'data' => $product]);
            }
            $products = $service->getProductsByDepartement($departementId);
            foreach ($products as &$p) {
                $p['stock_status'] = ManagerService::stockStatusLabel($p);
            }
            unset($p);
            mgrRespond(200, ['success' => true, 'data' => $products]);
            break;

        case 'POST':
            $data = mgrReadJson();
            $data['departementId'] = $departementId;
            $data['createdBy'] = $ctx['userId'];
            $newId = $service->createProduct($data);
            mgrRespond(201, ['success' => true, 'message' => 'Produit créé', 'id' => $newId]);
            break;

        case 'PUT':
        case 'PATCH':
            if ($id === null) {
                mgrRespond(400, ['success' => false, 'message' => 'ID requis']);
            }
            $product = $service->getProduct($id);
            if (!$product || (int)$product['departement_id'] !== $departementId) {
                mgrRespond(404, ['success' => false, 'message' => 'Produit introuvable']);
            }
            $service->updateProduct($id, mgrReadJson());
            mgrRespond(200, ['success' => true, 'message' => 'Produit mis à jour']);
            break;

        case 'DELETE':
            if ($id === null) {
                mgrRespond(400, ['success' => false, 'message' => 'ID requis']);
            }
            $product = $service->getProduct($id);
            if (!$product || (int)$product['departement_id'] !== $departementId) {
                mgrRespond(404, ['success' => false, 'message' => 'Produit introuvable']);
            }
            $service->deleteProduct($id);
            mgrRespond(200, ['success' => true, 'message' => 'Produit supprimé']);
            break;

        default:
            mgrRespond(405, ['success' => false, 'message' => 'Méthode non autorisée']);
    }
} catch (InvalidArgumentException $e) {
    mgrRespond(400, ['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    mgrRespond(500, ['success' => false, 'message' => $e->getMessage()]);
}
