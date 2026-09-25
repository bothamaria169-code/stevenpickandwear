<?php
require_once __DIR__ . '/../../includes/functions.php';

JWTHandler::clearToken();
session_destroy();
redirect('index.php');
