<?php

return function ($page) { ?>

<!DOCTYPE html>
<html>
<head>
    <title><?= $page['id'] ?> - Журнал</title>

    <meta id="js-enter-debug" name="enter-debug" content="<?= $page['dataDebug'] ?>">
    <meta id="js-enter-version" name="enter-version" content="<?//= $page['dataVersion'] ?>">
    <meta id="js-enter-module" name="enter-module" content="default">

    <link rel="shortcut icon" href="/favicon.ico">

    <link rel="stylesheet" href="/css/global.min.css">

    <script data-main="/js/main.js" src="/js/vendor/require-2.1.14.js"></script>
</head>
<body>
<h1><?= $page['id'] ?></h1>

<p><?= $page['date'] ?></p>

<ul>
    <? foreach ($page['messages'] as $message): ?>
        <li style="background: <?= $message['color'] ?>">
            <hr />
            <a href="/log/<?= $page['id'] ?>#log-<?= $message['id'] ?>">&#35; ссылка</a>
            <pre id="log-<?= $message['id'] ?>"><?= $message['value'] ?></pre>
        </li>
    <? endforeach ?>
</ul>

</body>
</html>

<? } ?>
