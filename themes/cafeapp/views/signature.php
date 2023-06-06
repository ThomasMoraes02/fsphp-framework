<form action="<?= $action; ?>" method="post" class="app_form">
    <?php
    if ($plans): ?>
        <div class="label_check al-center">
            <?php
            $checked = 0;
            foreach ($plans as $plan):
                $checked++;
                ?>
                <label class="<?= ($checked == 1 ? "check" : ""); ?>" data-checkbox="true">
                    <input type="radio" name="plan"
                           value="<?= $plan->id; ?>" <?= ($checked == 1 ? "checked" : ""); ?> >
                    <?= $plan->name; ?> R$ <?= str_price($plan->price); ?>/<?= $plan->period_str; ?>
                </label>
            <?php
            endforeach; ?>
        </div>
    <?php
    endif; ?>

    <p style="margin: 20px 0">Seus dados:</p>

    <div class="label_group">
        <label>
            <span class="field">Seu CPF:</span>
            <input class="mask-doc radius" name="document" type="text" required
                   placeholder="Apenas números" value="<?= user()->document ?? ""; ?>"/>
        </label>

        <label>
            <span class="field">Seu telefone:</span>
            <input class="mask-mobile-phone radius" name="mobile_phone" type="text" required
                   placeholder="Seu telefone com DDD" value="<?= user()->phone ?? ""; ?>"/>
        </label>
    </div>

    <p style="margin: 20px 0">Dados do Cartão:</p>

    <label>
        <span class="field">Número do cartão:</span>
        <input class="radius mask-card" name="card_number" type="tel" required
               placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;"/>
    </label>

    <div class="label_group">
        <label>
            <span class="field">Data de expiração:</span>
            <input class="radius mask-month" name="card_expiration_date" type="text" required
                   placeholder="mm/yyyy"/>
        </label>

        <label>
            <span class="field">CVV:</span>
            <input class="radius" name="card_cvv" type="number" required
                   placeholder="&bull;&bull;&bull;"/>
        </label>
    </div>

    <label>
        <span class="field">Nome do títular do cartão:</span>
        <input class="radius" name="card_holder_name" type="text" required
               placeholder="Igual ao impresso no cartão"/>
    </label>

    <p style="margin: 20px 0">Endereço de cobrança:</p>

    <div class="label_group">
        <label>
            <span class="field">CEP:</span>
            <input class="mask-zip radius" name="address_zip" type="text" required
                   placeholder="Apenas números"/>
        </label>

        <label>
            <span class="field">Estado:</span>
            <input class="radius" name="address_state" maxlength="2" type="text" required
                   placeholder="Código UF do seu estado"/>
        </label>
    </div>

    <div class="label_group">
        <label>
            <span class="field">Cidade:</span>
            <input class="radius" name="address_city" type="text" required
                   placeholder="Informe a Cidade"/>
        </label>

        <label>
            <span class="field">Nome da Rua:</span>
            <input class="radius" name="address_street" type="text" required
                   placeholder="Informe o nome da rua"/>
        </label>
    </div>

    <div class="label_group">
        <label>
            <span class="field">Número:</span>
            <input class="radius" name="address_number" type="text" required
                   placeholder="Informe o número"/>
        </label>

        <label>
            <span class="field">Complemento:</span>
            <input class="radius" name="address_line_2" type="text"
                   placeholder="Ex: Casa 05, AP 201"/>
        </label>
    </div>

    <button class="btn radius transition icon-check-square-o"><?= ($btn ?? "Confirmar Pagamento"); ?></button>
</form>