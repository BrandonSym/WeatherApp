<?php
$openWeatherApiKey = "2f069fd5df69915753510caa24766538";
$openUvApiKey = "openuv-1rmfhrmcek7rjy-io";

$weatherData = null;
$uvData = null;
$error = "";
$uvWarning = "";

$skinTypes = [
    'normaal' => 7,
    'gemengd' => 6,
    'gevoelig' => 5,
    'droog' => 6,
    'vet' => 7
];

if (isset($_GET['city']) && !empty($_GET['city']) && isset($_GET['skin']) && isset($skinTypes[$_GET['skin']])) {
    $city = urlencode($_GET['city']);
    $selectedSkin = $_GET['skin'];
    $uvThreshold = $skinTypes[$selectedSkin];

    // 1. Weerdata ophalen
    $weatherUrl = "https://api.openweathermap.org/data/2.5/weather?q={$city}&units=metric&appid={$openWeatherApiKey}";
    $weatherResponse = @file_get_contents($weatherUrl);

    if ($weatherResponse !== false) {
        $weatherData = json_decode($weatherResponse, true);

        if ($weatherData['cod'] != 200) {
            $error = "Stad niet gevonden of fout bij het ophalen van het weer.";
        } else {
            $lat = $weatherData['coord']['lat'];
            $lon = $weatherData['coord']['lon'];

            // 2. UV-data ophalen
            $uvContext = stream_context_create([
                "http" => [
                    "method" => "GET",
                    "header" => "x-access-token: {$openUvApiKey}"
                ]
            ]);

            $uvUrl = "https://api.openuv.io/api/v1/uv?lat={$lat}&lng={$lon}";
            $uvResponse = @file_get_contents($uvUrl, false, $uvContext);

            if ($uvResponse !== false) {
                $uvData = json_decode($uvResponse, true);
                $uvValue = $uvData['result']['uv'];

                // Waarschuwing berekenen
                if ($uvValue > $uvThreshold) {
                    $uvWarning = "⚠️ De UV-index is te hoog voor jouw huidtype ({$selectedSkin}). Smeer je om de twee uur in!";
                }
            } else {
                $error .= " UV-data niet beschikbaar.";
            }
        }
    } else {
        $error = "Kan geen verbinding maken met de weerservice.";
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Weer & UV-index vandaag</title>
    <style>
        body { font-family: Arial; text-align: center; padding: 50px; }
        input, select { padding: 10px; margin: 5px; }
        .weather { margin-top: 30px; border: 1px solid #ccc; border-radius: 8px; padding: 20px; display: inline-block; }
        .warning { color: darkred; font-weight: bold; margin-top: 10px; }
    </style>
    <link rel="stylesheet" href="main.css">
</head>
<body>

<h1>Weer & UV-index van vandaag</h1>

<form method="get" action="">
    <input type="text" name="city" placeholder="Voer een stad in" required value="<?php echo isset($_GET['city']) ? htmlspecialchars($_GET['city']) : ''; ?>">
    
    <select name="skin" required>
        <option value="">Kies huidtype</option>
        <?php foreach ($skinTypes as $type => $value): ?>
            <option value="<?php echo $type; ?>" <?php echo (isset($_GET['skin']) && $_GET['skin'] === $type) ? 'selected' : ''; ?>>
                <?php echo ucfirst($type); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <input type="submit" value="Bekijk weer">
</form>

<div class="weather">
    <?php if ($error): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php elseif ($weatherData): ?>
        <h2><?php echo htmlspecialchars($weatherData['name']); ?> - <?php echo strftime("%A %e %B", strtotime("today")); ?></h2>
        <p><strong>Temperatuur:</strong> <?php echo $weatherData['main']['temp']; ?> °C</p>
        <p><strong>Weersomstandigheden:</strong> <?php echo $weatherData['weather'][0]['description']; ?></p>
        <p><strong>Luchtvochtigheid:</strong> <?php echo $weatherData['main']['humidity']; ?>%</p>
        <p><strong>Windsnelheid:</strong> <?php echo $weatherData['wind']['speed']; ?> m/s</p>
        <p><strong>UV-index:</strong>
            <?php
                if ($uvData) {
                    $uv = $uvData['result']['uv'];
                    echo "$uv (" . (
                        $uv < 3 ? "Laag" :
                        ($uv < 6 ? "Matig" :
                        ($uv < 8 ? "Hoog" :
                        ($uv < 11 ? "Zeer hoog" : "Extreem")))
                    ) . ")";
                } else {
                    echo "Niet beschikbaar";
                }
            ?>
        </p>

        <?php if ($uvWarning): ?>
            <p class="warning"><?php echo $uvWarning; ?></p>
        <?php endif; ?>

    <?php endif; ?>
</div>

</body>
</html>
