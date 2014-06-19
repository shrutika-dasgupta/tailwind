<?php
    // Don't show the modal if the user's preference has already been saved.
    if (array_get($_COOKIE, 'language_preference')) {
        return;
    }

    if (!$language_tag = Request::getPreferredLanguage()) {
        return;
    }

    $language_code = explode('_', $language_tag);
    $language_code = $language_code[0];

    $language_codes = array(
        'es' => 'Español',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português',
    );

    if (!$language = array_get($language_codes, $language_code)) {
        return;
    }
?>

<div id="translationModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="translationModalLabel" aria-hidden="true">
    <div class="modal-header">
        <h3 id="translationModalLabel">Language Preference</h3>
    </div>
    <div class="modal-body">
        <p>Hi <?= $customer->first_name ?>!</p>
        <p>We're testing interest in a multilingual Tailwind app.</p>
        <p>Would you prefer to use the Tailwind app in English or <?= $language ?>?</p>
    </div>
    <div class="modal-footer">
        <button class="btn btn-primary btn-lang" data-dismiss="modal" data-user="<?= $customer->cust_id ?>" data-lang-code="en" data-lang-tag="en">English</button>
        <button class="btn btn-success btn-lang" data-dismiss="modal" data-user="<?= $customer->cust_id ?>" data-lang-code="<?= $language_code ?>" data-lang-tag="<?= $language_tag ?>"><?= $language ?></button>
    </div>
</div>

<div id="translationModalThanks" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="translationModalThanksLabel" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="translationModalThanksLabel">Thank You!</h3>
    </div>
    <div class="modal-body" id="translationModalThanksBody">
        <p>Thank you for your feedback!</p>
    </div>
</div>

<script type="text/javascript">
$(function () {
    $('#translationModal').modal('show');

    // Track language preference.
    $('button.btn-lang').on('click', function () {
        analytics.track(
            'App Language Preference',
            {'User':$(this).data('user'), 'Language Tag':$(this).data('lang-tag')},
            translationModalCallback($(this))
        );
    });

    /**
     * Stores language preference and displays thank you modal.
     *
     * @param DOM Element element
     *
     * @return void
     */
    function translationModalCallback(element)
    {
        setCookie('language_preference', element.data('lang-tag'), 365, '/', 'analytics.tailwindapp.com');

        translationModalThanksLabel = $('#translationModalThanksLabel');
        translationModalThanksBody  = $('#translationModalThanksBody p');

        thanksLabel = '';
        thanksBody  = '';

        langCode = element.data('lang-code');
        if (langCode == 'es') {
            thanksLabel = 'Gracias!';
            thanksBody  = 'Te diremos cuando Tailwind está disponible en Español.';
        } else if (langCode == 'fr') {
            thanksLabel = 'Merci!';
            thanksBody  = 'Nous vous dirons quand Tailwind sera disponible en Français.';
        } else if (langCode == 'de') {
            thanksLabel = 'Danke!';
            thanksBody  = 'Wir informieren Sie, sobald Tailwind in Deutsch verfügbar ist.';
        } else if (langCode == 'it') {
            thanksLabel = 'Grazie!';
            thanksBody  = 'Vi diremo quando Tailwind è disponibile in Italiano.';
        } else if (langCode == 'pt') {
            thanksLabel = 'Obrigado!';
            thanksBody  = 'Nós vamos dizer-lhe quando Tailwind está disponível em Português.';
        }

        if (thanksLabel && thanksBody) {
            translationModalThanksLabel.html(thanksLabel);
            translationModalThanksBody.html(thanksBody);
        }

        $('#translationModalThanks').modal('show');

        setTimeout(function () {
            $('#translationModalThanks').modal('hide');
        }, 5000);
    }
});
</script>