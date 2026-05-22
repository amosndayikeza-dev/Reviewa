<?php
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('html_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// ============== une fonction pour gérer les erreurs fatales ==============

register_shutdown_function(function () {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur interne du serveur',
                'debug' => [
                    'type' => $error['type'],
                    'message' => $error['message'],
                    'file' => basename($error['file']),
                    'line' => $error['line'],
                ],
            ], JSON_UNESCAPED_UNICODE);
        }
    });
    

// ============== une fonction pour envoyer une réponse JSON ==============

function jsonResponse($payload, int $status = 200): void
    {
        http_response_code($status);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

// ============== une fonction pour lire le corps de la requête JSON ==============

function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return $_POST ?: [];
        }

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            jsonResponse([
                'success' => false,
                'message' => 'JSON invalide: ' . json_last_error_msg(),
            ], 400);
        }

        return $data;
    }

// ============== une fonction pour normaliser le nom de la ressource ==============

function normalizeResource(string $resource): string
    {
        return strtolower(trim(str_replace('-', '_', $resource)));
    }

// ============== une fonction pour charger un DAO ==============

function loadDao(string $className)
    {
        $path = __DIR__ . '/../daophp/' . $className . '.php';
        if (!is_file($path)) {
            jsonResponse([
                'success' => false,
                'message' => "DAO introuvable pour {$className}",
            ], 404);
        }

        require_once $path;

        if (!class_exists($className)) {
            jsonResponse([
                'success' => false,
                'message' => "Classe {$className} introuvable",
            ], 500);
        }

        return new $className();
    }

// ============== une fonction pour appeler une fonction de manière sécurisée ==============

function safeCall(callable $callback, $fallback = null)
        {
            try {
                return $callback();
            } catch (Throwable $e) {
                return $fallback;
            }
        }
//===================== verification de la méthode de la requête =====================

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    jsonResponse(['success' => true]);
}

try {
    require_once __DIR__ . '/../config/Database.php';
    require_once __DIR__ . '/../daophp/DAO.php';

    ini_set('display_errors', 0);
    ini_set('html_errors', 0);

    $pdo = new PDO(
        'mysql:host=' . HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        USERNAME,
        PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );

    $method = $_SERVER['REQUEST_METHOD'];
    $resource = normalizeResource($_GET['resource'] ?? '');
    $id = $_GET['id'] ?? null;
    $action = normalizeResource($_GET['action'] ?? '');

    $daoMap = [
        // ==================== UTILISATEURS & ACTEURS ====================
        'users'               => 'UserDAO',
        'employees'           => 'EmployeeDAO',
        'departements'            => 'DepartementDAO',
        'debts'         => 'DebtsDAO',
        'notifications'     => 'NotificationsDAO',
        
        // ==================== MÉDICAMENTS & STOCKS ====================
        'products'         => 'productsDAO',
        'salary_reports'   => 'Salary_reportsDAO',
        'sales'          => 'SalesDAO',
        
        // ==================== RENDEZ-VOUS & CONSULTATIONS ====================
        'sale_items'         => 'Sale_itemsDAO',
        'stock_movements'     => 'Stock_movementDAO',
       
    ];

    if ($resource === '' || $resource === 'health' || $id === 'health') {
        jsonResponse([
            'success' => true,
            'data' => [
                'status' => 'healthy',
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => AP_VERSION,
                'resources' => array_keys($daoMap),
            ],
        ]);
    }

    if ($id === 'test_connection' || $id === 'test-connection' || $action === 'test_connection') {
        $stmt = $pdo->query('SELECT 1 AS test');
        jsonResponse([
            'success' => true,
            'message' => 'Connexion à la base de données réussie',
            'database_status' => $stmt->fetchColumn() ? 'connected' : 'unknown',
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }

    if (!isset($daoMap[$resource])) {
        jsonResponse([
            'success' => false,
            'message' => 'Resource not found',
            'available_resources' => array_keys($daoMap),
        ], 404);
    }

    $dao = loadDao($daoMap[$resource]);

    switch ($resource) {
        case 'users':
            switch ($method) {
                case 'GET':
                    if($id != null){
                            jsonResponse([
                            'success' => true,
                            'data' => $dao->findById($id),
                        ]);
                    }else{
                        jsonResponse([
                        'success' => true,
                        'data' => $dao->findAll(),
                    ]);
                    }
        
                    break;
                //== la création d'un utilisateur  ======================
                case 'POST':
                    $data = readJsonBody();
                    $message = $dao->create($data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Utilisateur créé avec succès',
                        'jsonResponse' => $message
                    ]);     
                    break;
                    
                //== la modification d'un utilisateur  ======================
                case 'PUT':
                    $data = readJsonBody();
                    $message = $dao->update($id, $data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Utilisateur modifié avec succès',
                        'jsonResponse' => $message
                    ]); 

                    break;

                // ==== la modification partielle d'un utilisateur  ======================   
                case 'PATCH':
                    $data = readJsonBody();
                    $message = $dao->update($id, $data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Utilisateur modifié avec succès',
                        'jsonResponse' => $message
                    ]); 
                    break;
                    
                //== la suppression d'un utilisateur  ======================
                case 'DELETE':
                    $message = $dao->delete($id);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Utilisateur supprimé avec succès',
                        'jsonResponse' => $message
                    ]); 
                    break;
                    
                default:
                    jsonResponse([
                        'success' => false,
                        'data' => $dao->findAll(),
                    ], 405);
            }
            break;
        //================ fin du method pour les requêtes liées aux utilisateur ======================
        case 'employees':
            switch ($method) {
                case 'GET':
                    if($id != null){
                            jsonResponse([
                            'success' => true,
                            'data' => $dao->findById($id),
                        ]);
                    }else{
                        jsonResponse([
                            'success' => true,
                            'data' => $dao->findAll(),
                        ]);
                    }
                    
                    break;
                case 'POST':
                        $data = readJsonBody();
                        $message = $dao->create($data);
                        jsonResponse([
                            'success' => true,
                            'message' => 'Employé créé avec succès',
                            'jsonResponse' => $message
                        ]);
                    break;
                case 'PUT':
                    $data = readJsonBody();
                    $message = $dao->update($id, $data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Employé modifié avec succès',
                        'jsonResponse' => $message
                    ]);
                    
                    break;
                case 'PATCH':
                    $data = readJsonBody();
                    $message = $dao->update($id,$data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Employé modifié avec succès',
                        'jsonResponse' => $message
                    ]);
                    
                    break;
                case 'DELETE':
                    $message = $dao->delete($id);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Employé supprimé avec succès',
                        'jsonResponse' => $message
                    ]);
                    break;
                    
                default:
                    jsonResponse([
                        'success' => false,
                        'data' =>  $dao->findAll(),
                    ], 405);
            }
            //================ fin du method pour les requêtes liées aux employes ======================
            break;
            
        case 'debts':
            switch ($method) {
                case 'GET':
                    if($id != null){
                        jsonResponse([
                            'success' => true,
                            'data' => $dao->findById($id),
                        ]);
                    }else{
                        jsonResponse([
                            'success' => true,
                            'data' => $dao->findAll(),
                        ]);
                    }
                    
                    break;
                case 'POST':
                    $data = readJsonBody();
                    $message = $dao->create($data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Dette créé avec succès',
                        'jsonResponse' => $message
                    ]);
                    break;
                case 'PUT':
                    $data = readJsonBody();
                    $message = $dao->update($id, $data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Dette modifiée avec succès',
                        'jsonResponse' => $message
                    ]);
                    break;
                case 'PATCH':
                    $data = readJsonBody();
                    $message = $dao->update($id,$data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Dette modifiée avec succès',
                        'jsonResponse' => $message
                    ]);
                    break;
                case 'DELETE':
                    $message = $dao->delete($id);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Dette supprimée avec succès',
                        'jsonResponse' => $message
                    ]);
                    break;
                    
                default:
                    jsonResponse([
                        'success' => false,
                        'message' => 'Méthode non supportée pour les inscriptions',
                    ], 405);
            }

            //================ fin du method pour les requêtes liées aux inscriptions ======================
            break;
            
        case 'departements':
            switch ($method) {
                case 'GET':
                    if($id != null){
                        jsonResponse([
                            'success' => true,
                            'data' => $dao->findById($id),
                        ]);
                        }else{
                        jsonResponse([
                            'success' => true,
                            'data' => $dao->findAll(),
                        ]);
                        }
                    
                    break;
                //== la création d'un departement  ======================
                case 'POST':
                    $data = readJsonBody();
                    $message = $dao->create($data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Département créé avec succès',
                        'jsonResponse' => $message
                    ]);
                    break;
                    
                //== la modification d'un depratement  ======================
                case 'PUT':
                    $data = readJsonBody();
                    $message = $dao->update($id,$data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Département modifie avec succes',
                        'jsonResponse' => $message
                    ]);

                    break;

                // ==== la modification partielle d'un pharmacien  ======================   
                case 'PATCH':
                    $data = readJsonBody();
                    $message = $dao->update($id,$data);
                    jsonResponse([
                        'succes' => true,
                        'message' => 'Departement modifie avec succes',
                    ]);
                    
                    break;
                    
                //== la suppression d'un departement  ======================
                case 'DELETE':
                    $message = $dao->delete($id);
                    jsonResponse([
                        'usses' => true,
                        'message' => 'Departement supprime avec succes',
                        'jsonResponse' => $message
                    ]);

                    break;
                    
                default:
                    jsonResponse([
                        'success' => false,
                        'message' => 'Méthode non supportée pour les pharmaciens',
                    ], 405);  
                }
            break;

            // Gestion générique pour les autres ressources
        case 'notifications':
        
            switch ($method) {
                case 'GET':
                    if($id != null){
                        jsonResponse([
                            'success' => true,
                            'data' => $dao->findById($id),
                        ]);
                    }else{
                        jsonResponse([
                            'success' => true,
                            'data' => $dao->findAll(),
                        ]);
                    }
                    
                    break;
                    
                //== la création d'un notications  ======================
                case 'POST':
                    $data = readJsonBody();
                    $message = $dao->create($data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Element créé avec succès',
                        'jsonResponse' => $message
                    ]);
                   
                    break;
                    
                //== la modification d'un élément  ======================
                case 'PUT':
                    $data = readJsonBody();
                    $message = $dao->update($id,$data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Element modifié avec succès',
                    ]);

                    break;
                // ==== la modification partielle d'un élément  ======================
                case 'PATCH':
                    $data = readJsonBody();
                    $message = $dao->update($id,$data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Element modifié avec succès',
                    ]);
                    
                    break;
                    
                //== la suppression d'un élément  ======================
                case 'DELETE':
                    $message = $dao->delete($id);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Element supprimé avec succès',
                        'jsonResponse' => $message
                    ]);

                    break;
                    
                default:
                    jsonResponse([
                        'success' => false,
                        'message' => 'Méthode non supportée pour cette resource',
                    ], 405);
            }
            break;
        case 'products':
            switch ($method) {
                case 'GET':
                    if($id != null){
                        jsonResponse([
                            'success' => true,
                            'data' => $dao->findById($id),
                        ]);
                        }else{
                        jsonResponse([
                            'success' => true,
                            'data' => $dao->findAll(),
                        ]);
                        }
                    
                    break;
                //== la création d'un produit  ======================
                case 'POST':
                    $data = readJsonBody();
                    $message = $dao->create($data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Produit créé avec succès',
                        'jsonResponse' => $message
                    ]);
                    
                    break;
                    
                //== la modification d'un produit  ======================
                case 'PUT':
                    $data = readJsonBody();
                    $message = $dao->update($id,$data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Produit modifié avec succès',
                        'jsonResponse' => $message
                    ]);


                    break;

                // ==== la modification partielle d'un produit  ======================   
                case 'PATCH':
                    $data = readJsonBody();
                    $message = $dao->update($id,$data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Produit modifié avec succès',
                        'jsonResponse' => $message
                    ]);
                    break;
                    
                //== la suppression d'un produit  ======================
                case 'DELETE':
                    $message = $dao->delete($id);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Produit supprimé avec succès',
                        'jsonResponse' => $message
                    ]);

                    break;
                    
                default:
                    jsonResponse([
                        'success' => false,
                        'message' => 'Méthode non supportée pour les produits',
                    ], 405);
            }
             break;
        case 'salary_reports':
            switch ($method) {
                case 'GET':
                    if($id != null){
                        jsonResponse([
                            'success' => true,
                            'data' => $dao->findById($id),
                        ]);
                        }else{
                        jsonResponse([
                            'success' => true,
                            'data' => $dao->findAll(),
                        ]);
                        }
                    
                    break;
                //== la création d'un rapport de salaire  ======================
                case 'POST':
                    $data = readJsonBody();
                    $message = $dao->create($data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Rapport de salaire créé avec succès',
                        'jsonResponse' => $message
                    ]);
                    
                    break;
                    
                //== la modification d'un rapport de salaire  ======================
                case 'PUT':
                    $data = readJsonBody();
                    $message = $dao->update($id,$data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Rapport de salaire modifié avec succès',
                        'jsonResponse' => $message
                    ]);

                    break;

                // ==== la modification partielle d'un rapport de salaire  ======================   
                case 'PATCH':
                    $data = readJsonBody();
                    $message = $dao->update($id,$data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Rapport de salaire modifié avec succès',
                        'jsonResponse' => $message
                    ]);

                    break;
                    
                //== la suppression d'un stock  ======================
                case 'DELETE':
                    $message = $dao->delete($id);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Rapport de salaire supprimé avec succès',
                        'jsonResponse' => $message
                    ]);
                    
                    break;
                    
                default:
                    jsonResponse([
                        'success' => false,
                        'message' => 'Méthode non supportée pour les rapports des salaraire',
                    ], 405);
            }
             break;
        case 'sales':
            switch ($method) {
                case 'GET':
                    if($id != null){
                        jsonResponse([
                            'success' => true,
                            'data' => $dao->findById($id),
                        ]);
                        }else{
                        jsonResponse([
                            'success' => true,
                            'data' => $dao->findAll(),
                        ]);
                        }
                    
                    break;
                //== la création d'une vente  ======================
                case 'POST':
                    $data = readJsonBody();
                    $message = $dao->create($data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Vente créée avec succès',
                        'jsonResponse' => $message
                    ]);
                    
                    break;
                    
                //== la modification d'une vente  ======================
                case 'PUT':
                    $data = readJsonBody();
                    $message = $dao->update($id,$data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Vente modifiée avec succès',
                        'jsonResponse' => $message
                    ]);

                    break;

                // ==== la modification partielle d'une vente  ======================   
                case 'PATCH':
                    $data = readJsonBody();
                    $message = $dao->update($id,$data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Vente modifiée avec succès',
                        'jsonResponse' => $message
                    ]);
                    break;
                    
                //== la suppression d'une vente  ======================
                case 'DELETE':
                    $message = $dao->delete($id);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Vente supprimée avec succès',
                        'jsonResponse' => $message
                    ]);
                    
                    break;
                    
                default:
                    jsonResponse([
                        'success' => false,
                        'message' => 'Méthode non supportée pour les substituts',
                    ], 405);
            }
            break;

        case 'sale_items':
            switch ($method) {
                case 'GET':
                    if($id != null){
                        jsonResponse([
                            'success' => true,
                            'data' => $dao->findById($id),
                        ]);
                        }else{
                        jsonResponse([
                            'success' => true,
                            'data' => $dao->findAll(),
                        ]);
                        }
                    
                    
                    break;
                //== la création d'un item de vente  ======================
                case 'POST':
                    $data = readJsonBody();
                    $message = $dao->create($data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Item de vente créé avec succès',
                        'jsonResponse' => $message
                    ]);
                    
                    break;
                    
                //== la modification d'un item de vente  ======================
                case 'PUT':
                    $data = readJsonBody();
                    $message = $dao->update($id,$data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Item de vente modifié avec succès',
                        'jsonResponse' => $message
                    ]);

                    break;

                // ==== la modification partielle d'un item de vente  ======================   
                case 'PATCH':
                    $data = readJsonBody();
                    $message = $dao->update($id,$data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Item de vente modifié avec succès',
                        'jsonResponse' => $message
                    ]);
                    break;
                    
                //== la suppression d'un item de vente  ======================
                case 'DELETE':
                    $message = $dao->delete($id);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Item de vente supprimé avec succès',
                        'jsonResponse' => $message
                    ]);
                    
                    break;
                    
                default:
                    jsonResponse([
                        'success' => false,
                        'message' => 'Méthode non supportée pour les rendez-vous',
                    ], 405);
            }
            break;

        case 'stock_movements':
            switch ($method) {
                case 'GET':
                    if($id != null){
                        jsonResponse([
                            'success' => true,
                            'data' => $dao->findById($id),
                        ]);
                        }else{
                        jsonResponse([
                            'success' => true,
                            'data' => $dao->findAll(),
                        ]);
                        }
                    
                    break;
                //== la création d'un mouvement de stock  ======================
                case 'POST':
                    $data = readJsonBody();
                    $message = $dao->create($data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Mouvement de stock créé avec succès',
                        'jsonResponse' => $message
                    ]);
                    
                    break;
                    
                //== la modification d'un mouvement de stock  ======================
                case 'PUT':
                    $data = readJsonBody();
                    $message = $dao->update($id,$data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Mouvement de stock modifié avec succès',
                        'jsonResponse' => $message
                    ]);

                    break;

                // ==== la modification partielle d'un mouvement de stock  ======================   
                case 'PATCH':
                    $data = readJsonBody();
                    $message = $dao->update($id,$data);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Mouvement de stock modifié avec succès',
                        'jsonResponse' => $message
                    ]);
                    
                    break;
                    
                //== la suppression d'un mouvement de stock  ======================
                case 'DELETE':
                    $message = $dao->delete($id);
                    jsonResponse([
                        'success' => true,
                        'message' => 'Mouvement de stock supprimé avec succès',
                        'jsonResponse' => $message
                    ]);
                    
                    break;
                    
                default:
                    jsonResponse([
                        'success' => false,
                        'message' => 'Méthode non supportée pour les mouvements de stock',
                    ], 405);
            }
            break;

        /*case 'agendas':
            switch ($method) {
                case 'GET':
                    
                    break;
                //== la création d'un agenda  ======================
                case 'POST':
                    
                    break;
                    
                //== la modification d'un agenda  ======================
                case 'PUT':

                    break;

                // ==== la modification partielle d'un agenda  ======================   
                case 'PATCH':
                    
                    break;
                    
                //== la suppression d'un agenda  ======================
                case 'DELETE':
                    
                    break;
                    
                default:
                    jsonResponse([
                        'success' => false,
                        'message' => 'Méthode non supportée pour les agendas',
                    ], 405);
            }
            break;

        case 'ordonnances':
            switch ($method) {
                case 'GET':
                    
                    break;
                //== la création d'une ordonnance  ======================
                case 'POST':
                    
                    break;
                    
                //== la modification d'une ordonnance  ======================
                case 'PUT':

                    break;

                // ==== la modification partielle d'une ordonnance  ======================   
                case 'PATCH':
                    
                    break;
                    
                //== la suppression d'une ordonnance  ======================
                case 'DELETE':
                    
                    break;
                    
                default:
                    jsonResponse([
                        'success' => false,
                        'message' => 'Méthode non supportée pour les ordonnances',
                    ], 405);
            }
            break;

        case 'lignes_ordonnance':
            switch ($method) {
                case 'GET':
                    
                    break;
                //== la création d'une ligne d'ordonnance  ======================
                case 'POST':
                    
                    break;
                    
                //== la modification d'une ligne d'ordonnance  ======================
                case 'PUT':

                    break;

                // ==== la modification partielle d'une ligne d'ordonnance  ======================   
                case 'PATCH':
                    
                    break;
                    
                //== la suppression d'une ligne d'ordonnance  ======================
                case 'DELETE':
                    
                    break;
                    
                default:
                    jsonResponse([
                        'success' => false,
                        'message' => 'Méthode non supportée pour les lignes d\'ordonnance',
                    ], 405);
            }
            break;

        case 'statuts_preparation':
            switch ($method) {
                case 'GET':
                    
                    break;
                //== la création d'un statut de préparation  ======================
                case 'POST':
                    
                    break;
                    
                //== la modification d'un statut de préparation  ======================
                case 'PUT':

                    break;

                // ==== la modification partielle d'un statut de préparation  ======================   
                case 'PATCH':
                    
                    break;
                    
                //== la suppression d'un statut de préparation  ======================
                case 'DELETE':
                    
                    break;
                    
                default:
                    jsonResponse([
                        'success' => false,
                        'message' => 'Méthode non supportée pour les statuts de préparation',
                    ], 405);
            }
            break;

        case 'notifications':
            switch ($method) {
                case 'GET':
                    
                    break;
                //== la création d'une notification  ======================
                case 'POST':
                    
                    break;
                    
                //== la modification d'une notification  ======================
                case 'PUT':

                    break;

                // ==== la modification partielle d'une notification  ======================   
                case 'PATCH':
                    
                    break;
                    
                //== la suppression d'une notification  ======================
                case 'DELETE':
                    
                    break;
                    
                default:
                    jsonResponse([
                        'success' => false,
                        'message' => 'Méthode non supportée pour les notifications',
                    ], 405);
            }
            break;

        case 'litiges':
            switch ($method) {
                case 'GET':
                    
                    break;
                //== la création d'un litige  ======================
                case 'POST':
                    
                    break;
                    
                //== la modification d'un litige  ======================
                case 'PUT':

                    break;

                // ==== la modification partielle d'un litige  ======================   
                case 'PATCH':
                    
                    break;
                    
                //== la suppression d'un litige  ======================
                case 'DELETE':
                    
                    break;
                    
                default:
                    jsonResponse([
                        'success' => false,
                        'message' => 'Méthode non supportée pour les litiges',
                    ], 405);
            }
            break;

        case 'alertes_stock':
            switch ($method) {
                case 'GET':
                    
                    break;
                //== la création d'une alerte de stock  ======================
                case 'POST':
                    
                    break;
                    
                //== la modification d'une alerte de stock  ======================
                case 'PUT':

                    break;

                // ==== la modification partielle d'une alerte de stock  ======================   
                case 'PATCH':
                    
                    break;
                    
                //== la suppression d'une alerte de stock  ======================
                case 'DELETE':
                    
                    break;
                    
                default:
                    jsonResponse([
                        'success' => false,
                        'message' => 'Méthode non supportée pour les alertes de stock',
                    ], 405);
            }
            break;

        case 'gardes_pharmacies':
            switch ($method) {
                case 'GET':
                    
                    break;
                //== la création d'une garde de pharmacie  ======================
                case 'POST':
                    
                    break;
                    
                //== la modification d'une garde de pharmacie  ======================
                case 'PUT':

                    break;

                // ==== la modification partielle d'une garde de pharmacie  ======================   
                case 'PATCH':
                    
                    break;
                    
                //== la suppression d'une garde de pharmacie  ======================
                case 'DELETE':
                    
                    break;
                    
                default:
                    jsonResponse([
                        'success' => false,
                        'message' => 'Méthode non supportée pour les gardes de pharmacies',
                    ], 405);
            }
            break;

        case 'provinces':
            switch ($method) {
                case 'GET':
                    
                    break;
                //== la création d'une province  ======================
                case 'POST':
                    
                    break;
                    
                //== la modification d'une province  ======================
                case 'PUT':

                    break;

                // ==== la modification partielle d'une province  ======================   
                case 'PATCH':
                    
                    break;
                    
                //== la suppression d'une province  ======================
                case 'DELETE':
                    
                    break;
                    
                default:
                    jsonResponse([
                        'success' => false,
                        'message' => 'Méthode non supportée pour les provinces',
                    ], 405);
            }
            break;

        case 'communes':
            switch ($method) {
                case 'GET':
                    
                    break;
                //== la création d'une commune  ======================
                case 'POST':
                    
                    break;
                    
                //== la modification d'une commune  ======================
                case 'PUT':

                    break;

                // ==== la modification partielle d'une commune  ======================   
                case 'PATCH':
                    
                    break;
                    
                //== la suppression d'une commune  ======================
                case 'DELETE':
                    
                    break;
                    
                default:
                    jsonResponse([
                        'success' => false,
                        'message' => 'Méthode non supportée pour les communes',
                    ], 405);
            }
            break;

        case 'taux_change':
            switch ($method) {
                case 'GET':
                    
                    break;
                //== la création d'un taux de change  ======================
                case 'POST':
                    
                    break;
                    
                //== la modification d'un taux de change  ======================
                case 'PUT':

                    break;

                // ==== la modification partielle d'un taux de change  ======================   
                case 'PATCH':
                    
                    break;
                    
                //== la suppression d'un taux de change  ======================
                case 'DELETE':
                    
                    break;
                    
                default:
                    jsonResponse([
                        'success' => false,
                        'message' => 'Méthode non supportée pour les taux de change',
                    ], 405);
            }
            break;

        case 'recherches_patients':
            switch ($method) {
                case 'GET':
                    
                    break;
                //== la création d'une recherche de patient  ======================
                case 'POST':
                    
                    break;
                    
                //== la modification d'une recherche de patient  ======================
                case 'PUT':

                    break;

                // ==== la modification partielle d'une recherche de patient  ======================   
                case 'PATCH':
                    
                    break;
                    
                //== la suppression d'une recherche de patient  ======================
                case 'DELETE':
                    
                    break;
                    
                default:
                    jsonResponse([
                        'success' => false,
                        'message' => 'Méthode non supportée pour les recherches de patients',
                    ], 405);
            }
            break;*/
        //================ fin du method pour les requêtes liées aux patients ======================
    }
    //================ fin du switch pour les ressources ======================

    jsonResponse([
        'success' => false,
        'message' => 'Méthode ou route non supportée',
    ], 405);

    
} catch (Throwable $e) {
    jsonResponse([
        'success' => false,
        'message' => $e->getMessage(),
    ], 500);
}

?>