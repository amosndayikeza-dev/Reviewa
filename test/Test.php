<?php

include(__DIR__ . "/../daophp/Database.php");
// ou selon votre structure réelle
class Test{

 private PDO $pdo;

public function __construct()
{
   $database = new Database();
   $this->pdo = $database->getConnection();
}   
 function RequetePrepare($table){
        $cles = $this->decouvrirLesCles($table);
        $colonnes = "(" . implode(",", $cles) . ")";
        $valeurs = "(" . implode(",",array_fill(0,count($cles),"?")) .")";
        return $colonnes . "values" . $valeurs;
    }

    function decouvrirLesCles($table){
        $valeurs = $this->pdo->query("SELECT * FROM" .$table) ->fetchAll(PDO::FETCH_ASSOC);
        $cles = array_keys($valeurs[0]);
        return array_slice($cles,1,-1);
    }

    function tableComplete($table){
        $valeurs = $this->pdo->query("SELECT * FROM " . $table)->fetchAll(PDO::FETCH_ASSOC);
        $cles = array_keys($valeurs[0]);
        $this->afficherTableau($cles,$valeurs);
    }

    function afficherTableau($cles,$valeurs){
        echo'<style>
        .mon-tableau{
            width:100%;
            border-collapse: collapse;
            font-family:Arial,sans-serif;
            margin:20px 0;
            box-shadow:0 2px 5px rgba(0,0,0,0.1);
        }

        .mon-tableau thead tr {
            background-color : #4caf50;
            color:white;
        }

        .mon-tableau th{
            padding:12px;
            text-align: left;
            border: 1px solid #ddd;
        }

        .mon-tableau td{
            padding:10px;
            border: 1px solid #ddd;
        }

        .mon-tableau tbody tr:nth-child(even){
            background-color: #f2f2f2;
        }
        
        .mon-tableau tbody tr:hover{
            background-color: #ddd;
            transition:0.2s;
        }
        </style>
        ';

        echo '<table class="mon-tableau">';
        echo '<thead>';
        echo '<tr>';
        foreach($cles as $cle){
            echo '<th>' . htmlspecialchars((string)$cle, ENT_QUOTES, 'UTF-8') . '</th>';
        }
        echo '</tr>';
        echo '</thead>';

        echo '<tbody>';
        foreach ($valeurs as $valeur){
            echo '<tr>';
            foreach ($valeur as $cellule){
                echo '<td>' . htmlspecialchars((string)$cellule, ENT_QUOTES, 'UTF-8') . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody>';

        echo '</table>';
    }

    function requeteInsertion($table,iterable $valeurs){
    $query = "INSERT INTO" . $table .$this->RequetePrepare($table);
    $stmt = $this->pdo->prepare($query);
    $stmt->execute($valeurs);
    }
}   

$test = new Test();
$test->tableComplete("sales");


?>