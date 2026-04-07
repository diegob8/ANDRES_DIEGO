<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración
$to_email = "info@prendetufiesta.cl"; // Email de destino
$from_email = "noreply@prendetufiesta.cl"; // Email de origen
$subject_prefix = "Consulta desde web - Prende Tu Fiesta";

// Función para limpiar datos de entrada
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

// Verificar que sea una petición POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

try {
    // Obtener y limpiar datos del formulario
    $nombre = isset($_POST['nombre']) ? clean_input($_POST['nombre']) : '';
    $email = isset($_POST['email']) ? clean_input($_POST['email']) : '';
    $telefono = isset($_POST['telefono']) ? clean_input($_POST['telefono']) : '';
    $mensaje = isset($_POST['mensaje']) ? clean_input($_POST['mensaje']) : '';

    // Validaciones
    $errors = [];

    if (empty($nombre)) {
        $errors[] = "El nombre es requerido";
    }

    if (empty($email)) {
        $errors[] = "El email es requerido";
    } elseif (!validate_email($email)) {
        $errors[] = "El email no es válido";
    }

    if (empty($telefono)) {
        $errors[] = "El teléfono es requerido";
    }

    if (empty($mensaje)) {
        $errors[] = "El mensaje es requerido";
    }

    // Si hay errores, devolver respuesta de error
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => implode(", ", $errors)
        ]);
        exit;
    }

    // Construir el mensaje de email
    $email_subject = $subject_prefix . " - " . $nombre;
    
    $email_body = "
    <html>
    <head>
        <title>Nueva consulta desde el sitio web</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #37c8cc; color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 20px; }
            .field { margin-bottom: 15px; }
            .label { font-weight: bold; color: #072a4d; }
            .value { margin-left: 10px; }
            .footer { background: #072a4d; color: white; padding: 15px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Nueva Consulta - Prende Tu Fiesta</h2>
            </div>
            <div class='content'>
                <div class='field'>
                    <span class='label'>Nombre:</span>
                    <span class='value'>" . $nombre . "</span>
                </div>
                <div class='field'>
                    <span class='label'>Email:</span>
                    <span class='value'>" . $email . "</span>
                </div>
                <div class='field'>
                    <span class='label'>Teléfono:</span>
                    <span class='value'>" . $telefono . "</span>
                </div>
                <div class='field'>
                    <span class='label'>Mensaje:</span>
                    <div style='margin-top: 10px; padding: 15px; background: white; border-left: 4px solid #37c8cc;'>
                        " . nl2br($mensaje) . "
                    </div>
                </div>
                <div class='field' style='margin-top: 20px; color: #666; font-size: 12px;'>
                    <span class='label'>Fecha:</span>
                    <span class='value'>" . date('d/m/Y H:i:s') . "</span>
                </div>
                <div class='field' style='color: #666; font-size: 12px;'>
                    <span class='label'>IP:</span>
                    <span class='value'>" . $_SERVER['REMOTE_ADDR'] . "</span>
                </div>
            </div>
            <div class='footer'>
                <p>Este mensaje fue enviado desde el formulario de contacto de prendetufiesta.cl</p>
                <p>Para responder, utiliza el email: " . $email . "</p>
            </div>
        </div>
    </body>
    </html>
    ";

    // Headers para el email
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . $from_email,
        'Reply-To: ' . $email,
        'X-Mailer: PHP/' . phpversion()
    ];

    // Enviar email
    $mail_sent = mail($to_email, $email_subject, $email_body, implode("\r\n", $headers));

    if ($mail_sent) {
        // Email de confirmación automática al cliente
        $client_subject = "Confirmación de consulta - Prende Tu Fiesta";
        $client_body = "
        <html>
        <head>
            <title>Confirmación de consulta</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #37c8cc; color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; }
                .footer { background: #072a4d; color: white; padding: 15px; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>¡Gracias por contactarnos!</h2>
                </div>
                <div class='content'>
                    <p>Hola <strong>" . $nombre . "</strong>,</p>
                    <p>Hemos recibido tu consulta y nos pondremos en contacto contigo a la brevedad.</p>
                    <p><strong>Resumen de tu consulta:</strong></p>
                    <p><strong>Teléfono:</strong> " . $telefono . "</p>
                    <p><strong>Mensaje:</strong></p>
                    <div style='background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #37c8cc;'>
                        " . nl2br($mensaje) . "
                    </div>
                    <p>Mientras tanto, puedes contactarnos directamente:</p>
                    <ul>
                        <li><strong>WhatsApp:</strong> <a href='https://wa.me/56989639573'>+569 8963 9573</a></li>
                        <li><strong>Email:</strong> info@prendetufiesta.cl</li>
                    </ul>
                </div>
                <div class='footer'>
                    <p><strong>Prende Tu Fiesta</strong></p>
                    <p>Arriendo de juegos mecánicos para fiestas</p>
                    <p>www.prendetufiesta.cl</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $client_headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $from_email,
            'X-Mailer: PHP/' . phpversion()
        ];

        // Enviar email de confirmación al cliente
        mail($email, $client_subject, $client_body, implode("\r\n", $client_headers));

        // Respuesta exitosa
        echo json_encode([
            'success' => true,
            'message' => 'Mensaje enviado correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al enviar el mensaje. Intenta nuevamente.'
        ]);
    }

} catch (Exception $e) {
    // Log del error (opcional)
    error_log("Error en formulario de contacto: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>