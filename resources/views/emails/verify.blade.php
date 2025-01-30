<!DOCTYPE html>
<html>
<head>
    <title>Account Verification</title>
</head>
<body>
    <h1>Hello, {{ $user->name }}</h1>
    <p>Thank you for registering with our application. Please click the following link to verify your account:</p>
    <a href="{{url('/verify/' . $verification_token)}}">Verify Account</a>
    <p>If you did not create an account, no further action is required.</p>
    <p>Best regards,<br>The BPapp Team</p>
</body>
</html>