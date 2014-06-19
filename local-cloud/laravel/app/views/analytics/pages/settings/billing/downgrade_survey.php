<?= $settings_navigation; ?>

<div class="row margin-fix">

    <form action="<?= URL::route('billing-downgrade')?>"
          method="POST"
          id="downgrade-survey"
    >

        <legend>What's making you want to downgrade today?</legend>

        <div class="span6">

            <label class="radio">
                <input type="radio"
                       name="reason"
                       id="reason1"
                       class="reason-radio"
                       value="Just wanted to see data once/run a single report"
                       required
                >
                Just wanted to see data once/run a single report
            </label>
            <input type="text"
                   class="span6 reason-text"
                   id="reason1-text"
                   name="reason_text"
                   placeholder="How did you use the data? Why did you only need it once?"
                   disabled
            >

            <label class="radio">
                <input type="radio"
                       name="reason"
                       id="reason2"
                       class="reason-radio"
                       value="Didn't need all of that data for my business/personal account"
                       required
                >
                Didn't need all of that data for my business/personal account
            </label>

            <label class="radio">
                <input type="radio"
                       name="reason"
                       id="reason3"
                       class="reason-radio"
                       value="I wasn't sure how to use the information"
                       required
                >
                I wasn't sure how to use the information
            </label>
            <input type="text"
                   class="span6 reason-text"
                   id="reason3-text"
                   name="reason_text"
                   placeholder="Care to tell us more?"
                   disabled
            >

            <label class="radio">
                <input type="radio"
                       name="reason"
                       id="reason4"
                       class="reason-radio"
                       value="I was testing/considering the product for a future decision"
                       required
                >
                I was testing/considering the product for a future decision
            </label>
            <input type="text"
                   class="span6 reason-text"
                   id="reason4-text"
                   name="reason_text"
                   placeholder="How did you feel about the product?"
                   disabled
            >

            <label class="radio">
                <input type="radio"
                       name="reason"
                       id="reason5"
                       class="reason-radio"
                       value="I no longer need the product"
                       required
                >
                I no longer need the product
            </label>
            <input type="text"
                   class="span6 reason-text"
                   id="reason5-text"
                   name="reason_text"
                   placeholder="Could you let us know why the product is no longer necessary?"
                   disabled
            >

            <label class="radio">
                <input type="radio"
                       name="reason"
                       id="reason6"
                       class="reason-radio"
                       value="Pinterest is not a current focus"
                       required
                >
                Pinterest is not a current focus
            </label>

            <label class="radio">
                <input type="radio"
                       name="reason"
                       id="reason7"
                       class="reason-radio"
                       value="Couldn't afford it"
                       required
                    >
                Didn't fit my budget
            </label>

            <label class="radio">
                <input type="radio"
                       name="reason"
                       id="reason8"
                       class="reason-radio"
                       value="Didn't realize I was being charged"
                       required
                    >
                Didn't realize I was being charged
            </label>

            <label class="radio">
                <input type="radio"
                       name="reason"
                       id="reason9"
                       class="reason-radio"
                       value="Features didn't work as I expected"
                       required
                >
                Features didn't work as I expected
            </label>
            <input type="text"
                   class="span6 reason-text"
                   id="reason9-text"
                   name="reason_text"
                   placeholder="Could you elaborate?"
                   disabled
            >

            <label class="radio">
                <input type="radio"
                       name="reason"
                       id="reason10"
                       class="reason-radio"
                       value="I was looking for a feature that didn't exist"
                       required
                >
                I was looking for a feature that didn't exist
            </label>
            <input type="text"
                   class="span6 reason-text"
                   id="reason10-text"
                   name="reason_text"
                   placeholder="Could you tell us what you were looking for?"
                   disabled
            >

            <label class="radio">
                <input type="radio"
                       name="reason"
                       id="reason11"
                       class="reason-radio"
                       value="Other"
                       required
                >
                Other
            </label>
            <input type="text"
                   class="span6 reason-text"
                   id="reason11-text"
                   name="reason_text"
                   placeholder="Could you tell us more?"
                   disabled
            >

        </div>

        <div class="span4">

            <div class="alert alert-info reason-text hidden" id="reason2-text">
                <p>That's understandable.</p>
                <p>
                    If your needs change in the future, remember you can upgrade to see more helpful
                    data and reports!
                </p>
            </div>

            <div class="alert alert-info reason-text hidden" id="reason6-text">
                <p>That's too bad!</p>
                <p>
                    If Pinterest does become a focus in the future, please remember to come back to
                    Tailwind. We would love to help you improve your Pinterest strategy and maximize
                    your reach!
                </p>
            </div>

            <div class="alert alert-info reason-text hidden" id="reason7-text">
                <p>We're sorry to hear that.</p>
                <p>
                    If your budget changes in the future, please remember you can always upgrade to
                    a more robust plan!
                </p>
            </div>

        </div>

        <div class="clearfix"></div>

        <input type="hidden" name="old_plan" value="<?= $old_plan_id ?>">
        <input type="hidden" name="new_plan" value="<?= $new_plan_id ?>">

        <div class="form-actions">
            <input type="submit" class="btn" value="Downgrade to <?= $new_plan_name ?>">
            <a href="<?= URL::route('billing-cancel-downgrade', array('old_plan' => $old_plan_id, 'new_plan' => $new_plan_id)) ?>"
               class="btn btn-primary"
            >
                Keep My Current Plan
            </a>
        </div>

        <?php if ($new_plan_id == 1): ?>

            <p>
                Note: by downgrading to the free plan, you will no longer be able to access
                historical data or advanced features of the dashboard.
            </p>

        <?php elseif ($new_plan_id == 2): ?>

            <p>
                Note: by downgrading to the lite plan, you will no longer be able to access advanced
                features of the dashboard and your history will be limited to 90 days.
            </p>

        <?php elseif ($new_plan_id == 3): ?>

            <p>
                Note: by downgrading to the pro plan, you will no longer have unlimited access to advanced features of the dashboard and your
                history will be limited to 1 year.
            </p>

        <?php endif ?>
    </form>

</div>

<script type="text/javascript">
    var downgradeSurvey = {
        reasonRadioId : '',

        toggleReasonText : function(id)
        {
            this.reasonRadioId = id;

            var visibleText = $('.reason-text:not(#' + downgradeSurvey.reasonRadioId + '-text)').filter(':visible');
            if (!visibleText.length) {
                this.showSelectedReasonText();
            } else {
                if (visibleText.prop('tagName') == 'INPUT') {
                    visibleText.slideUp(200, function() {
                        visibleText.prop('disabled', true);
                        downgradeSurvey.showSelectedReasonText();
                    });
                } else {
                    visibleText.fadeOut(200, function() {
                        downgradeSurvey.showSelectedReasonText();
                    });
                }
            }
        },

        showSelectedReasonText : function()
        {
            var reasonText = $('#' + downgradeSurvey.reasonRadioId + '-text');
            if (reasonText) {
                if (reasonText.prop('tagName') == 'INPUT') {
                    reasonText.prop('disabled', false);
                    reasonText.slideDown(400, function() {
                        reasonText.focus();
                    });
                } else {
                    reasonText.fadeIn();
                }
            }
        }
    };

    $(window).ready(function() {
        $('.reason-radio').click(function() {
            downgradeSurvey.toggleReasonText($(this).attr('id'));
        });
    });
</script>