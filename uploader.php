<?php         
require_once 'API/Client.php';     
require_once 'API/Service/Drive.php';
$client = new Google_Client();

$client->setClientId('CLIENT ID');
$client->setClientSecret('CLIENT SECRET');
$client->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');
$client->setScopes(array('https://www.googleapis.com/auth/drive')); //Full Access
$emailAddress = 'seu-email@gmail.com'; //Opcional

/** 
 * Instanciando o serviço
 */ 
$service = new Google_Service_Drive($client);


/**
 * Obtem um novo token de acesso
 * Tipo da variável				Nome 		   Descrição da variável
 *
 * @param Google_Service_Drive 			$client 	-  Objeto Cliente da API
 *
 * @return void
 */
function obter_token($client)
{	
	global $emailAddress;
	$client->setAccessType('offline');
	//$client->setApprovalPrompt("force");
	
	//obtendo a URL de autenticação para re-obter o token;
  	$tmpUrl = parse_url($client->createAuthUrl());

	$query = explode('&', $tmpUrl['query']);

	//Adicionando o id (email) do usuário [opcional]
	$query[] = 'user_id=' . urlencode($emailAddress);

	//Remontando a url;
  	$authUrl = $tmpUrl['scheme'] . '://' . $tmpUrl['host'] .
      $tmpUrl['path'] . '?' . implode('&', $query);

	//Pedindo o código de autenticação
	print "URL para obter o codigo:\n\n$authUrl\n\n";
	print "Colar o codigo aqui:\n";
	$authCode = trim(fgets(STDIN));

	try
	{
		// Seta o código de autorização para obter o token
		$accessToken = $client->authenticate($authCode);

		$client->setAccessToken($accessToken);

		//Escreve o Token de renovação no arquivo
		//$accessToken = json_decode($accessToken);
		//file_put_contents("token", $accessToken->refresh_token);
		file_put_contents("token", $accessToken);
	}
	catch(Exception $e)
	{
		die("Codigo invalido\n\n");
	}
}

/**
 * Inserindo um arquivo
 *
 *		  Tipo da variável		Nome 		   Descrição da variável
 *
 * @param Google_Service_Drive 			$service 	 - Instancia do objeto 'service Drive'.
 * @param string 				$title 		 - Título do arquivo inserido (deve incluir a extensão do arquivo).
 * @param string 				$description 	 - Descrição do arquivo inserido.
 * @param string 				$parentId	 - ID da pasta onde o arquivo será inserido.
 * @param string 				$mimeType 	 - Tipo MIME do arquivo inserido - Ex: "application/pdf"
 * @param string 				$filename 	 - Nome do arquivo no disco.
 *
 * @return Google_Service_Drive_DriveFile O objeto "arquivo" que foi inserido. Se houver algum erro, então retorna NULL.
 */
function insertFile($service, $title, $description, $parentId, $mimeType, $filename)
{
	print "\ntitulo: ".$title;
	print "\ndescription: ".$description;
	print "\nparentId: ".$parentId;
	print "\nfilename: ".$filename;

	//Cria o objeto "arquivo"
	$file = new Google_Service_Drive_DriveFile();
	$file->setTitle($title);
	$file->setDescription($description);
	$file->setMimeType($mimeType);

	// Seta pasta onde o arquivo será inserido
	if ($parentId != null) 
	{
		$parent = new Google_Service_Drive_ParentReference();
		$parent->setId($parentId);
		$file->setParents(array($parent));
	}

	try 
	{
		$data = file_get_contents($filename);

		$createdFile = $service->files->insert($file, array(
		  'data' => $data,
		  'mimeType' => $mimeType,
          	  'uploadType' => 'media'
		));

		// A linha abaixo escreve o ID do arquivo
		// print 'File ID: %s' % $createdFile->getId();

		return (bool)$createdFile;
	} 
	catch (Exception $e)
	{
		print "Erro no upload do arquivo: " . $e->getMessage();
	}
}




/********************************************************************************************
****************************** WORKFLOW DO UPLOAD DE ARQUIVOS *******************************
*********************************************************************************************/

if(file_exists("token"))
{
	try
	{
		$client->setAccessType('offline');
		$client->setAccessToken(file_get_contents("token"));
		print "Expirado: ".(($client->isAccessTokenExpired()) ? 'Sim' : 'Nao');
	}
	catch(Exception $e)
	{
		print "Token Errado\n";
		obter_token($client);
	}
}
else
{
	print "Sem Token\n";
	obter_token($client);
}


//Pasta onde estão os arquivos pdf 
$path = '/caminho/do/seu/arquivo';

//Obtem todos os arquivos .pdf dentro da pasta setada acima
$files = glob($path.'/*.{pdf}', GLOB_BRACE);

//Percorre o array fazendo upload de cada arquivo pdf
foreach($files as $file) 
{
	$title = end(explode("/", $file))."\n\n\n";
	$description = "Titulo:".$title;
	$parentId = "0B1V88ruDj7qleGsxSUxWQkhvaWs";
	$mimeType = "application/pdf";
	$filename = $file;

	//Criando o arquivo
	$createdFile = insertFile($service, $title, $description, $parentId, $mimeType, $filename);
	print_r($createdFile);
}
?>
