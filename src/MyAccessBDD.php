<?php
include_once("AccessBDD.php");

/**
 * Classe de construction des requêtes SQL
 * hérite de AccessBDD qui contient les requêtes de base
 * Pour ajouter une requête :
 * - créer la fonction qui crée une requête (prendre modèle sur les fonctions 
 *   existantes qui ne commencent pas par 'traitement')
 * - ajouter un 'case' dans un des switch des fonctions redéfinies 
 * - appeler la nouvelle fonction dans ce 'case'
 */
class MyAccessBDD extends AccessBDD {
	    
    /**
     * constructeur qui appelle celui de la classe mère
     */
    public function __construct(){
        try{
            parent::__construct();
        }catch(\Exception $e){
            throw $e;
        }
    }

    /**
     * demande de recherche
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return array|null tuples du résultat de la requête ou null si erreur
     * @override
     */	
    protected function traitementSelect(string $table, ?array $champs) : ?array{
        switch($table){  
            case "detailcommande" :
                return $this->selectDetailCommande($champs);
            case "commandedocument" :
                return $this->selectAllCommandedocuments();
            case "suivi":
                return $this->selectAllSuivi();
            case "commande": 
                return $this->selectAllCommandes();
            case "livre" :
                return $this->selectAllLivres();
            case "dvd" :
                return $this->selectAllDvd();
            case "revue" :
                return $this->selectAllRevues();
            case "exemplaire" :
                return $this->selectExemplairesRevue($champs);
            case "genre" :
            case "public" :
            case "rayon" :
            case "etat" :
                // select portant sur une table contenant juste id et libelle
                return $this->selectTableSimple($table);
            case "exemplaire" :
                return $this->selectExemplairesRevue($champs['id']);
            case "detailcommande" :
                return $this->selectDetailCommande($champs['idLivreDvd']);
            case "" :
                // return $this->uneFonction(parametres);
            default:
                // cas général
                return $this->selectTuplesOneTable($table, $champs);
        }	
    }

    /**
     * demande d'ajout (insert)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples ajoutés ou null si erreur
     * @override
     */	
    protected function traitementInsert(string $table, ?array $champs) : ?int{
        switch($table){ 
            case "detailcommande":
                return $this->insertDetailCommande($champs);
            case "" :
                // return $this->uneFonction(parametres);
            default:                    
                // cas général
                return $this->insertOneTupleOneTable($table, $champs);	
        }
    }
    
    /**
     * demande de modification (update)
     * @param string $table
     * @param string|null $id
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples modifiés ou null si erreur
     * @override
     */	
    protected function traitementUpdate(string $table, ?string $id, ?array $champs) : ?int{
        switch($table){
            case "" :
                // return $this->uneFonction(parametres);
            default:                    
                // cas général
                return $this->updateOneTupleOneTable($table, $id, $champs);
        }	
    }  
    
    /**
     * demande de suppression (delete)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples supprimés ou null si erreur
     * @override
     */	
    protected function traitementDelete(string $table, ?array $champs) : ?int{
        switch($table){
            case "" :
                // return $this->uneFonction(parametres);
            default:                    
                // cas général
                return $this->deleteTuplesOneTable($table, $champs);	
        }
    }	    
        
    /**
     * récupère les tuples d'une seule table
     * @param string $table
     * @param array|null $champs
     * @return array|null 
     */
    private function selectTuplesOneTable(string $table, ?array $champs) : ?array{
        if(empty($champs)){
            // tous les tuples d'une table
            $requete = "select * from $table;";
            return $this->conn->queryBDD($requete);  
        }else{
            // tuples spécifiques d'une table
            $requete = "select * from $table where ";
            foreach ($champs as $key => $value){
                $requete .= "$key=:$key and ";
            }
            // (enlève le dernier and)
            $requete = substr($requete, 0, strlen($requete)-5);	 
            return $this->conn->queryBDD($requete, $champs);
        }
    }	

    /**
     * demande d'ajout (insert) d'un tuple dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples ajoutés (0 ou 1) ou null si erreur
     */	
    private function insertOneTupleOneTable(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        // construction de la requête
        $requete = "insert into $table (";
        foreach ($champs as $key => $value){
            $requete .= "$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ") values (";
        foreach ($champs as $key => $value){
            $requete .= ":$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ");";
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * demande de modification (update) d'un tuple dans une table
     * @param string $table
     * @param string\null $id
     * @param array|null $champs 
     * @return int|null nombre de tuples modifiés (0 ou 1) ou null si erreur
     */	
    private function updateOneTupleOneTable(string $table, ?string $id, ?array $champs) : ?int {
        if(empty($champs)){
            return null;
        }
        if(is_null($id)){
            return null;
        }
        // construction de la requête
        $requete = "update $table set ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);				
        $champs["id"] = $id;
        $requete .= " where id=:id;";		
        return $this->conn->updateBDD($requete, $champs);	        
    }
    
    /**
     * demande de suppression (delete) d'un ou plusieurs tuples dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples supprimés ou null si erreur
     */
    private function deleteTuplesOneTable(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        // construction de la requête
        $requete = "delete from $table where ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key and ";
        }
        // (enlève le dernier and)
        $requete = substr($requete, 0, strlen($requete)-5);   
        return $this->conn->updateBDD($requete, $champs);	        
    }
 
    /**
     * récupère toutes les lignes d'une table simple (qui contient juste id et libelle)
     * @param string $table
     * @return array|null
     */
    private function selectTableSimple(string $table) : ?array{
        $requete = "select * from $table order by libelle;";		
        return $this->conn->queryBDD($requete);	    
    }
    
    /**
     * récupère toutes les lignes de la table Livre et les tables associées
     * @return array|null
     */
    private function selectAllLivres() : ?array{
        $requete = "Select l.id, l.ISBN, l.auteur, d.titre, d.image, l.collection, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from livre l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";		
        return $this->conn->queryBDD($requete);
    }	

    /**
     * récupère toutes les lignes de la table DVD et les tables associées
     * @return array|null
     */
    private function selectAllDvd() : ?array{
        $requete = "Select l.id, l.duree, l.realisateur, d.titre, d.image, l.synopsis, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from dvd l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";	
        return $this->conn->queryBDD($requete);
    }	

    /**
     * récupère toutes les lignes de la table Revue et les tables associées
     * @return array|null
     */
    private function selectAllRevues() : ?array{
        $requete = "Select l.id, l.periodicite, d.titre, d.image, l.delaiMiseADispo, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from revue l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }	

    /**
     * récupère tous les exemplaires d'une revue
     * @param array\null $champs 
     * @return array|null
     */
    private function selectExemplairesRevue(?array $champs) : ?array{
        if(empty($champs)){
            return null;
        }
        if(!array_key_exists('id', $champs)){
            return null;
        }
        $champNecessaire['id'] = $champs['id'];
        $requete = "Select e.id, e.numero, e.dateAchat, e.photo, e.idEtat ";
        $requete .= "from exemplaire e join document d on e.id=d.id ";
        $requete .= "where e.id = :id ";
        $requete .= "order by e.dateAchat DESC";		
        return $this->conn->queryBDD($requete, $champNecessaire);
    }	
    
    /**
    * Récupère toutes les commandes de livres
    * @return array|null
    */
    private function selectAllCommandes() : ?array {
        $requete = " select c.id, c.dateCommande, c.montant ";
        $requete .= "from commande c ";
        $requete .= "order by c.dateCommande desc";
    return $this->conn->queryBDD($requete);
    }
    
    private function selectAllCommandedocuments() : ?array {
        $requete = "select cd.id, cd.nbExemplaire, cd.idLivreDvd ";
        $requete .= "from commandedocument as cd ";
        $requete .= "join commande AS c ON cd.id = c.id ";
        $requete .= "order by cd.id desc ";
        
        var_dump($requete);
        return $this->conn->queryBDD($requete);
    }
    
   private function selectAllSuivi() : ?array {
    $requete = "SELECT s.id, s.idCommandeDocument, s.etape ";
    $requete .= "FROM suivi as s ";
    $requete .= "join commandedocument as cd on s.idCommandeDocument = cd.id ";
    $requete .= "ORDER BY s.id DESC ";
    return $this->conn->queryBDD($requete);
    }

    
    private function selectDetailCommande (?array $champs) : ?array{  
        if(empty($champs)){
            return null;
        }
        if(!array_key_exists('idLivreDvd', $champs)){
            return null;
        }
        $champsNecessaire['idLivreDvd'] = $champs['idLivreDvd'];
        $requete = "SELECT c.id AS idCommande, cd.id AS idCommandeDocument, c.dateCommande AS dateCommande, c.montant as montant, cd.nbExemplaire as nbExemplaire, cd.idLivreDvd as idLivreDvd, s.etape AS etape ";
        $requete .= "from commandedocument cd ";
        $requete .= "join commande c on cd.id = c.id ";
        $requete .= "left join suivi s on cd.id = s.idCommandeDocument ";
        $requete .= "where cd.idLivreDvd = :idLivreDvd ";
        $requete .= "order by c.dateCommande desc ";
        
        return $this->conn->queryBDD($requete, $champsNecessaire);
    }
    
    public function insertDetailCommande(?bool $champs): ?bool {
    
    $champs = json_decode(file_get_contents("php://input"), true);
    if ($champs === null || 
        !isset($champs['IdCommande']) || 
        !isset($champs['IdCommandeDocument']) || 
        !isset($champs['DateCommande']) || 
        !isset($champs['Montant']) || 
        !isset($champs['NbExemplaire']) || 
        !isset($champs['IdLivreDvd']) || 
        !isset($champs['Etape'])) {

       
        return false;
    }

    $requeteCommande = "INSERT INTO commande (id, dateCommande, montant) VALUES (:id, :dateCommande, :montant)";
    $paramsCommande = [
        'id' => $champs['IdCommande'],  
        'dateCommande' => $champs['DateCommande'],
        'montant' => $champs['Montant']
    ];
    $resultCommande = $this->conn->updateBDD($requeteCommande, $paramsCommande);
   

    
    $requeteCommandeDocument = "INSERT INTO commandedocument (id, nbExemplaire, idLivreDvd) VALUES (:id, :nbExemplaire, :idLivreDvd)";
    $paramsCommandeDocument = [
        'id' => $champs['IdCommandeDocument'],  
        'nbExemplaire' => $champs['NbExemplaire'],
        'idLivreDvd' => $champs['IdLivreDvd']
    ];
    if ($champs['IdCommandeDocument'] === $champs['IdCommande']) {
        $paramsCommandeDocument['id'] = $champs['IdCommandeDocument'];  
    } else {
    return false;
    }
    $resultCommandeDocument = $this->conn->updateBDD($requeteCommandeDocument, $paramsCommandeDocument);
    

    $requeteSuivi = "INSERT INTO suivi (idCommandeDocument, etape) VALUES (:idCommandeDocument, :etape)";
    $paramsSuivi = [
        'idCommandeDocument' => $champs['IdCommandeDocument'], 
        'etape' => 'En cours'
    ];
    $resultSuivi = $this->conn->updateBDD($requeteSuivi, $paramsSuivi);
    

    return ($resultCommande === 1 && $resultCommandeDocument === 1 && $resultSuivi === 1);
}
    
}
    
    

