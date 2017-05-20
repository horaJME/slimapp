<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';

$app = new \Slim\App;

//IDENTIFICATION

//GET METHOD USER
//Searching if users OTP list exists

$app->get('/api/user/{user}', function (Request $request, Response $response) {
    $user = $request->getAttribute('user');
	$filename = "users/".$user.".txt";
		if (file_exists($filename)) {
			echo "User exists";
		} else {
			echo "Error #001 - User does not exist";
		}
});

//POST METHOD PIN
//Sending User and his PIN if his OTP list exists

$app->post('/api/PIN', function (Request $request, Response $response) {
	//Posted data
	$data = $request->getParsedBody();
	$counter = 0;
	//Unpacking info
	//1. PIN 2. Username
	$info = [];
	$info["PIN"] = $data["PIN"];
	$info["user"] = $data["user"];
	//Reading file
	$filename = "users/".$info["user"].".txt";
	$userFile = fopen($filename, "rw") or die("Unable to open file!");
	$file = fread($userFile,filesize($filename));
	$file = json_decode($file);
	fclose($userFile);
	
	//Checking if user already finished process 
	if (empty($file->{"PIN"})){
		//Preparing JSON for writing
		$status = "PIN Added";
		$file->{"PIN"} = $info["PIN"];
		$file->{"counter"} = $counter;
		$file = json_encode($file);

		//Writing PIN into file
		$writeFile = fopen($filename, "w") or die("Unable to open file!");
		fwrite($writeFile, $file);
		fclose($writeFile);
			
		}else {
			$status = "Error #002 - PIN already set";
		}
		
		echo $status;
	
});

//GET METHOD OTP
//Getting OTP list if PIN is set

$app->get('/api/OTP/{user}', function (Request $request, Response $response) {
    $user = $request->getAttribute('user');
	$filename = "users/".$user.".txt";
	$userFile = fopen($filename, "rw") or die("Unable to open file!");
	$file = fread($userFile,filesize($filename));
	$response = $response->withJson($file);
	$response = $response->withHeader('Content-type', 'application/json');
	//Sending OTP list
	return $response;
});

//AUTHENTIFICATION

//POST METHOD AUTH
//Verifying given credentials, checking OTP 

$app->post('/api/auth', function (Request $request, Response $response) {
	//Posted data
	$data = $request->getParsedBody();
	//Unpacking info
	//1. PIN 2. Username
	$info = [];
	$info["OTP"] = $data["OTP"];
	$info["user"] = $data["user"];
	//Reading file
	$filename = "users/".$info["user"].".txt";
	$userFile = fopen($filename, "rw") or die("Unable to open file!");
	$file = fread($userFile,filesize($filename));
	$file = json_decode($file);
	$OTPlist[] = $file->{"OTPlist"};
	$FileOTP = json_decode(json_encode($OTPlist[0][$file->{"counter"}]));
	fclose($userFile);
	
	//Checking if OTP and counter match
	if ($FileOTP->{"OTP"} == $info["OTP"]){
		//Preparing JSON for writing
		$status = "Authentication successful";
			
		//Updating counter, start from 0 after 9
		$file->{"counter"} += 1;
		$file->{"counter"} = $file->{"counter"}%10;
		$file = json_encode($file);

		//Writing updated counter into file
		$writeFile = fopen($filename, "w") or die("Unable to open file!");
		fwrite($writeFile, $file);
		fclose($writeFile);
			
	}else {
		$status = "Error #003 - OTPs doesnt match, Authentication failed";
	}
		
	//Response
	$responseArray = [];
	$responseArray["status"] = $status;
	$responseArray["data"] = $info;
	
	$response = $response->withJson($responseArray);
	$response = $response->withHeader('Content-type', 'application/json');
	return $response;
	
});

$app->run();