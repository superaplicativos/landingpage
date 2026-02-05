<?php

$errors = array();

if (!isset($_POST['nome']) || trim($_POST['nome']) === '') {
	$errors['nome'] = 'Informe seu nome completo';
}

if (!isset($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
	$errors['email'] = 'Informe um e-mail válido';
}

if (!isset($_POST['telefone']) || trim($_POST['telefone']) === '') {
	$errors['telefone'] = 'Informe seu telefone';
}

if (!isset($_POST['cpf']) || trim($_POST['cpf']) === '') {
	$errors['cpf'] = 'Informe seu CPF';
}

if (!isset($_POST['cidade']) || trim($_POST['cidade']) === '') {
	$errors['cidade'] = 'Informe sua cidade';
}

if (!isset($_FILES['comprovante']) || $_FILES['comprovante']['error'] !== UPLOAD_ERR_OK) {
	$errors['comprovante'] = 'Envie o comprovante de residência';
}

if (!isset($_FILES['documento']) || $_FILES['documento']['error'] !== UPLOAD_ERR_OK) {
	$errors['documento'] = 'Envie o documento pessoal';
}

if (!empty($errors)) {
	$errorOutput = '<div class="alert alert-danger alert-dismissible" role="alert">';
	$errorOutput .= '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
	$errorOutput .= '<ul>';
	foreach ($errors as $value) {
		$errorOutput .= '<li>'.$value.'</li>';
	}
	$errorOutput .= '</ul>';
	$errorOutput .= '</div>';
	echo $errorOutput;
	die();
}

$nome = trim($_POST['nome']);
$email = trim($_POST['email']);
$telefone = trim($_POST['telefone']);
$cpf = trim($_POST['cpf']);
$cidade = trim($_POST['cidade']);
$cpfSafe = preg_replace('/\D+/', '', $cpf);

$accessToken = getenv('CLICKSIGN_ACCESS_TOKEN');
$templateKey = getenv('CLICKSIGN_TEMPLATE_KEY');
$baseUrl = getenv('CLICKSIGN_BASE_URL');
if (!$baseUrl) {
	$baseUrl = 'https://app.clicksign.com';
}

if (!$accessToken || !$templateKey) {
	$result = '<div class="alert alert-danger alert-dismissible" role="alert">';
	$result .= '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
	$result .= 'Configuração de integração indisponível. Tente novamente mais tarde.';
	$result .= '</div>';
	echo $result;
	die();
}

$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) {
	mkdir($uploadDir, 0755, true);
}

$timestamp = date('YmdHis');
$comprovanteName = basename($_FILES['comprovante']['name']);
$documentoName = basename($_FILES['documento']['name']);
$comprovantePath = $uploadDir . '/comprovante-' . $cpfSafe . '-' . $timestamp . '-' . $comprovanteName;
$documentoPath = $uploadDir . '/documento-' . $cpfSafe . '-' . $timestamp . '-' . $documentoName;
move_uploaded_file($_FILES['comprovante']['tmp_name'], $comprovantePath);
move_uploaded_file($_FILES['documento']['tmp_name'], $documentoPath);

$signerPayload = array(
	'signer' => array(
		'email' => $email,
		'phone_number' => $telefone,
		'auths' => array('email'),
		'name' => $nome,
		'documentation' => $cpf,
		'has_documentation' => true
	)
);

$signerResponse = clicksignRequest($baseUrl . '/api/v1/signers?access_token=' . urlencode($accessToken), $signerPayload);
if (!isset($signerResponse['signer']['key'])) {
	failResponse($signerResponse);
}

$signerKey = $signerResponse['signer']['key'];
$documentPath = '/convites/convite-' . $cpfSafe . '-' . $timestamp . '.pdf';

$documentPayload = array(
	'document' => array(
		'path' => $documentPath
	),
	'template' => array(
		'data' => array(
			'nome' => $nome,
			'email' => $email,
			'telefone' => $telefone,
			'cpf' => $cpf,
			'cidade' => $cidade
		)
	)
);

$documentResponse = clicksignRequest($baseUrl . '/api/v1/templates/' . urlencode($templateKey) . '/documents?access_token=' . urlencode($accessToken), $documentPayload);
if (!isset($documentResponse['document']['key'])) {
	failResponse($documentResponse);
}

$documentKey = $documentResponse['document']['key'];

$listPayload = array(
	'list' => array(
		'document_key' => $documentKey,
		'signer_key' => $signerKey,
		'sign_as' => 'sign',
		'refusable' => true,
		'group' => 1,
		'message' => 'Olá ' . $nome . ', seu convite está disponível para assinatura digital.'
	)
);

$listResponse = clicksignRequest($baseUrl . '/api/v1/lists?access_token=' . urlencode($accessToken), $listPayload);
$requestSignatureKey = null;
if (isset($listResponse['list']['request_signature_key'])) {
	$requestSignatureKey = $listResponse['list']['request_signature_key'];
}

if ($requestSignatureKey) {
	$notificationPayload = array(
		'notification' => array(
			'request_signature_key' => $requestSignatureKey,
			'message' => 'Olá ' . $nome . ', por favor assine o convite para prosseguirmos.'
		)
	);
	clicksignRequest($baseUrl . '/api/v1/notifications?access_token=' . urlencode($accessToken), $notificationPayload);
}

$result = '<div class="alert alert-success alert-dismissible" role="alert">';
$result .= '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
$result .= 'Convite enviado com sucesso. Verifique seu e-mail para assinar.';
$result .= '</div>';

echo $result;

function clicksignRequest($url, $payload) {
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json'));
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$error = curl_error($ch);
	curl_close($ch);
	if ($response === false || $httpCode < 200 || $httpCode >= 300) {
		return array('error' => $error ? $error : $response, 'http_code' => $httpCode);
	}
	$decoded = json_decode($response, true);
	return $decoded ? $decoded : array('error' => $response, 'http_code' => $httpCode);
}

function failResponse($response) {
	$message = 'Não foi possível enviar o convite. Tente novamente.';
	if (isset($response['error'])) {
		$message = $response['error'];
		if (is_array($message)) {
			$message = json_encode($message);
		}
	}
	$result = '<div class="alert alert-danger alert-dismissible" role="alert">';
	$result .= '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
	$result .= $message;
	$result .= '</div>';
	echo $result;
	die();
}
