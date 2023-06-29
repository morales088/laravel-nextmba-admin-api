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
                <h1>New Student Account Created</h1>
            </div>

            <div class="content">
                <p>Dear Admin,</p>

                <p>A new student account has been created. The student's account credentials are as follows:</p>

                <p>
                    <strong>Email:</strong> {{ $email }}<br>
                </p>

                <p>Please ensure that the student is provided with these credentials and assist them as needed.</p>

                <p>Thank you for your attention to this matter.</p>
                <p>Regards, NEXT MBA</p>
            </div>
        </div>
    </body>
</html>
