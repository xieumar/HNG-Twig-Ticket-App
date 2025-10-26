<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TicketApp</title>
    <link rel="icon" href="/public/favicon.svg" type="image/svg+xml">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Confirmation Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 999;
        }
        .modal-content {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            max-width: 400px;
            width: 90%;
        }
    </style>
</head>
<body>
    <div class="max-w-[1440px] mx-auto">
    <div id="confirmation-modal" class="modal-overlay hidden">
        <div class="modal-content">
            <h3 id="modal-title" class="text-xl font-bold mb-4"></h3>
            <p id="modal-message" class="mb-6"></p>
            <div class="flex justify-end space-x-4">
                <button id="modal-cancel-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Cancel</button>
                <button id="modal-confirm-btn" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">Confirm</button>
            </div>
        </div>
    </div>
<?php

session_start();

require_once __DIR__ . '/vendor/autoload.php';

use App\JsonDataManager;

$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader);

$dataManager = new JsonDataManager(__DIR__ . '/data');

// Get current user from session
$current_user = $_SESSION['user'] ?? null;
$is_logged_in = (bool)$current_user;

// Function to redirect
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Basic routing
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Remove leading/trailing slashes for consistent matching
$path = trim($path, '/');

// Handle POST requests for forms
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($path) {
        case 'signup':
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $users = $dataManager->readData('users');

            // Simple validation
            if (empty($name) || empty($email) || empty($password)) {
                // Handle error: all fields required
                $_SESSION['error'] = 'All fields are required.';
                redirect('/signup');
            }

            // Check if user already exists
            foreach ($users as $user) {
                if ($user['email'] === $email) {
                    $_SESSION['error'] = 'User with this email already exists. Please log in.';
                    redirect('/auth/login');
                }
            }

            // Create new user
            $newUser = [
                'id' => uniqid(), // Simple unique ID
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_BCRYPT) // Hash password
            ];
            $users[] = $newUser;
            $dataManager->writeData('users', $users);

            $_SESSION['user'] = ['id' => $newUser['id'], 'name' => $newUser['name'], 'email' => $newUser['email']];
            $_SESSION['success'] = 'Account created successfully!';
            redirect('/dashboard');
            break;

        case 'auth/login':
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $users = $dataManager->readData('users');

            // Simple validation
            if (empty($email) || empty($password)) {
                $_SESSION['error'] = 'Email and password are required.';
                redirect('/auth/login');
            }

            $authenticatedUser = null;
            foreach ($users as $user) {
                if ($user['email'] === $email && password_verify($password, $user['password'])) {
                    $authenticatedUser = $user;
                    break;
                }
            }

            if ($authenticatedUser) {
                $_SESSION['user'] = ['id' => $authenticatedUser['id'], 'name' => $authenticatedUser['name'], 'email' => $authenticatedUser['email']];
                $_SESSION['success'] = 'Logged in successfully!';
                redirect('/dashboard');
            } else {
                $_SESSION['error'] = 'Invalid email or password.';
                redirect('/auth/login');
            }
            break;

        case 'tickets/delete': // This will handle POST requests for deletion
            // Protect this action
            if (!$is_logged_in) {
                $_SESSION['error'] = 'Please log in to delete tickets.';
                redirect('/auth/login');
            }

            $ticket_id = $_POST['id'] ?? null;

            if ($ticket_id) {
                $tickets = $dataManager->readData('tickets');
                $initial_count = count($tickets);
                $tickets = array_filter($tickets, fn($ticket) => $ticket['id'] !== $ticket_id);

                if (count($tickets) < $initial_count) {
                    $dataManager->writeData('tickets', $tickets);
                    $_SESSION['success'] = 'Ticket deleted successfully!';
                }
            } else {
                $_SESSION['error'] = 'No ticket ID provided for deletion.';
            }
            redirect('/tickets');
            break;

        case 'tickets/submit':
            // Protect this action
            if (!$is_logged_in) {
                $_SESSION['error'] = 'Please log in to submit tickets.';
                redirect('/auth/login');
            }

            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $status = $_POST['status'] ?? 'open';
            $ticket_id = $_POST['id'] ?? null; // For updating existing ticket

            $tickets = $dataManager->readData('tickets');

            // Simple validation
            if (empty($title)) {
                $_SESSION['error'] = 'Ticket title is required.';
                redirect('/tickets'); // Redirect back to the form or list
            }

            if ($ticket_id) {
                // Update existing ticket
                $found = false;
                foreach ($tickets as &$ticket) {
                    if ($ticket['id'] === $ticket_id) {
                        $ticket['title'] = $title;
                        $ticket['description'] = $description;
                        $ticket['status'] = $status;
                        $found = true;
                        break;
                    }
                }
                if ($found) {
                    $dataManager->writeData('tickets', $tickets);
                    $_SESSION['success'] = 'Ticket updated successfully!';
                } else {
                    $_SESSION['error'] = 'Ticket not found for update.';
                }
            } else {
                // Create new ticket
                $newTicket = [
                    'id' => uniqid(),
                    'title' => $title,
                    'description' => $description,
                    'status' => $status,
                    'createdAt' => date('Y-m-d H:i:s'),
                    'createdBy' => $current_user['email'] ?? 'anonymous'
                ];
                $tickets[] = $newTicket;
                $dataManager->writeData('tickets', $tickets);
                $_SESSION['success'] = 'Ticket created successfully!';
            }
            redirect('/tickets');
            break;
    }
}

// Handle GET requests (page rendering)
// Process flash messages
$flash_messages = [
    'success' => $_SESSION['success'] ?? null,
    'error' => $_SESSION['error'] ?? null,
];
unset($_SESSION['success'], $_SESSION['error']);


// Define routes
switch ($path) {
    case '':
    case 'home':
        echo $twig->render('homepage.html.twig', array_merge(['is_logged_in' => $is_logged_in, 'current_user' => $current_user], $flash_messages));
        break;
    case 'tickets':
        // Protect this route
        if (!$is_logged_in) {
            $_SESSION['error'] = 'Please log in to view tickets.';
            redirect('/auth/login');
        }
        // Placeholder for real tickets data
        $tickets_data = $dataManager->readData('tickets');

        echo $twig->render('tickets.html.twig', array_merge(['is_logged_in' => $is_logged_in, 'current_user' => $current_user, 'tickets' => $tickets_data], $flash_messages));
        break;
    case 'dashboard':
        // Protect this route
        if (!$is_logged_in) {
            $_SESSION['error'] = 'Please log in to view the dashboard.';
            redirect('/auth/login');
        }

        // Placeholder for dynamic chart data (will improve on this)
        $tickets = $dataManager->readData('tickets'); // Assume some tickets data for calculations
        $totalTickets = count($tickets);
        $openTickets = count(array_filter($tickets, fn($t) => $t['status'] === 'open'));
        $inProgressTickets = count(array_filter($tickets, fn($t) => $t['status'] === 'in_progress'));
        $closedTickets = count(array_filter($tickets, fn($t) => $t['status'] === 'closed'));
        
        $dashboard_data = [
            'totalTickets' => $totalTickets,
            'openTickets' => $openTickets,
            'inProgressTickets' => $inProgressTickets,
            'closedTickets' => $closedTickets,
            // Sample over time data simplified for now
            'overTimeLabels' => ['Jan', 'Feb', 'Mar'],
            'overTimeOpenData' => [1, 2, 3],
            'overTimeInProgressData' => [0, 1, 1],
            'statusDistributionData' => [$openTickets, $inProgressTickets, $closedTickets],
        ];
        echo $twig->render('dashboard.html.twig', array_merge(['is_logged_in' => $is_logged_in, 'current_user' => $current_user, 'tickets' => $tickets], $flash_messages, $dashboard_data));
        break;
    case 'auth/login':
        // If already logged in, redirect to dashboard
        if ($is_logged_in) {
            redirect('/dashboard');
        }
        echo $twig->render('login.html.twig', array_merge(['is_logged_in' => $is_logged_in], $flash_messages));
        break;
    case 'signup':
        // If already logged in, redirect to dashboard
        if ($is_logged_in) {
            redirect('/dashboard');
        }
        echo $twig->render('signup.html.twig', array_merge(['is_logged_in' => $is_logged_in], $flash_messages));
        break;
    case 'logout':
        // If it's a GET request, show the logout confirmation page
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            echo $twig->render('logout.html.twig', array_merge(['is_logged_in' => $is_logged_in], $flash_messages));
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // If it's a POST request, perform the actual logout
            session_destroy();
            redirect('/');
        }
        break;
    default:
        // Handle ticket details, assuming /tickets/{id}
        if (preg_match('/^tickets\/(\d+)$/', $path, $matches)) {
            // Protect this route
            if (!$is_logged_in) {
                $_SESSION['error'] = 'Please log in to view ticket details.';
                redirect('/auth/login');
            }
            $ticket_id = $matches[1];
            
            // Placeholder for real ticket details
            $tickets_data = $dataManager->readData('tickets');
            $ticket = null;
            foreach($tickets_data as $t) {
                if ($t['id'] == $ticket_id) {
                    $ticket = $t;
                    break;
                }
            }

            if ($ticket) {
                echo $twig->render('ticket_details.html.twig', array_merge(['is_logged_in' => $is_logged_in, 'current_user' => $current_user, 'ticket' => $ticket], $flash_messages));
            } else {
                 header("HTTP/1.0 404 Not Found");
                 echo $twig->render('404.html.twig', array_merge(['is_logged_in' => $is_logged_in], $flash_messages));
            }
        } else {
            // 404 Not Found
            header("HTTP/1.0 404 Not Found");
            echo $twig->render('404.html.twig', array_merge(['is_logged_in' => $is_logged_in], $flash_messages));
        }
        break;
}

?>
    <script>
        // JavaScript for Confirmation Modal
        let resolveConfirmation;

        function showConfirmation(title, message, confirmButtonText = 'Confirm', cancelButtonText = 'Cancel') {
            const modal = document.getElementById('confirmation-modal');
            const modalTitle = document.getElementById('modal-title');
            const modalMessage = document.getElementById('modal-message');
            const confirmBtn = document.getElementById('modal-confirm-btn');
            const cancelBtn = document.getElementById('modal-cancel-btn');

            if (!modal || !modalTitle || !modalMessage || !confirmBtn || !cancelBtn) {
                console.error('Confirmation modal elements not found.');
                return Promise.resolve(false); // Fallback
            }

            modalTitle.textContent = title;
            modalMessage.textContent = message;
            confirmBtn.textContent = confirmButtonText;
            cancelBtn.textContent = cancelButtonText;

            modal.classList.remove('hidden');

            return new Promise(resolve => {
                resolveConfirmation = resolve;

                confirmBtn.onclick = () => {
                    modal.classList.add('hidden');
                    resolveConfirmation(true);
                };

                cancelBtn.onclick = () => {
                    modal.classList.add('hidden');
                    resolveConfirmation(false);
                };
            });
        }

        // Mobile Navbar Toggle
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.getElementById('navbar-toggle');
            const navbarMenu = document.getElementById('navbar-menu');

            if (toggleButton && navbarMenu) {
                toggleButton.addEventListener('click', function() {
                    navbarMenu.classList.toggle('hidden');
                });
            }

            // Auto-hide flash messages after 3 seconds
            const flashMessages = document.querySelectorAll('[role="alert"]');
            flashMessages.forEach(message => {
                setTimeout(() => {
                    message.classList.add('hidden');
                }, 3000);
            });
        });
    </script>
    </div> <!-- Closing div for max-w-[1440px] mx-auto -->
</body>
</html>