<?php
 /*
 *@file 
 * Contains \Drupal\napay\Controller\NotifyController
 */

  namespace Drupal\napay\Controller;
  use Drupal\Core\Controller\ControllerBase;
  use Symfony\Component\HttpFoundation\Request;
  use Psr\Log\LoggerInterface;
  use Symfony\Component\DependencyInjection\ContainerInterface;
  use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
  use Symfony\Component\HttpFoundation\Response;
  use Drupal\node\Entity\Node; 
  use Symfony\Component\HttpFoundation\BinaryFileResponse;
  
  class NotifyController extends ControllerBase implements ContainerInjectionInterface {
	  
   /**
   * The http request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
	  
  protected $request;
	  
   /**
   * The http response.
   *
   * @var \Symfony\Component\HttpFoundation\Response
   */
	  
  protected $response;  
	  
  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  
  protected $logger;
   
  
  /**
   * Constructs a new NotifyController object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */

   
  public function __construct(LoggerInterface $logger) {
  
    $this->logger = $logger;
    }  

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.channel.default')
    );
    }
  
  /**
   * notify() Cette fonction est la juste for debugging purpose
   */   
  protected function notify(){
    $response = new Response(json_encode(['id' => '1']));
    $request = Request::createFromGlobals();
    if ($request->isMethod('POST'))
    {
		$this->logger->info('dans le bloc informations');
    }
    $response->headers->set('Content-Type', 'application/json');
    return $response;
    }
	
  /**
   * order() for testing purpose
   * Cette fonction envois une requête post
   * comme devrait le faire le mobile gateway
   */   
   
  public function order(){
	$this->logger->info('Creation de la commande');
	$client = \Drupal::httpClient();
	$request = Request::createFromGlobals();
	$request = $client->post('http://nasande.cg/process', [
	'json' => [
	'numero'=> '064781414'
	]
	]);
    $response = json_decode($request->getBody());
	return array(
		  '#type' => 'markup',
		  '#markup' => t('This is the order'),
		  );
		  
	}
	  
    public function fichier(){
        
    $request = Request::createFromGlobals();
	if ($request->isMethod('POST'))
	{
		$this->logger->info('dans le bloc de creation de fichier');
		$numero = $request->request->get('numero');
		$title = $request->request->get('titre');
		$id = $request->request->get('id');
		$userid = $request->request->get('userId');
	
		
		// Create node object with attached file.
		$node = Node::create([
		  'type'        => 'fichier',
		  'title'       => $title,
		  'field_id_fichier' => $id,
		  'field_numero' => $numero,
		  'field_id_user' => $userid,


		]);
		$node->save();
		
		$response = new Response(json_encode(['success' => 1]));
		$response->headers->set('Content-Type', 'application/json');
		
		return $response;
    }
    }
    
	
  /**
   *  process() Cette fonction reçois la requête envoyé par le mobile gateway
   *  pour créer l'utilisateur ou encore la commande
   */
   
  public function process(){
	$request = Request::createFromGlobals();
	if ($request->isMethod('POST'))
	{
		$this->logger->info('dans le bloc nouvelle formile ');
		$numero = $request->request->get('numero');
		$body = $request->request->get('body');
		$montant = $request->request->get('montant');
		$userid = $request->request->get('user_id');
		
		// Create node object with attached file.
		$node = Node::create([
		  'type'        => 'notification',
		  'title'       => $montant." de ".$numero,
		  'body'        => $body,
		  'field_amount' => $montant,
		  'field_telephone_number' => $numero,
		  'field_user_id' => $userid,

		]);
		$node->save();
		
		
		$montant = $request->request->get('montant');
		$this->logger->info('dans le bloc got '.$numero);
		if(is_bool(user_load_by_name($numero))){
			// Si l'utilisateur n'existe pas le créer et renvoyer son id et le mot de passe au mobile gateway
			return $this->createUser($numero);

			// $this->createOrder($user->id(),$montant); 
		}
		else {
			// Si l'utilisateur existe 
			$this->logger->info('des nullos');
			$user = user_load_by_name($numero);
			$response = new Response(json_encode(['id' => "".$user->id(),'numero' => $numero ,'success' => 2])) ;
			// $this->createOrder($user->id(),$montant);
			$response->headers->set('Content-Type', 'application/json');
			return $response;
			
		}
	}
        else {
           		$response = new Response(json_encode(['success' => 3])) ;
			// $this->createOrder($user->id(),$montant);
			$response->headers->set('Content-Type', 'application/json');
			return $response; 
        }
	}
  /*
   * @function createUser() Crée un utilisateur
   */   
  protected function createUser($numero){
	$language = \Drupal::languageManager()->getCurrentLanguage()->getId();
	$user = \Drupal\user\Entity\User::create();
	$password = user_password(7); 
	//Mandatory settings
	$user->setPassword($password);
	$user->enforceIsNew();
	$user->setEmail('');
	$user->setUsername($numero); //This username must be unique and accept only a-Z,0-9, - _ @ .
	//Optional settings
	$user->set("init", 'username');
	$user->set("langcode", $language);
	$user->set("preferred_langcode", $language);
	$user->set("preferred_admin_langcode", $language);
	//$user->set("setting_name", 'setting_value');
	$user->activate();
	//Save user
	$res = $user->save();
	if($res){
		$this->logger->info('utilisateur créer');
		$response =  new Response(json_encode(['id' => $user->id(),'pass' => $password,'numero' => $numero,'success' => 1]));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
			
	}
	else return null;
    }


  /**
   * @function download() 
   *
   */
  public function download() {
      
        $fid = \Drupal::request()->query->get('q');
        $this->logger->info("id fichier " + $fid);
      
        $file =  \Drupal\file\Entity\File::load($fid);
        
        $url_fichier = $file->getFileUri();
        
        $this->logger->info("Url fichier " + $url_fichier);
        
        $filename = basename($url_fichier);  
	   
    //$uri_prefix = 'private://music/';
	//$filename = 'Flames.mp3';

    $uri = $uri_prefix . $filename;  
	$headers = [
      'Content-Type' => 'audio/mp3', // Would want a condition to check for extension and set Content-Type dynamically
      'Content-Description' => 'File Download',
      'Content-Disposition' => 'attachment; filename=' . $filename
    ];

    // TODO Get user id
	// TODO Verify user has send money in notification
	// TODO Allow download 	
	
	$user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
	$name = $user->get('name')->value;
	$query = \Drupal::entityQuery('node');
	$query->condition('type', 'notification');
	$query->condition('field_telephone_number', $name , '=');
	$query->range(0, 1);
	$nb_resultats = $query->count()->execute();
	
	if($nb_resultats  == 1){
		 
    return new BinaryFileResponse($url_fichier, 200, $headers, true );
	}
	
	else {
	    return [
      '#markup' => $this->t('Pas de fichier disponible.'),
    ];	
	}







  }

}
 
