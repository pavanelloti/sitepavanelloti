<?php $this->layout("_theme", ["head"=>$head]); ?>

<article class="optin_page">
    <div class="container content">
        <div class="optin_page_content">
            <img alt="<?= $data->title; ?>" title="<?= $data->title; ?>"
                 src="<?= $data->image; ?>"/>

            <h1><?= $data->title; ?></h1>
            <p><?= $data->desc; ?></p>
            <?php if(!empty($data->link)): ?>
                <a href="<?= $data->link; ?>" title="Logar-se" class="optin_page_btn gradient gradient-green gradient-hover radius"><?= $data->linkTitle ?></a>
            <?php endif; ?>
        </div>
    </div>
</article>