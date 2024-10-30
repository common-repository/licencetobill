<?php
/* Utilisation de l'API Licence To Bill en PHP.
 *
 * La fonction Licence_To_Bill() devrait pouvoir s'adapter à n'importe quel environnement.
 * Les appels se font en JSON, mais la fonction d'appel retourne un objet PHP.
 *
 * La classe LicenceToBill a ses limites, et ne prétend absolument pas être pertinente
 * dans toutes les situations, mais seulement montrer une façon de faire.
 */



/*
 * La fonction magique qui fait des appels vers LicenceToBill, en JSON.
 * Paramètres :
 * $ressource : La ressource complète à aller chercher, par exemple "v2/users/{$key_user}"
 * [$post] : Pour une requête en écriture, tableau associatif des données à envoyer
 *           Par exemple : array('key_user'=>$key_user, 'name_user'=>$name, 'lcid'=>12)
 * Valeur de retour : le résultat renvoyé par LTB, décodé sous forme d'objet PHP
 *
 * Exemple :
 * $key=1;
 * $name='root';
 * $lcid=12;
 * $result=LicenceToBill("v2/users/$key", array('key_user'=>$key, 'name_user'=>$name, 'lcid'=>$lcid));
 * result : stdClass Object
(
    [key_user]         => 1
    [lcid]             => 12
    [name_user]        => root
    [url_choose_offer] => https://....licencetobill.com/...
    [url_deals]        => https://....licencetobill.com/...
    [url_invoices]     => https://....licencetobill.com/...
)
 */
function Licence_To_Bill($ressource, $post=NULL) {
  $server  ='api.licencetobill.com'; // ou test-api.licencetobill.com
  $business= get_option('LTB_setting_business_key');
  $agent   = get_option('LTB_setting_agent_key');
  $auth    ='Authorization: Basic '.base64_encode("$business:$agent");
  $url     ="https://$server/$ressource.json";
  $headers=array();
  $headers[]='Content-Type: application/json; charset=utf-8';
  $headers[]='Accept: application/json';
  $headers[]=$auth;

  $ch=curl_init();
  //if(!$ch)
  //	add_option( 'debug_seb_10c', 'curl_init failed', '', 'yes' );
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_TIMEOUT, 60);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  if($post) {
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
  }
  $rr=curl_exec($ch);
  /* if(!$rr) add_option( 'debug_seb_10b', 'curl_exec failed - error : '.curl_error($ch).' - '.curl_errno($ch), '', 'yes' ); */
  $r=str_replace(array(chr(239), chr(187), chr(191)), '', $rr);
  $i=curl_getinfo($ch);
  curl_close($ch);
  //var_dump($rr);
  return json_decode($r);
}

/*
 * Une classe pour simplifier les appels à Licence To Bill.
 */
class LicenceToBill {
  private $lcid=array( // A peupler selon le besoin, ou trouver une meilleure solution.
	'en'=>9,
	'es'=>10,
	'fr'=>12,
	'pt'=>22,
	'fr-fr'=>1036,
  );
  //private $free_key_offer ='67E4991D-38CA-4E08-91FC-C79894045D9C'; // ID du deal gratuit à ajouter par défaut.
  private $free_key_offer =''; // ID du deal gratuit à ajouter par défaut.
  private $keyfeatures=array( // Features définies dans le backoffice LTB
	//'f1' =>'c538f86e-82a6-4d51-bab9-cc2447cdbdc0', // key_feature du type 43F007BB-A8DE-487D-CC47-42FD55F452D2
	//'f2' =>'1b6b5370-aba6-4a5e-8648-0e0e344d8c18',
	'f1' =>'key1',
	'f2' =>'key2',
	'f3' =>'key3',
	'f4' =>'key4' // , etc.
  );
  /* LicenceToBill(uid)             : Jouer avec un utilisateur LTB.
   * LicenceToBill(uid, name, lcid) : Créer un utilisateur LTB s'il n'existe pas.
   * LicenceToBill()                : Faire des requêtes anonymes
   */
  function __construct($key_user=NULL, $name_user=NULL, $lcid=NULL, $dealgratuit=False) {

	if(!$key_user) return;
	if($dealgratuit) {
	  $f=$this->trial($key_user, $name_user, $lcid);
	  // L'appel trial crée un utilisateur, mais ne retourne pas ses infos !
	  // Il retourne la liste de ses features par contre, donc on peut les renseigner immédiatement.
	  $this->features_info($f);
	  $u=$this->users($key_user);
	} else
	  $u=$this->users($key_user, $name_user, $lcid);
	// On peuple l'objet des informations de l'utilisateur LTB
	foreach(get_object_vars($u) as $k=>$v) $this->$k=$v;
	// Sont fixés : key_user, lcid, name_user, url_choose_offer, url_deals, url_invoices
  }
  function __get($name) {
	if(isset($this->key_user)) {
	  if(isset($this->keyfeatures[$name])) {
		$this->features_info();
		return isset($this->$name)?$this->$name:False;
	  }
	}
	$trace=debug_backtrace();
	trigger_error("Undefined property via __get(): $name in {$trace[0]['file']} on line {$trace[0]['line']}", E_USER_NOTICE);
	return null;
  }
  function lcid($lcid, $defaut=12) {
	if(!$lcid) return NULL;
	return isset($this->lcid[$lcid])?$this->lcid[$lcid]:(is_numeric($lcid)?intval($lcid):$defaut);
  }
  /**************************
   * Fonctions « anonymes » *
   **************************/
  /* Sert à lister les utilisateur, les récupérer, créer et modifier.
   * users()                 : liste les utilisateurs
   * users(uid)              : récupère un utilisateur
   * users(uid, nom[, lcid]) : crée ou modifie un utilisateur
   * users(uid, NULL, lcid)  : modifie spécifiquement la langue d'un utilisateur.
   * lcid peut valoir le numéro ou le code (12 ou 'fr' par exemple).
   */
  function users($key_user='', $name_user=NULL, $lcid=NULL) {
	$url='v2/users'.($key_user?'/'.$key_user:'');
	if($name_user || $lcid) {
	  $post=array('key_user'=>$key_user);
	  if($name_user) $post['name_user']=$name_user;
	  if($lcid)      $post['lcid']=$this->lcid($lcid);
	} else $post=NULL;
	return Licence_To_Bill($url, $post);
  }
  /* Crée un utilisateur et lui met le deal gratuit.
   */
  function trial($key_user, $name_user=NULL, $lcid=NULL) {
  	$this->free_key_offer = get_option('LTB_setting_trial');
	$post=array('key_user'=>$key_user, 'key_offer'=>$this->free_key_offer);
	if($name_user) $post['name_user']=$name_user;
	if($lcid)      $post['lcid']=$this->lcid($lcid);
	return Licence_To_Bill('v2/trial/'.$key_user, $post);
  }

  function address($key_user, $company=NULL, $fullname=NULL, $address_line1=NULL, $address_line2=NULL, $zipcode=NULL, $country=NULL, $country_iso2=NULL, $country_iso3=NULL, $vat_information=NULL) {
  	$post=array();
  	if($company) $post['company']=$company;
  	if($fullname) $post['fullname']=$fullname;
  	if($address_line1) $post['address_line1']=$address_line1;
  	if($address_line2) $post['address_line2']=$address_line2;
  	if($zipcode) $post['zipcode']=$zipcode;
  	if($country) $post['country']=$country;
  	if($country_iso2) $post['country_iso2']=$country_iso2;
  	if($country_iso3) $post['country_iso3']=$country_iso3;
  	if($vat_information) $post['vat_information']=$vat_information;
  	return Licence_To_Bill('v2/address/users/'.$key_user, $post);
  }
  /* deal(uid)    : Liste les deals avec un utilisateur
   */
  function deal($key_user) {
	return Licence_To_Bill('v2/deals/users/'.$key_user);
  }
  /* features([lcid])        : liste toutes les features.
   * features(uid)           : liste les features accessible à un utilisateur.
   * features(uid, feature)  : renvoie les infos de la feature pour l'utilisateur donné.
   * features(NULL, feature) : liste des utilisateurs ayant la feature donnée.
   */
  function features($key_user='', $key_feature='', $lcid='') {
	if($key_user) $url='/v2/features/'.($key_feature?$key_feature.'/':'').'users/'.$key_user;
	elseif($key_feature) $url='/v2/users/features/'.$key_feature;
	else $url='/v2/features/'.$this->lcid($lcid);
	return Licence_To_Bill($url);
  }
  /* offers([lcid]) : liste des offres disponibles.
   * offers(uid)    : liste des offres avec URLs spécifiques pour l'utilisateur donné.
   */
  function offers($key_user='', $lcid='') {
	if($key_user) $url='/v2/offers/users/'.$key_user;
	else $url='/v2/offers/'.$lcid;
	return Licence_To_Bill($url);
  }
  /*************************************
   * Fonctions pour utilisateur chargé *
   *************************************/
  // On récupère le deal de l'utilisateur
  // Si setFree, et pas de deal existant, on crée un deal gratuit
  function deal_info($setFree=False) {
	if(isset($this->_deal)) return $this->_deal;
	$d=$this->deal($this->key_user);
	// !$d si aucun deal n'existe pour l'utilisateur.
	if(!$d && $setFree) $d=$this->trial($this->key_user);
	$this->_deal=is_array($d)?$d[0]:$d;
  }
  /* Récupère les limites des features avec un nom exploitable dans le code PHP.
   * On peut fournir la liste des features sous forme de résultat d'appel LTB,
   * par exemple après un appel à trial qui retourne la liste des features.
   * Sinon l'appel à features est effectué.
   *
   * Selon ce qui est défini dans le tableau $this->keyfeatures,
   * après cet appel on a par exemple :
   * $this->f1=True;
   * $this->f2=False;
   * $this->f3=100;
   * $this->f4=2;
   * Toutes les features du tableau $this->keyfeatures sont définies, même si en pratique elles n'existent
   * pas sur LTB ou ne sont pas référencées dans la réponse. Leur valeur sera alors False.
   *
   * Les limitations sont aussi attribuées aux variables $this->{$key_feature}, mais c'est peu exploitable dans le code.
   *
   * Grâce à la fonction magique __get, demander directement $this->f1 si cet appel n'a pas été effectué
   * permet de le faire et de peupler toutes les valeurs des limitations.
   */
  function features_info($features=NULL) {
	if(!$features) $features=$this->features($this->key_user);
	// On s'assure d'avoir toujours un tableau de features, même s'il n'y en a qu'une seule.
	if($features && !is_array($features) && isset($features->key_feature)) $features=array($features);
	foreach($this->keyfeatures as $name=>$key) $this->$name=False;
	foreach($features as $f) {
	  $this->{$f->key_feature}=isset($f->limitation)?$f->limitation:True;
	  if($name=array_search($f->key_feature, $this->keyfeatures))
		$this->$name=$this->{$f->key_feature};
	}
  }
  // Modifie ou crée l'utilisateur
  function save($name_user=NULL, $lcid=NULL) {
	$u=$this->users($this->key_user, $name_user, $lcid);
	foreach(get_object_vars($u) as $k=>$v) $this->$k=$v;
  }
}

?>