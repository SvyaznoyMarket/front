<?php

return function ($page) { ?>

    <!DOCTYPE html>
    <html>
    <head>
        <title>Настройки</title>

        <link rel="shortcut icon" href="/favicon.ico">

        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap.min.css">
    </head>
    <body>

    <div class="container">

        <h1><?= $page['title'] ?></h1>

        <? if ($form = @$page['updateForm']): ?>
            <form role="form" action="<?= $form['action'] ?>" method="post">

                <div class="form-group">
                    <label for="updateForm-field-config">Содержание</label>
                    <code>
                    <textarea id="updateForm-field-config" rows="20" class="form-control" name="<?= $form['field']['config']['name'] ?>">
<?= $form['field']['config']['value'] ?>
                    </textarea>
                    </code>
                </div>

                <button type="submit">Применить</button>
            </form>
        <? endif ?>

        <? if ($form = @$page['resetForm']): ?>
            <form role="form" action="<?= $form['action'] ?>" method="get">
                <button type="submit">Сбросить</button>
            </form>
        <? endif ?>

    </div>

    </body>
    </html>

<? } ?>
