<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

// Obtener el nombre de usuario actual
$current_username = $_SESSION['username'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Proceso en curso</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background-color: white;
            font-family: 'Courier New', monospace;
        }
        .countdown-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: white;
            z-index: 1000;
            flex-direction: column;
            margin: 0;
            padding: 0;
        }
        .countdown-message {
            font-size: 32px;
            text-align: center;
            color: #333;
            margin-bottom: 10px;
            font-family: 'Courier New', monospace;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .countdown-timer {
            font-size: 120px;
            font-weight: bold;
            margin: 20px 0;
            color: #ff0000;
            font-family: 'Courier New', monospace;
            line-height: 1;
            text-shadow: 0 0 10px rgba(255, 0, 0, 0.3);
            animation: pulse 1s infinite alternate;
        }
        @keyframes pulse {
            from { transform: scale(1); }
            to { transform: scale(1.05); }
        }
        .username {
            font-size: 24px;
            color: #333;
            margin-top: 20px;
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="countdown-container">
        <div>
            <div class="countdown-message">
                Borrando base de datos de la empresa
            </div>
            <div class="username">
                BY: <?php echo htmlspecialchars($current_username); ?>
            </div>
            <div class="countdown-timer" id="countdown">10</div>
        </div>
    </div>

    <script>
        // Contador regresivo de 10 segundos
        let seconds = 10;
        const countdownElement = document.getElementById('countdown');
        
        const countdownInterval = setInterval(() => {
            seconds--;
            countdownElement.textContent = seconds;
            
            if (seconds <= 0) {
                clearInterval(countdownInterval);
                // Simple approach with a reliable image URL
                const container = document.querySelector('.countdown-container');
                container.innerHTML = `
                    <div style="text-align: center; margin: 0 auto; max-width: 800px; padding: 20px;">
                        <img src="data:image/jpeg;base64,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" 
                             alt="Success" 
                             style="max-width: 100%; height: auto; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);"
                             onerror="this.onerror=null; this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22800%22%20height%3D%22400%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%23f0f0f0%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20font-family%3D%22Arial%22%20font-size%3D%2220%22%20text-anchor%3D%22middle%22%20dominant-baseline%3D%22middle%22%20fill%3D%22%23666%22%3EImagen%20no%20disponible%3C%2Ftext%3E%3C%2Fsvg%3E';">
                        <div style="margin-top: 30px; font-size: 28px; color: #333; font-weight: bold;">
                            ¡Base de datos borrada exitosamente!
                        </div>
                        <div style="margin-top: 15px; font-size: 20px; color: #666;">
                            BY: <?php echo htmlspecialchars($current_username); ?>
                        </div>
                    </div>`;
            }
        }, 1000);

        // Asegurarse de que el menú lateral esté activo
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarItem = document.querySelector('.sidebar-cotizaciones');
            if (sidebarItem) {
                sidebarItem.classList.add('active');
            }
        });
    </script>
</body>
</html> 