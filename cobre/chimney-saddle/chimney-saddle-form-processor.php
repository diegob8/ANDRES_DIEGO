<?php
// VERSIÓN ACTUALIZADA PARA CHIMNEY SADDLE - Campos actualizados según HTML
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Verificar si PHPMailer existe
$phpmailer_available = false;
if (file_exists('PHPMailer/src/Exception.php') && 
    file_exists('PHPMailer/src/PHPMailer.php') && 
    file_exists('PHPMailer/src/SMTP.php')) {
    
    require 'PHPMailer/src/Exception.php';
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';
    $phpmailer_available = true;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// CONFIGURACIÓN
$to_email = "andres@toromecanicoeventos.cl"; // Email de destino

// CONFIGURACIÓN SMTP
$smtp_configs = [
    'primary' => [
        'host' => 'smtp.gmail.com',
        'username' => 'i.perez.vergara@gmail.com',
        'password' => 'jhzo ogpt pwcs exio',
        'port' => 587,
        'secure' => 'tls'
    ]
];

// Función para calcular el precio del chimney saddle (actualizada)
function calculateChimneySaddlePrice($dimA, $dimB, $dimC, $dimD, $dimE, $materialType, $edgeDetail, $topBottom, $roofPitch) {
    // Convertir valores a float y manejar valores vacíos
    $dimA = floatval($dimA ?: 0);
    $dimB = floatval($dimB ?: 0);
    $dimC = floatval($dimC ?: 0);
    $dimD = floatval($dimD ?: 0);
    $dimE = floatval($dimE ?: 0);
    
    // Calcular área en pies cuadrados (asumiendo que las dimensiones están en pulgadas)
    // Para chimney saddle, usamos las dimensiones principales
    $length = ($dimA) / 12; // Convertir a pies
    $width = ($dimB) / 12;   // Convertir a pies
    $area = $length * $width;
    
    // Agregar área adicional basada en dimensiones C, D, E
    $additionalArea = (($dimC * $dimD) + ($dimE * 12)) / 144; // Convertir pulgadas cuadradas a pies cuadrados
    $totalArea = $area + $additionalArea;
    
    // Costos de material por pie cuadrado
    $materialCosts = [
        '16oz. Copper' => 20.00,
        '20oz. Copper' => 24.00,
        '24ga. Galvanized' => 12.00,
        '24ga. Bondarized' => 14.00
    ];
    
    // Factores de complejidad del detalle del borde
    $edgeFactors = [
        'Closed Hem' => 1.0,
        'Closed Hem with Kick' => 1.3,
        'Raw No Hem & No Kick' => 0.8
    ];
    
    // Factores por tipo (Top/Bottom/Set)
    $topBottomFactors = [
        'Top' => 1.0,
        'Bottom' => 1.2, // Bottom es más complejo
        'Set (Top & Bottom)' => 1.8 // Set incluye ambos
    ];
    
    // Factores por pitch del techo (más pitch = más complejo)
    $pitchFactors = [
        '0 Pitch' => 1.0,
        '2/12 Pitch' => 1.1,
        '3/12 Pitch' => 1.15,
        '4/12 Pitch' => 1.2,
        '5/12 Pitch' => 1.25,
        '6/12 Pitch' => 1.3
    ];
    
    // Obtener factores (con defaults)
    $materialCost = isset($materialCosts[$materialType]) ? $materialCosts[$materialType] : $materialCosts['16oz. Copper'];
    $edgeFactor = isset($edgeFactors[$edgeDetail]) ? $edgeFactors[$edgeDetail] : 1.0;
    $topBottomFactor = isset($topBottomFactors[$topBottom]) ? $topBottomFactors[$topBottom] : 1.0;
    $pitchFactor = isset($pitchFactors[$roofPitch]) ? $pitchFactors[$roofPitch] : 1.0;
    
    // Costo base de mano de obra para saddles
    $baseLaborCost = 85.00;
    
    // Factor de complejidad del tamaño
    $sizeComplexityFactor = max(1.0, $totalArea * 0.15);
    
    // Calcular precio final
    $materialPrice = $totalArea * $materialCost;
    $laborPrice = $baseLaborCost * $sizeComplexityFactor;
    $basePrice = $materialPrice + $laborPrice;
    
    // Aplicar todos los factores
    $totalPrice = $basePrice * $edgeFactor * $topBottomFactor * $pitchFactor;
    
    // Precio mínimo para saddles
    return max($totalPrice, 180.00);
}

// Función compatible con PHP 7.x para detectar dominio
function getDomainType($email) {
    $domain = strtolower(substr(strrchr($email, "@"), 1));
    
    // Dominios chilenos
    $cl_domains = ['cl', 'utem.cl', 'uchile.cl', 'usach.cl', 'postgrado.cl'];
    foreach ($cl_domains as $cl_domain) {
        if (substr($domain, -strlen($cl_domain)) === $cl_domain) {
            return 'cl';
        }
    }
    
    // Dominios internacionales
    $intl_domains = ['com', 'net', 'org', 'edu', 'gov'];
    foreach ($intl_domains as $intl_domain) {
        if (substr($domain, -strlen($intl_domain)) === $intl_domain) {
            return 'international';
        }
    }
    
    return 'other';
}

// Función con debugging mejorado
function sendEmailWithDebug($to, $subject, $body, $from_name = 'Copper & Metal Supply Inc.', $reply_to = null) {
    global $smtp_configs, $phpmailer_available;
    
    $debug_info = ['attempts' => [], 'final_result' => false];
    $domain_type = getDomainType($to);
    
    // INTENTO 1: PHPMailer (si está disponible)
    if ($phpmailer_available) {
        $mail = new PHPMailer(true);
        
        try {
            // Configuración SMTP
            $mail->isSMTP();
            $mail->Host = $smtp_configs['primary']['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_configs['primary']['username'];
            $mail->Password = $smtp_configs['primary']['password'];
            $mail->SMTPSecure = $smtp_configs['primary']['secure'];
            $mail->Port = $smtp_configs['primary']['port'];
            
            // Configuración adicional
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->SMTPDebug = 0;
            
            // Headers
            $mail->setFrom($smtp_configs['primary']['username'], $from_name);
            $mail->addAddress($to);
            
            if ($reply_to) {
                $mail->addReplyTo($reply_to);
            }
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);
            
            // Intentar envío
            if ($mail->send()) {
                $debug_info['attempts'][] = ['method' => 'phpmailer', 'status' => 'success'];
                $debug_info['final_result'] = true;
                return $debug_info;
            }
            
        } catch (Exception $e) {
            $debug_info['attempts'][] = [
                'method' => 'phpmailer', 
                'status' => 'failed', 
                'error' => $e->getMessage()
            ];
        }
    } else {
        $debug_info['attempts'][] = [
            'method' => 'phpmailer', 
            'status' => 'not_available', 
            'error' => 'PHPMailer files not found'
        ];
    }
    
    // INTENTO 2: mail() nativo
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: noreply@toromecanicoeventos.cl',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    if ($reply_to) {
        $headers[] = 'Reply-To: ' . $reply_to;
    }
    
    if (mail($to, $subject, $body, implode("\r\n", $headers))) {
        $debug_info['attempts'][] = ['method' => 'native_mail', 'status' => 'success'];
        $debug_info['final_result'] = true;
        return $debug_info;
    } else {
        $debug_info['attempts'][] = [
            'method' => 'native_mail', 
            'status' => 'failed', 
            'error' => 'mail() function failed'
        ];
    }
    
    return $debug_info;
}

// Función para limpiar datos
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Función para validar email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Verificar método POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

try {
    // Recoger datos del formulario (ACTUALIZADOS)
    $first_name = isset($_POST['first_name']) ? clean_input($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? clean_input($_POST['last_name']) : '';
    $email = isset($_POST['email']) ? clean_input($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? clean_input($_POST['phone']) : '';
    $company_name = isset($_POST['company_name']) ? clean_input($_POST['company_name']) : '';
    
    // Dimensiones (solo 5 dimensiones: A, B, C, D, E)
    $dimension_a = isset($_POST['dimension_a']) ? clean_input($_POST['dimension_a']) : '';
    $dimension_b = isset($_POST['dimension_b']) ? clean_input($_POST['dimension_b']) : '';
    $dimension_c = isset($_POST['dimension_c']) ? clean_input($_POST['dimension_c']) : '';
    $dimension_d = isset($_POST['dimension_d']) ? clean_input($_POST['dimension_d']) : '';
    $dimension_e = isset($_POST['dimension_e']) ? clean_input($_POST['dimension_e']) : '';
    
    // Campos nuevos
    $top_bottom = isset($_POST['top_bottom']) ? clean_input($_POST['top_bottom']) : '';
    $roof_pitch = isset($_POST['roof_pitch']) ? clean_input($_POST['roof_pitch']) : '';
    $edge_detail = isset($_POST['edge_detail']) ? clean_input($_POST['edge_detail']) : '';
    $material_type = isset($_POST['material_type']) ? clean_input($_POST['material_type']) : '';

    // Validaciones básicas (ACTUALIZADAS)
    $errors = [];

    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!validate_email($email)) {
        $errors[] = "Invalid email format";
    }
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($top_bottom)) $errors[] = "Top/Bottom selection is required";
    if (empty($roof_pitch)) $errors[] = "Roof pitch selection is required";
    if (empty($edge_detail)) $errors[] = "Edge detail selection is required";
    if (empty($material_type)) $errors[] = "Material type selection is required";

    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => implode(", ", $errors)
        ]);
        exit;
    }

    // Calcular precio estimado (ACTUALIZADO)
    $estimated_price = calculateChimneySaddlePrice(
        $dimension_a, $dimension_b, $dimension_c, $dimension_d, 
        $dimension_e, $material_type, $edge_detail, $top_bottom, $roof_pitch
    );

    // HTML para el negocio (ACTUALIZADO)
    $email_body = "
    <html>
    <head>
        <title>Chimney Saddle Quote Request</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #B8860B; color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 20px; }
            .field { margin-bottom: 15px; }
            .label { font-weight: bold; color: #2C2C2C; }
            .value { margin-left: 10px; }
            .dimensions-section { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #B8860B; }
            .price-section { background: #4A90A4; color: white; padding: 20px; margin: 15px 0; text-align: center; border-radius: 8px; }
            .price-amount { font-size: 2rem; font-weight: bold; margin: 10px 0; }
            .footer { background: #2C2C2C; color: white; padding: 15px; text-align: center; font-size: 12px; }
            .specs-section { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #4A90A4; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Chimney Saddle Quote Request</h2>
                <p>Copper & Metal Supply Inc.</p>
            </div>
            <div class='content'>
                <h3>Customer Information</h3>
                <div class='field'>
                    <span class='label'>First Name:</span>
                    <span class='value'>" . $first_name . "</span>
                </div>
                <div class='field'>
                    <span class='label'>Last Name:</span>
                    <span class='value'>" . $last_name . "</span>
                </div>
                <div class='field'>
                    <span class='label'>Email:</span>
                    <span class='value'>" . $email . "</span>
                </div>
                <div class='field'>
                    <span class='label'>Phone:</span>
                    <span class='value'>" . $phone . "</span>
                </div>
                " . ($company_name ? "<div class='field'><span class='label'>Company:</span><span class='value'>" . $company_name . "</span></div>" : "") . "
    
                <div class='price-section'>
                    <h3>Estimated Price</h3>
                    <div class='price-amount'>$" . number_format($estimated_price, 2) . "</div>
                    <p style='font-size: 0.9rem; margin: 0;'>*This is an estimate. Final price may vary based on complexity and current material costs.</p>
                </div>
                
                <div class='dimensions-section'>
                    <h3>Saddle Dimensions</h3>
                    <div class='field'>
                        <span class='label'>Dimension A:</span>
                        <span class='value'>" . ($dimension_a ?: 'Not specified') . " inches</span>
                    </div>
                    <div class='field'>
                        <span class='label'>Dimension B:</span>
                        <span class='value'>" . ($dimension_b ?: 'Not specified') . " inches</span>
                    </div>
                    <div class='field'>
                        <span class='label'>Dimension C:</span>
                        <span class='value'>" . ($dimension_c ?: 'Not specified') . " inches</span>
                    </div>
                    <div class='field'>
                        <span class='label'>Dimension D:</span>
                        <span class='value'>" . ($dimension_d ?: 'Not specified') . " inches</span>
                    </div>
                    <div class='field'>
                        <span class='label'>Dimension E:</span>
                        <span class='value'>" . ($dimension_e ?: 'Not specified') . " inches</span>
                    </div>
                </div>
                
                <div class='specs-section'>
                    <h3>Saddle Specifications</h3>
                    <div class='field'>
                        <span class='label'>Top or Bottom:</span>
                        <span class='value'>" . $top_bottom . "</span>
                    </div>
                    <div class='field'>
                        <span class='label'>Roof Pitch:</span>
                        <span class='value'>" . $roof_pitch . "</span>
                    </div>
                    <div class='field'>
                        <span class='label'>Edge Detail:</span>
                        <span class='value'>" . $edge_detail . "</span>
                    </div>
                    <div class='field'>
                        <span class='label'>Type of Material:</span>
                        <span class='value'>" . $material_type . "</span>
                    </div>
                </div>
                
                <div class='field' style='margin-top: 20px; color: #666; font-size: 12px;'>
                    <span class='label'>Date:</span>
                    <span class='value'>" . date('m/d/Y H:i:s') . "</span>
                </div>
                <div class='field' style='color: #666; font-size: 12px;'>
                    <span class='label'>IP:</span>
                    <span class='value'>" . $_SERVER['REMOTE_ADDR'] . "</span>
                </div>
            </div>
            <div class='footer'>
                <p>This message was sent from the Chimney Saddle Quote Request form</p>
                <p>To respond, use the email: " . $email . "</p>
            </div>
        </div>
    </body>
    </html>
    ";

    // HTML para el cliente (ACTUALIZADO)
    $client_body = "
    <html>
    <head>
        <title>Chimney Saddle Quote Request Confirmation</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                background: #f5f5f5; 
                margin: 0; 
                padding: 20px; 
            }
            .email-container { 
                max-width: 400px; 
                margin: 0 auto; 
                background: white; 
                border: 3px solid #4A90A4; 
                border-radius: 8px;
            }
            .header { 
                background: #4A90A4; 
                color: white; 
                padding: 20px; 
                text-align: center; 
                border-radius: 5px 5px 0 0;
            }
            .header h1 { 
                margin: 0; 
                font-size: 18px; 
                font-weight: bold;
            }
            .diagram-section {
                background: #e8e8e8;
                padding: 20px;
                text-align: center;
            }
            .content { 
                padding: 20px; 
                background: white; 
            }
            .check-info {
                text-align: center;
                margin-bottom: 20px;
                font-size: 14px;
            }
            .price-highlight {
                background: #B8860B;
                color: white;
                padding: 15px;
                margin: 15px -20px;
                text-align: center;
                font-size: 16px;
                font-weight: bold;
            }
            .price-amount {
                font-size: 24px;
                margin: 5px 0;
            }
            .info-grid {
                font-size: 12px;
                line-height: 1.4;
            }
            .info-row { 
                margin-bottom: 5px; 
                display: flex;
                justify-content: space-between;
            }
            .label { 
                font-weight: bold; 
                color: #333; 
            }
            .value { 
                color: #666; 
                text-align: right;
            }
            .visit-button { 
                display: inline-block; 
                background: #999; 
                color: white; 
                padding: 10px 20px; 
                text-decoration: none; 
                border-radius: 5px; 
                margin: 20px 0; 
                text-align: center;
                width: 120px;
            }
            .contact-section {
                background: #f9f9f9;
                padding: 15px;
                margin: 15px 0;
                text-align: center;
                font-size: 11px;
                line-height: 1.3;
            }
            .social-section {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 20px;
                background: #f0f0f0;
                font-size: 10px;
            }
            .footer-note {
                background: #333;
                color: white;
                padding: 10px;
                text-align: center;
                font-size: 10px;
                border-radius: 0 0 5px 5px;
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <h1>Thanks For Your Saddle Request</h1>
                <div style='font-size: 12px; margin-top: 5px;'>" . strtoupper($first_name) . "</div>
            </div>
            
            <div class='diagram-section'>
                <img src='https://toromecanicoeventos.cl/cobre/chimney-saddle/SADDLE.webp' alt='Chimney Saddle Dimensions Diagram' style='max-width: 100%; height: auto; border-radius: 5px; display: block; margin: 0 auto;'>
            </div>
            
            <div class='price-highlight'>
                <div>Estimated Price:</div>
                <div class='price-amount'>$" . number_format($estimated_price, 2) . "</div>
                <div style='font-size: 11px; margin-top: 5px;'>*Final price may vary</div>
            </div>
            
            <div class='content'>
                <div class='check-info'>
                    <strong>Please double check the information you submitted:</strong>
                </div>
                
                <div class='info-grid'>
                    <div class='info-row'>
                        <span class='label'>First Name:</span>
                        <span class='value'>" . $first_name . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='label'>Last name:</span>
                        <span class='value'>" . $last_name . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='label'>E-mail:</span>
                        <span class='value'>" . $email . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='label'>Phone:</span>
                        <span class='value'>" . $phone . "</span>
                    </div>
                    " . ($company_name ? "<div class='info-row'><span class='label'>Company:</span><span class='value'>" . $company_name . "</span></div>" : "") . "
                    <div class='info-row'>
                        <span class='label'>Dimension for A:</span>
                        <span class='value'>" . ($dimension_a ?: 'Not specified') . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='label'>Dimension for B:</span>
                        <span class='value'>" . ($dimension_b ?: 'Not specified') . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='label'>Dimension for C:</span>
                        <span class='value'>" . ($dimension_c ?: 'Not specified') . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='label'>Dimension for D:</span>
                        <span class='value'>" . ($dimension_d ?: 'Not specified') . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='label'>Dimension for E:</span>
                        <span class='value'>" . ($dimension_e ?: 'Not specified') . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='label'>Top or Bottom:</span>
                        <span class='value'>" . $top_bottom . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='label'>Roof Pitch:</span>
                        <span class='value'>" . $roof_pitch . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='label'>Edge Detail:</span>
                        <span class='value'>" . $edge_detail . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='label'>Type of Material:</span>
                        <span class='value'>" . $material_type . "</span>
                    </div>
                </div>
                
                <div style='text-align: center;'>
                    <a href='#' class='visit-button'>Visit Our Site</a>
                </div>
            </div>
            
            <div class='contact-section'>
                <div><strong>132 Garden Street, Santa Barbara, CA, USA</strong></div>
                <div>(805) 965-6911</div>
            </div>
            
            <div class='social-section'>
                <div><strong>Share on social</strong></div>
                <div><strong>Check out our site</strong></div>
            </div>
            
            <div class='footer-note'>
                This email was created with professional service
            </div>
        </div>
    </body>
    </html>
    ";

    // Enviar email al negocio
    $business_result = sendEmailWithDebug(
        $to_email, 
        'New Chimney Saddle Quote Request from ' . $first_name . ' ' . $last_name . ' - $' . number_format($estimated_price, 2), 
        $email_body, 
        'Copper & Metal Supply Inc.',
        $email
    );

    // Log detallado
    $log_entry = date('Y-m-d H:i:s') . " - CHIMNEY SADDLE REQUEST\n";
    $log_entry .= "To: " . $to_email . "\n";
    $log_entry .= "Customer: " . $email . " (" . getDomainType($email) . ")\n";
    $log_entry .= "Estimated Price: $" . number_format($estimated_price, 2) . "\n";
    $log_entry .= "Type: " . $top_bottom . " - " . $roof_pitch . "\n";
    $log_entry .= "PHPMailer available: " . ($phpmailer_available ? 'YES' : 'NO') . "\n";
    foreach ($business_result['attempts'] as $attempt) {
        $log_entry .= "Attempt " . $attempt['method'] . ": " . $attempt['status'];
        if (isset($attempt['error'])) {
            $log_entry .= " - " . $attempt['error'];
        }
        $log_entry .= "\n";
    }
    $log_entry .= "Final result: " . ($business_result['final_result'] ? 'SUCCESS' : 'FAILED') . "\n\n";
    
    file_put_contents("debug_email_log.txt", $log_entry, FILE_APPEND);

    if ($business_result['final_result']) {
        // Enviar confirmación al cliente
        $client_result = sendEmailWithDebug(
            $email, 
            'Thanks for your Chimney Saddle request - Copper & Metal Supply Inc.', 
            $client_body, 
            'Copper & Metal Supply Inc.'
        );
        
        // Log del cliente
        $client_log = date('Y-m-d H:i:s') . " - CLIENT SADDLE EMAIL\n";
        $client_log .= "To: " . $email . " (" . getDomainType($email) . ")\n";
        $client_log .= "Estimated Price: $" . number_format($estimated_price, 2) . "\n";
        foreach ($client_result['attempts'] as $attempt) {
            $client_log .= "Attempt " . $attempt['method'] . ": " . $attempt['status'];
            if (isset($attempt['error'])) {
                $client_log .= " - " . $attempt['error'];
            }
            $client_log .= "\n";
        }
        $client_log .= "Final result: " . ($client_result['final_result'] ? 'SUCCESS' : 'FAILED') . "\n\n";
        
        file_put_contents("debug_email_log.txt", $client_log, FILE_APPEND);

        echo json_encode([
            'success' => true,
            'message' => 'Chimney Saddle quote request sent successfully! Estimated price: $' . number_format($estimated_price, 2),
            'estimated_price' => $estimated_price,
            'debug' => [
                'phpmailer_available' => $phpmailer_available,
                'business_email' => $business_result,
                'client_email' => $client_result,
                'customer_domain_type' => getDomainType($email),
                'calculated_price' => $estimated_price
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Could not send email to business. Check logs.',
            'debug' => [
                'phpmailer_available' => $phpmailer_available,
                'business_email' => $business_result,
                'customer_domain_type' => getDomainType($email),
                'calculated_price' => $estimated_price
            ]
        ]);
    }

} catch (Exception $e) {
    $error_log = date('Y-m-d H:i:s') . " - SADDLE FATAL ERROR: " . $e->getMessage() . "\n";
    file_put_contents("debug_email_log.txt", $error_log, FILE_APPEND);
    
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'debug' => ['error' => $e->getMessage()]
    ]);
}
?>