<?php
/**
 * Gestión de Emails - MyPyMEs Training
 * Funciones para envío de emails usando PHPMailer o mail() nativo
 */

/**
 * Enviar email de certificado
 * 
 * @param string $toEmail Email del destinatario
 * @param string $toName Nombre del destinatario
 * @param string $certType Tipo de certificado ('knowledge' o 'certified')
 * @param float|null $score Puntuación del examen (solo para certified)
 * @return bool True si se envió correctamente
 */
function sendCertificateEmail($toEmail, $toName, $certType, $score = null) {
    $subject = '¡Felicitaciones! Has obtenido tu certificado MyPyMEs';
    
    $certTypeName = $certType === 'knowledge' ? 'Certificado de Conocimiento' : 'Certificado de Usuario Calificado';
    
    // Construir el cuerpo del email en HTML
    $htmlBody = generateCertificateEmailHTML($toName, $certType, $certTypeName, $score);
    
    // Construir el cuerpo en texto plano
    $textBody = generateCertificateEmailText($toName, $certType, $certTypeName, $score);
    
    // Intentar enviar con PHPMailer si está disponible, sino usar mail()
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return sendEmailPHPMailer($toEmail, $toName, $subject, $htmlBody, $textBody);
    } else {
        return sendEmailNative($toEmail, $toName, $subject, $htmlBody, $textBody);
    }
}

/**
 * Generar HTML del email de certificado
 */
function generateCertificateEmailHTML($toName, $certType, $certTypeName, $score) {
    $scoreText = $score ? " con una calificación de " . number_format($score, 0) . "%" : "";
    
    return "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Certificado MyPyMEs</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #1E40AF 0%, #3B82F6 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px; }
            .certificate-badge { background: #FEF3C7; border: 3px solid #F59E0B; padding: 20px; border-radius: 10px; text-align: center; margin: 20px 0; }
            .button { display: inline-block; background: #1E40AF; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; color: #6B7280; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin: 0;'>🎓 MyPyMEs Training</h1>
                <p style='margin: 10px 0 0 0;'>Tu negocio digital</p>
            </div>
            
            <div class='content'>
                <h2 style='color: #1E40AF;'>¡Felicitaciones, {$toName}!</h2>
                
                <p>Nos complace informarte que has completado exitosamente la capacitación de <strong>MyPyMEs</strong>{$scoreText}.</p>
                
                <div class='certificate-badge'>
                    <h3 style='color: #D97706; margin: 0;'>🏆 {$certTypeName}</h3>
                    <p style='margin: 10px 0 0 0;'>Emitido el " . date('d/m/Y') . "</p>
                </div>
                
                <p><strong>Tu certificado está disponible en la plataforma:</strong></p>
                
                <div style='text-align: center;'>
                    <a href='" . BASE_URL . "' class='button'>Acceder a la Plataforma</a>
                </div>
                
                <p>Desde tu panel de usuario podrás:</p>
                <ul>
                    <li>✅ Ver tu certificado en pantalla</li>
                    <li>📥 Descargar tu certificado en formato PDF</li>
                    <li>📧 Compartir tu logro</li>
                </ul>
                
                <p>Este certificado valida tus conocimientos en el uso del sistema MyPyMEs y demuestra tu capacidad para gestionar eficientemente las operaciones de tu negocio.</p>
                
                <p style='color: #059669; font-weight: bold;'>¡Gracias por confiar en MyPyMEs para el crecimiento de tu negocio!</p>
            </div>
            
            <div class='footer'>
                <p><strong>MyPyMEs - Tu negocio digital</strong></p>
                <p>training.onoboaits.com/mypymes</p>
                <p>
                    <a href='https://facebook.com/mypymes'>Facebook</a> | 
                    <a href='https://instagram.com/mypymes'>Instagram</a> | 
                    <a href='https://wa.me/593999999999'>WhatsApp</a>
                </p>
                <p style='font-size: 12px; color: #9CA3AF;'>
                    Este es un email automático, por favor no responder. 
                    Para soporte contacta a training@onoboaits.com
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * Generar texto plano del email de certificado
 */
function generateCertificateEmailText($toName, $certType, $certTypeName, $score) {
    $scoreText = $score ? " con una calificación de " . number_format($score, 0) . "%" : "";
    
    return "
¡Felicitaciones, {$toName}!

Nos complace informarte que has completado exitosamente la capacitación de MyPyMEs{$scoreText}.

{$certTypeName}
Emitido el " . date('d/m/Y') . "

Tu certificado está disponible en la plataforma MyPyMEs.
Accede a: " . BASE_URL . "

Desde tu panel de usuario podrás:
- Ver tu certificado en pantalla
- Descargar tu certificado en formato PDF
- Compartir tu logro

Este certificado valida tus conocimientos en el uso del sistema MyPyMEs y demuestra tu capacidad para gestionar eficientemente las operaciones de tu negocio.

¡Gracias por confiar en MyPyMEs para el crecimiento de tu negocio!

---
MyPyMEs - Tu negocio digital
training.onoboaits.com/mypymes
training@onoboaits.com
    ";
}

/**
 * Enviar email usando PHPMailer (recomendado)
 */
function sendEmailPHPMailer($toEmail, $toName, $subject, $htmlBody, $textBody) {
    try {
        require_once '../vendor/autoload.php'; // Si usas Composer
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Configuración del servidor
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        // Remitente y destinatario
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo(SMTP_FROM, SMTP_FROM_NAME);
        
        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Enviar email usando mail() nativo de PHP
 */
function sendEmailNative($toEmail, $toName, $subject, $htmlBody, $textBody) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    $headers .= "Reply-To: " . SMTP_FROM . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    $success = mail($toEmail, $subject, $htmlBody, $headers);
    
    if (!$success) {
        error_log("Native mail() function failed to send email to {$toEmail}");
    }
    
    return $success;
}

/**
 * Enviar email de bienvenida (opcional)
 */
function sendWelcomeEmail($toEmail, $toName) {
    $subject = 'Bienvenido a MyPyMEs Training';
    
    $htmlBody = "
    <!DOCTYPE html>
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <h2 style='color: #1E40AF;'>¡Bienvenido a MyPyMEs Training, {$toName}!</h2>
        <p>Tu cuenta ha sido creada exitosamente.</p>
        <p>Ahora puedes acceder a todos nuestros módulos de capacitación y obtener certificados al completarlos.</p>
        <p><a href='" . BASE_URL . "' style='background: #1E40AF; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Comenzar Capacitación</a></p>
        <p>¡Éxitos en tu aprendizaje!</p>
        <p><strong>Equipo MyPyMEs</strong></p>
    </body>
    </html>
    ";
    
    $textBody = "¡Bienvenido a MyPyMEs Training, {$toName}!\n\nTu cuenta ha sido creada exitosamente.\n\nAccede a: " . BASE_URL;
    
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return sendEmailPHPMailer($toEmail, $toName, $subject, $htmlBody, $textBody);
    } else {
        return sendEmailNative($toEmail, $toName, $subject, $htmlBody, $textBody);
    }
}
?>