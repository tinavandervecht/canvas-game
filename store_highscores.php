<?php
    if ($_SERVER['HTTP_TOKEN'] && $_SERVER['HTTP_TOKEN'] === $_COOKIE['_token']
        && $_SERVER["REMOTE_ADDR"] === '23.229.197.100') {
        $highscores = file_get_contents('./highscores.json');
        $highscores = json_decode($highscores, true);
        $highscores[count($highscores)] = $_POST;

        file_put_contents('./highscores.json', json_encode($highscores));
    }
?>
