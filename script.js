document.addEventListener('DOMContentLoaded', () => {
    const loginContainer = document.getElementById('login-container');
    const registerContainer = document.getElementById('register-container');
    const showRegisterLink = document.getElementById('show-register');
    const showLoginLink = document.getElementById('show-login');
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    const REDIRECT_TO_LOGIN_PAGE = false; // Set to true if you want to go to login.html after registration

    // Toggle to Register Form
    if (showRegisterLink) {
        showRegisterLink.addEventListener('click', (e) => {
            e.preventDefault();
            loginContainer.style.display = 'none';
            registerContainer.style.display = 'block';
        });
    }

    // Toggle to Login Form
    if (showLoginLink) {
        showLoginLink.addEventListener('click', (e) => {
            e.preventDefault();
            registerContainer.style.display = 'none';
            loginContainer.style.display = 'block';
        });
    }

    // LOGIN SUBMIT
    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const loginData = {
                email: document.getElementById('login_email').value,
                password: document.getElementById('login-password').value
            };

            fetch('login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(loginData)
            })
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    alert("Login successful!");
                    window.location.href = 'dashboard.html'; // Redirect after login
                } else {
                    alert("Login failed: " + response.error);
                }
            })
            .catch(err => {
                console.error("Login error:", err);
                alert("Login failed due to a server error.");
            });
        });
    }

    // REGISTER SUBMIT
    if (registerForm) {
        registerForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const idInput = document.getElementById('id');
            const firstNameInput = document.getElementById('firstName');
            const lastNameInput = document.getElementById('lastName');
            const emailInput = document.getElementById('register_email');
            const phoneInput = document.getElementById('phoneNumber');
            const passwordInput = document.getElementById('password');
            const submitBtn = registerForm.querySelector('button[type="submit"]');

            // Basic validation
            if (!idInput || !firstNameInput || !lastNameInput || !emailInput || !phoneInput || !passwordInput) {
                alert("One or more form fields are missing.");
                return;
            }

            // In your registerForm event listener:
const registerData = {
    id: document.getElementById('id').value.trim(),
    firstName: document.getElementById('firstName').value.trim(),
    lastName: document.getElementById('lastName').value.trim(),
    email: document.getElementById('register_email').value.trim(),
    phoneNumber: document.getElementById('phoneNumber').value.trim() || '', // Optional field
    password: document.getElementById('password').value
};

// Add validation before sending
if (!registerData.id || !registerData.firstName || !registerData.lastName || 
    !registerData.email || !registerData.password) {
    alert('Please fill all required fields');
    return;
}

            // Disable button and show progress
            submitBtn.disabled = true;
            submitBtn.textContent = "Registering...";

            fetch('register.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify(registerData)
})

            .then(async response => {
                const text = await response.text();

                try {
                    const json = JSON.parse(text);

                    if (response.ok) {
                        alert(json.message || "Registered successfully!");
                        registerForm.reset();

                        if (REDIRECT_TO_LOGIN_PAGE) {
                            window.location.href = 'login.html';
                        } else {
                            registerContainer.style.display = 'none';
                            loginContainer.style.display = 'block';
                        }

                    } else {
                        alert(json.error || "Registration failed.");
                    }
                } catch (err) {
                    console.error("Unexpected response:", text);
                    alert("Server error or unexpected response.");
                }
            })
            .catch(error => {
                console.error("Fetch error:", error);
                alert("Something went wrong. Try again.");
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = "Register";
            });
        });
    }
});
