<?php
// Set your API key here
$apiKey = "2f069fd5df69915753510caa24766538";
$weatherData = null;
$error = "";

if (isset($_GET['city']) && !empty($_GET['city'])) {
    $city = urlencode($_GET['city']);
    $apiUrl = "https://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$apiKey}&units=metric";

    // Fetch the weather data
    $response = @file_get_contents($apiUrl);

    if ($response !== false) {
        $weatherData = json_decode($response, true);
        if ($weatherData['cod'] != 200) {
            $error = "City not found.";
        }
    } else {
        $error = "Unable to connect to the weather service.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Weather App</title>
    <style>
        body { font-family: Arial; text-align: center; padding: 50px; }
        input[type="text"] { padding: 10px; width: 250px; }
        input[type="submit"] { padding: 10px 20px; }
        .weather { margin-top: 20px; }
    </style>
</head>
<body>

<h1>Check Weather</h1>
<form method="get" action="">
    <input type="text" name="city" placeholder="Enter city name" required>
    <input type="submit" value="Get Weather">
</form>

<div class="weather">
    <?php if ($error): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php elseif ($weatherData): ?>
        <h2><?php echo htmlspecialchars($weatherData['name']); ?> Weather</h2>
        <p><strong>Temperature:</strong> <?php echo $weatherData['main']['temp']; ?> °C</p>
        <p><strong>Condition:</strong> <?php echo $weatherData['weather'][0]['description']; ?></p>
        <p><strong>Humidity:</strong> <?php echo $weatherData['main']['humidity']; ?>%</p>
        <p><strong>Wind Speed:</strong> <?php echo $weatherData['wind']['speed']; ?> m/s</p>
    <?php endif; ?>
</div>

</body>
</html>