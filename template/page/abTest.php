<?php

use EnterModel as Model;

$f = function ($page) {
    /** @var Model\AbTest $abTest */
?>
<html>
<head>
    <script src="https://yastatic.net/jquery/1.8.3/jquery.min.js"></script>
</head>
<body>
<script type="application/javascript">
$(document).ready(function() {
    $('body').on('change', '.js-abTest-radio', function() {
        $(this).closest('form').submit();
    });
});
</script>

<a href="/"><img src="/img/header/logo@2.png" alt="Enter" /></a>

<form method="post" action="/switch">
    <ul>
        <? foreach ($page['abTests'] as $abTest): ?>
        <li>
            <?= $abTest->name ?>
            <ul style="list-style-type: none; padding: 5px 0 10px 0;">
            <? foreach ($abTest->items as $item): ?>
            <li>
                <label>
                    <input class="js-abTest-radio" type="radio" name="abTest[<?= $abTest->token ?>]" value="<?= $item->token ?>"<? if ($abTest->chosenItem->token === $item->token): ?> checked="checked" <? endif ?> />
                    <?= $item->name ?> <?= $item->traffic ?>%
                </label>
            </li>
            <? endforeach ?>
            </ul>
        </li>
        <? endforeach ?>
    </ul>
</form>

</body>
</html>
<? }; return $f;