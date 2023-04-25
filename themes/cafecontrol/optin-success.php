<?php $this->layout("_theme", ["head"=>$head]); ?>

<article class="optin_page">
    <div class="container content">
        <div class="optin_page_content">
            <img alt="Cadastro com sucesso" title="Cadastro com sucesso"
                 src="<?= theme("/assets/images/optin-success.jpg"); ?>"/>

            <h1>Tudo pronto. Você já pode controlar :)</h1>
            <p>Bem-vindo(a) ao seu controle de contas, vamos tomar um café?</p>
            <a href="<?= url("/entrar"); ?>" title="Logar-se"
               class="optin_page_btn gradient gradient-green gradient-hover radius">Fazer Login</a>
        </div>
    </div>
</article>