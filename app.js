
    let generatedOtp = null;

    document.getElementById('sendOtpBtn').addEventListener('click', function() {
        const email = document.getElementById('email').value;
        if (!email) {
            alert('Please enter your email first.');
            return;
        }

        // Generate a 6-digit OTP
        generatedOtp = Math.floor(100000 + Math.random() * 900000);

        // Send OTP to the user's email (this is a mock implementation)
        // In a real-world scenario, you would send this OTP via an email service
        alert(`OTP sent to ${email}. Your OTP is ${generatedOtp}`);

        // Enable the OTP input field
        document.getElementById('otp').disabled = false;
    });

    document.querySelector('form').addEventListener('submit', function(event) {
        const otpInput = document.getElementById('otp').value;

        if (!generatedOtp) {
            alert('Please send the OTP first.');
            event.preventDefault();
            return;
        }

        if (otpInput != generatedOtp) {
            alert('Invalid OTP. Please try again.');
            event.preventDefault();
            return;
        }

        // If OTP is valid, proceed with form submission
        alert('OTP verified successfully!');
    });
