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
		
		// Create node object with attached file.
		$node = Node::create([
		  'type'        => 'notification',
		  'title'       => 'Test',
		  'body'        => $body,
		  'field_telephone_number' => $numero,

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
			$response = new Response(json_encode(['id' => $user->id(),'numero' => $numero ],'success' => 2)) ;
			// $this->createOrder($user->id(),$montant);
			$response->headers->set('Content-Type', 'application/json');
			return $response;
			
		}
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
  /*
   * @function createOrder() création de la commande
   * Utilisée pour les produits physiques
   */
   
	protected function createOrder($uid,$amount_received){
		$product = \Drupal\commerce_product\Entity\Product::load(1);
		$entity_manager = \Drupal::entityManager();
		$product_variation = $entity_manager->getStorage('commerce_product_variation')->load((int)$product->getVariationIds()[0]);
		$item_cost = $product_variation->get('price')->getValue()[0]['number'];
		$order_item = \Drupal\commerce_order\Entity\OrderItem::create([
			  'type' => 'default',
			  'purchased_entity' => $product_variation,
			  'quantity' => 1,
			  'unit_price' => $product_variation->getPrice(),
			]);
		$order_item->save();
		// Next, we create the order.
		$order = \Drupal\commerce_order\Entity\Order::create([
			  'type' => 'default',
			  'state' => 'draft',
			  'mail' => 'user@example.com',
			  'uid' => $uid,
			  'store_id' => 1,
			  'order_items' => [$order_item],
			  'placed' => time(),
			]);
		$order->save();
		if($amount_received >= $item_cost){
			$order->set('state','completed');
			$order->save();
		}
		$this->logger->info('creation de la commande');
}

  /**
   * @function download() 
   *
   */
  public function download() {
	   
    $uri_prefix = 'private://music/';
	$filename = 'Flames.mp3';

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
		 
    return new BinaryFileResponse($uri, 200, $headers, true );
	}
	
	else {
		
	}







  }

}
 
