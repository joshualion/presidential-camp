<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $status }} - {{ $message }}</title>
    <style>
        /* General Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f9;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
        }

        .error-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
        }

        .error-content h1 {
            font-size: 120px;
            color: {{ $SystemSetting ? $SystemSetting->dark_theme_color : '#008751' }} !important;
            margin-bottom: 20px;
        }

        .error-content h2 {
            font-size: 32px;
            margin-bottom: 10px;
            color: #333;
        }

        .error-content p {
            font-size: 18px;
            margin-bottom: 30px;
            color: #666;
        }

        .home-btn {
            display: inline-block;
            padding: 12px 24px;
            font-size: 16px;
            color: #fff;
            background-color: {{ $SystemSetting ? $SystemSetting->dark_theme_color : '#008751' }} !important;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .home-btn:hover {
            background-color: #f4f4f9 !important;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-content">
            <h1>{{ $status }}</h1>
            <h2>{{ $message }}</h2>
            <p>Oops! Something went wrong. The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>
            <a href="{{ url('/') }}" class="home-btn">Go to Homepage</a>
        </div>
    </div>
</body>
</html>