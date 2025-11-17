<?php
require_once 'includes/config.php';
require_once 'includes/login.view.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login | Speedy Wheels</title>
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    </noscript>
    <style>
        :root { --primary: #2c3e50; --secondary: #3498db; }
        body { 
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #2c3e50;
        }
        .navbar { background-color: var(--primary); padding: 1rem 0; }
        .navbar-brand { font-weight: bold; color: white; text-decoration: none; }
    </style>
</head>
<body>
    <!-- Background loads after content -->
    <div id="backgroundLoader" style="position:fixed;top:0;left:0;width:100%;height:100%;z-index:-1;background:linear-gradient(135deg, #2c3e50 0%, #3498db 100%);"></div>
    
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-car me-2"></i>Speedy Wheels
            </a>
        </div>
    </nav>

    <div class="d-flex justify-content-center align-items-center" style="min-height: calc(100vh - 76px);">
        <form action="/WE22/carRental/includes/login.php" method="POST" class="centered-form">
            <div class="text-center mb-4">
                <i class="fas fa-user-circle fa-3x text-primary mb-3"></i>
                <h3 class="fw-bold">Employee Login</h3>
            </div>

            <div class="form-floating mb-3">
                <input type="email" name="email" class="form-control" id="floatingInput" placeholder="name@example.com" required>
                <label for="floatingInput"><i class="fas fa-envelope me-2"></i>Email Address</label>
            </div>
            <div class="form-floating mb-4">
                <input type="password" name="password" class="form-control" id="floatingPassword" placeholder="Password" autocomplete="off" required>
                <label for="floatingPassword"><i class="fas fa-lock me-2"></i>Password</label>
            </div>
            <button type="submit" class="btn login-btn text-white w-100 py-2 fw-semibold">
                <i class="fas fa-sign-in-alt me-2"></i>Login to Dashboard
            </button>
        </form>
    </div>

   
    <script>
        // Load background image
        window.addEventListener('load', function() {
            const img = new Image();
            img.src = "Images/B8B4CD98-F217-4D25-9086-FE3AA7BED2D6.JPEG";
            img.onload = function() {
                document.getElementById('backgroundLoader').style.background = 
                    'url("' + img.src + '") no-repeat center -450px fixed';
                document.getElementById('backgroundLoader').style.backgroundSize = 'cover';
            };
            
            // Load form styles
            const style = document.createElement('style');
            style.textContent = `
                .centered-form {
                    background: rgba(255,255,255,0.95);
                    border-radius: 1rem;
                    padding: 2.5rem;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                    width: 100%;
                    max-width: 420px;
                    border: 1px solid rgba(255,255,255,0.2);
                }
                .login-btn {
                    background: linear-gradient(135deg, var(--primary), var(--secondary));
                    border: none;
                    padding: 12px;
                    font-weight: 600;
                    transition: all 0.3s;
                }
                .login-btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                }
                .form-control {
                    border-radius: 8px;
                    padding: 12px;
                    border: 1px solid #dee2e6;
                }
                .form-control:focus {
                    border-color: var(--secondary);
                    box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
                }
            `;
            document.head.appendChild(style);
        });
    </script>

    <?php
    // Execute function that may output error messages
    check_login_error();
    ?>
</body>
</html>