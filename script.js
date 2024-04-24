const signUpButton = document.getElementById('signUpButton');
const signInButton = document.getElementById('signInButton'); // Ensure this button exists or remove if not used
const signInForm = document.getElementById('signIn');
const signUpForm = document.getElementById('signup'); // Ensure this form exists in your HTML

signUpButton.addEventListener('click', function() {
    signInForm.style.display = "none";
    signUpForm.style.display = "block";
});

if (signInButton) { // This check is necessary to avoid errors if signInButton is not defined
    signInButton.addEventListener('click', function() {
        signInForm.style.display = "block";
        signUpForm.style.display = "none";
    });
}
