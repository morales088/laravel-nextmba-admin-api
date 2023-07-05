<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Credentials</title>
    <style>
        /* Global styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #F5F5F5;
            color: #333333;
            margin: 0;
            padding: 0;
        }

        /* Email container */
        .email-container {
            font-size: 15px;
            max-width: 600px;
            margin: 0 auto;
            background-color: #FFFFFF;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Header section */
        .header {
            background-color: #FF5500;
            color: #FFFFFF;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        /* Content section */
        .content {
            padding: 20px;
        }

        .content p {
            margin-bottom: 15px;
        }

        .content strong {
            font-weight: bold;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            font-family: Arial, sans-serif;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
            background-color: #FF5600;
            color: #FFFFFF !important;
            border-radius: 4px;
        }

        /* Footer section */
        .footer {
            background-color: #F5F5F5;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 5px 5px;
        }

        .footer p {
            margin: 0;
        }

        /* Responsive styles */
        @media screen and (max-width: 600px) {
            .email-container {
                max-width: 100%;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Account Credentials</h1>
        </div>

        <div class="content">
            <p>Dear User,</p>

            <p>Your account credentials are as follows:</p>

            <p>
                <strong>Email:</strong> {{ $email }}<br>
                <strong>Password:</strong> {{ $password }}
            </p>

            <p>Please keep these credentials secure and do not share them with anyone.</p>

            <p>You can login using the following link:</p>

            <a href="{{ env('PARTNER_FRONTEND_LINK') }}" target="_blank" class="button">LOGIN TO YOUR ACCOUNT</a>
  
            <p>Thank you for using our service!</p>
            <p>Regards, NEXT MBA</p>
        </div>
    </div>
</body>
</html>
