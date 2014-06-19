<?php
$first_section = true;
$accordion_num = 2;
$collapse_num = 1;
?>

<div class='clearfix'></div>

<div class="row-fluid">
    <div class="span3 help-sidebar">
        <ul class="nav nav-tabs nav-stacked affix">

            <?php foreach ($faq_sections as $section_id => $section_name): ?>
                <li class="<?= $first_section ? 'active' : '' ?>">
                    <a href="#<?= $section_id ?>">
                        <i class="icon-arrow-right-3"></i>
                        <?= $section_name ?>
                    </a>
                </li>

                <?php $first_section = false; ?>
            <?php endforeach ?>

            <li>
                <a id="Intercom">
                    <i class="icon-paperplane highlight-alt"></i>
                    Contact Us
                </a>
            </li>
        </ul>
    </div>
    <div class="span9 help-content">
        <div class="accordion">
            <div class="accordion-body collapse in">
                <div class="accordion-inner">
                    <div class="span12">
                        <div class="well span6 text-center">
                            <h4>Learn to Master Your Dashboard</h4>
                            <a href="https://attendee.gotowebinar.com/rt/7480801490469400320"
                               class="button btn btn-large btn-success"
                               target="_blank"
                            >
                                Signup for our Webinar
                            </a>
                        </div>
                        <div class="well span6 text-center">
                            <h4>Demo our Agency or Enterprise Features</h4>
                            <a href="https://tailwindapp.wufoo.com/forms/tailwind-enterprise-partner-inquiry/"
                               class="button btn btn-large btn-success"
                               target="_blank"
                            >
                                Contact Sales
                            </a>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="accordion">
            <div class="accordion-body collapse in">
                <div class="accordion-inner">

                    <div class="span12">

                    <?php foreach ($faq_sections as $section_id => $section_name): ?>
                        <section id="<?= $section_id ?>" class="faq-section">
                            <div class="page-header">
                                <h2><?= $section_name ?></h2>
                            </div>
                            <div class="accordion" id="accordion<?= $accordion_num ?>">
                            <?php foreach ($questions[$section_id] as $question_id => $question_text): ?>
                                <?php $answer_id = 'a' . substr($question_id, 1); ?>
                                <?php if (empty($answers[$section_id][$answer_id])) continue; ?>

                                <div class="accordion-group">
                                    <div class="accordion-heading">
                                        <a class="accordion-toggle"
                                           data-toggle="collapse"
                                           data-parent="#accordion<?= $accordion_num ?>"
                                           href="#collapse<?= $collapse_num ?>"
                                        >
                                            <?= $question_text ?>
                                        </a>
                                    </div>
                                    <div id="collapse<?= $collapse_num ?>"class="accordion-body collapse">
                                        <div class="accordion-inner">
                                            <?= $answers[$section_id][$answer_id] ?>
                                        </div>
                                    </div>
                                </div>

                                <?php $collapse_num++; ?>
                            <?php endforeach ?>
                            </div>
                        </section>

                        <?php $accordion_num++; ?>
                    <?php endforeach ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row-fluid">
    <div class="accordion">
        <div class="accordion-body collapse in">
            <div class="accordion-inner">
                <div class="span12 text-center faq-messenger">
                    <h2>More questions? Feedback?</h2>
                    <a id="Intercom" class="button btn btn-large btn-success">
                        Send us a Message!
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(window).ready(function(){
        $('.help-sidebar .nav li a').click(function(event) {
            if ($(this).attr('id') != 'Intercom') {
                event.preventDefault();
                $(this).tab('show');
                $($(this).attr('href'))[0].scrollIntoView();
            }
        });
    });
</script>
