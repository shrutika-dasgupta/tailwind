<?php ini_set('display_errors', 'off');
error_reporting(0);

$page = "Settings";

$customer = User::find($cust_id);

$free_track = "";

/*
 * Plan links
 */
if($cust_is_admin!="V"){
    if ($customer->doesNotHaveCreditCardOnFile()) {
        $append      = '?reference=' . $customer->cust_id . '&first_name=' . $cust_first_name . '&last_name=' .
            $cust_last_name . '&organization=' . $cust_org_name . '&email=' . $cust_email;
        $basic_link  = 'https://tailwind.chargify.com/h/3319111/subscriptions/new' . $append;
        $pro_link    = 'https://tailwind.chargify.com/h/3319112/subscriptions/new' . $append;
        $agency_link = "https://tailwindapp.wufoo.com/forms/tailwind-enterprise-partner-inquiry/";
        $free_link   = false;

        $basic_button       = 'Choose This Plan';
        $basic_button_class = "btn-success";
        $pro_button         = 'Choose This Plan';
        $agency_button      = "Request a Demo";

        $basic_sub_cta  = "14-Day Free Trial";
        $pro_sub_cta    = "14-Day Free Trial";
        $agency_sub_cta = "Or call us: (405) 702-9998";

        $basic_track  = "id=\"chargify_lite_link\"";
        $pro_track    = "id=\"chargify_pro_link\"";
        $agency_track = "";

//        $upgrade_headline    = "Try a premium plan <strong>Free</strong> for 14 days.";
        $upgrade_headline   = "Plans & Pricing";
        $upgrade_subheadline = "Unlock advanced features.  Cancel anytime.";
        $upgrade_button_class      = "no-show";
        $upgrade_button_text      = "";
    } else {
        $prepend     = '/settings/billing/change-plan/';
        $free_link   = $prepend . 'forever-free-pinterest-analytics-plan';
        $basic_link  = $prepend . 'basic-pinterest-analytics-plan';
        $pro_link    = $prepend . 'professional-pinterest-analytics-plan';
        $agency_link = "https://tailwindapp.wufoo.com/forms/tailwind-enterprise-partner-inquiry/";
        if ($customer->plan()->plan_id == 1) {
            $free_link        = false;
            $free_button_link = false;
        }

        $basic_button  = 'Choose This Plan';
        $pro_button    = 'Choose This Plan';
        $agency_button = "Request a Demo";
        if ($customer->plan()->plan_id == 3 || $customer->plan()->plan_id == 4) {
            $basic_button       = "Downgrade to Lite Plan";
            $basic_button_class = "btn-downgrade";
        } else {
            $basic_button       = "Try the Lite Plan";
            $basic_button_class = "btn-success";
        }

        $basic_sub_cta  = "";
        $pro_sub_cta    = "";
        $agency_sub_cta = "Or call us: (405) 702-9998";

        $basic_track  = "";
        $pro_track    = "";
        $agency_track = "";

//        $upgrade_headline    = "Upgrade Your Account to Unlock New Features";
        $upgrade_headline   = "Plans & Pricing";
        $upgrade_subheadline = "Select a plan that Best Fits Your needs";
        $upgrade_button_class      = "no-show";
        $upgrade_button_text      = "";

    }
    $viewer_message_class = "no-show";

} else {
    $basic_link   = false;
    $pro_link     = false;
    $agency_link  = false;
    $free_link    = false;
    $basic_track  = "class='button btn btn-success disabled'";
    $pro_track    = "class='button btn btn-success disabled'";
    $agency_track = "class='button btn btn-success disabled'";
    $free_track   = "class='button btn disabled free-button' style='cursor:default;'";

    $viewer_message_class = "";
}

?>
    <div class='clearfix'></div>
    <div id='pricing' class="active-plan-<?= strtolower($customer->plan()->name); ?>">

    <div class='accordion' id='accordion3' style='margin-bottom:25px'>
    <div class='accordion-group' style='margin-bottom:25px'>
    <div class='accordion-heading'>
        <div class='accordion-toggle' data-parent='#accordion3' href='#collapseTwo' style='cursor:default'>

            <div class="pull-left" style='text-align:left;'>
                <div class="" style='text-align:right;margin-right:15px'>

                </div>
            </div>

            <div class="pull-right">

            </div>

        </div>
    </div>

    <div class='clearfix section-header'></div>
    <div id='collapseTwo' class='accordion-body collapse in'>
    <div class='accordion-inner'>
    <div class="row-fluid" style='margin-bottom:-10px;'>

        <?php if ($customer->organization()->is_legacy && $customer->plan()->plan_id == 3 && $customer->type != "DEMO") { ?>
            <div class="alert alert-info ">
                <button type="button" class="close"
                        data-dismiss="alert">&times;</button>
                <strong>Important!</strong>
                Your account is currently grandfathered in under a
                subscription plan and price that is no longer offered. If you
                change your plan, you will be migrated to a current plan and
                price, and we will not be able to reactivate your current plan.
            </div>
        <?php } ?>
        <?php if ($customer->organization()->is_legacy && $customer->plan()->plan_id == 2 && $customer->type != "DEMO") { ?>
            <div class="alert alert-info ">
                <button type="button" class="close"
                        data-dismiss="alert">&times;</button>
                <strong>Important!</strong>
                Important! Your account is grandfathered in under a subscription
                plan and price that is no longer offered. If you downgrade your
                account to Free, we will be unable to reactivate your current
                plan in the future.
            </div>
        <?php } ?>

        <div class="alert alert-info <?=$viewer_message_class;?>">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong>NOTE:</strong>
            You do not currently have permissions to make changes to subscription settings.
            If you need Admin rights, only please contact the person who invited you to request
            a role change.
        </div>

        <?php if ($trial_days_left > 0): ?>
            <div class="alert alert-info">
                You still have <?= $trial_days_left?> days left in your trial. You can
                upgrade or downgrade to any plan without being charged until
                <?= $pretty_trial_end_date ?>.
            </div>
        <?php endif ?>

        <div class="span9 columns sub-hero">
            <h2 style="font-weight:normal;"><?=$upgrade_headline;?></h2>

<!--            <h3><span style="font-weight:normal">--><?//=$upgrade_subheadline;?><!--</span></h3>-->
        </div>
    </div>
    <div class="row-fluid">


    <div class="span12 pricing-table">

    <div class="pricing-wrapper">
    <!-- Basic -->
    <div class="row" style="margin-left:4.2%;">
    <div id="basic">
        <div class="span3 price-badge">
            <div>
                <h4 class="price"><sup>$</sup>149<span
                        class="per-month">/month</span></h4>
                <h5 class="highlight-imp-grey">Lite</h5>
                <h6>For small businesses</h6>
                <a href="<?= $basic_link; ?>"
                    <?= $basic_track; ?> class="button btn <?=$basic_button_class;?>" >
                    <?= $basic_button;?></a>
                <button class="button btn" disabled="disabled">You Have This Plan
                </button>
                <ul class="plan-details">
                    <li>
                            <span class="tip has-tip tip-right" data-toggle="popover" data-container="body"
	data-placement="right" data-content="<span class='tip-highlight'>Measure and Benchmark performance across time</span>
                                  <br>for Followers, Repins, Virality
                                  <br>and much more for your:
                                  <ul><li>Brand Page (Profile)</li>
                                  <li>Individual Boards</li></ul>">
                                <strong>Full Profile & Board Reporting</strong> 
                            </span>
                    </li>
                    <li>
                            <span class="tip has-tip tip-right" data-toggle="popover" data-container="body"
	data-placement="right" data-content="<span class='tip-highlight'>Track Potential Impressions</span> from what people
                                   <br>have pinned from your domain.">
                                Track Organic Impressions 
                            </span>
                    </li>
                    <li>
                            <span class="tip has-tip tip-right" data-toggle="popover" data-container="body"
	data-placement="right" data-content="Uncover trending pins receiving the most engagement
                                   <br>over a given period of time.">
                                Analyze Trending Pins
                            </span>
                    </li>
                    <li>
                            <span class="tip has-tip tip-right" data-toggle="popover" data-container="body"
	data-placement="right" data-content="View your <span class='tip-highlight'>top-line ROI across Pinterest</span>,
                                  <br>including Traffic, Conversions and Revenue.
                                  <br>(Google Analytics req'd)">
                                Measure Pinterest ROI 
                            </span>
                    </li>
                    <li>
                            <span >
                                &nbsp;
                            </span>
                    </li>
                    <li>
                            <span >
                                &nbsp;
                            </span>
                    </li>
                    <li style="border-bottom:1px solid rgba(0,0,0,0.1);">
                            <span >
                                &nbsp;
                            </span>
                    </li>
                </ul>
            </div>
            <div>
                <ul class="plan-details">
                    <li>
                            <span class="tip has-tip tip-right" data-toggle="popover" data-container="body"
	data-placement="right" data-content="Archive up to <span class='tip-highlight'>90 Days of Historical Data</span> on
                                         <br>all dashboard metrics.">
                                <strong>90-Day</strong> History Archive 
                            </span>
                    </li>
                    <li>
                            <span class="tip has-tip tip-right" data-toggle="popover" data-container="body"
	data-placement="right" data-content="<span class='tip-highlight'>Export your dashboard data to .CSV</span>,
                                  <br>and take your data where ever you need it to go.">
                                <strong>Export Data</strong> 
                            </span>
                    </li>
                    <li>
                            <span class="tip has-tip tip-right" data-toggle="popover" data-container="body"
	data-placement="right" data-content="Invite up to 2 colleagues, co-workers or clients
                                    <br>to access your dashboard with their own login.">
                                2 Collaborators 
                            </span>
                    </li>
                    <li>
                            <span class="tip has-tip tip-right" data-toggle="popover" data-container="body"
	data-placement="right" data-content="1 Primary Admin user + 2 collaborators with
                                  Viewer access.">
                                Basic Roles & Permissions 
                            </span>
                    </li>

                    <li>
                            <span >
                                &nbsp;
                            </span>
                    </li>
                    <li>
                            <span >
                                &nbsp;
                            </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Professional -->
    <div id="professional">
        <div class="span3 price-badge">
            <div>
                <!--                                        <h6 class="sale"><s>$129/month</s></h6>-->
                <h4 class="price larger"><sup>$</sup>399<span
                        class="per-month">/month</span>
                </h4>
                <h5 class="highlight-imp">Professional</h5>
                <h6>For mid-sized businesses</h6>
                <a href="<?= $pro_link; ?>"
                    <?= $pro_track; ?> class="button btn btn-success"  >
                    <?= $pro_button;?></a>
                <button class="button btn" disabled="disabled">You Have This Plan
                </button>
                <ul class="plan-details">
                    <!--                        <li>-->
                    <!--                            <span class="tip has-tip tip-right" data-tooltip-->
                    <!--                                  title="Track performance for followers, repins, virality and much more across time.">-->
                    <!--                                Daily Pin Tracking-->
                    <!--                            </span>-->
                    <!--                        </li>-->


                    <li>
                            <span class="tip has-tip tip-right" data-toggle="popover" data-container="body"
	data-placement="right" data-content="<span class='tip-highlight'>Figure out what's working</span>,
                                  and what's not.
                                  <br>Uncover your <span class='tip-highlight'>most valuable content</span> and
                                  <br>identify the <span class='tip-highlight'>best days and times</span>
                                  <br>to engage your audience.">
                                <strong>Optimize Content Strategy</strong> 
                            </span>
                    </li>
                    <li>
                            <span class="tip has-tip tip-right" data-toggle="popover" data-container="body"
	data-placement="right" data-content="Leverage <span class='tip-highlight'>Pixel Matching</span> technology to
                                   <br>reveal the most popular images from your domain,
                                   <br>analyze how how Impressions are being generated, and
                                   <br><span class='tip-highlight'>monitor Comments & Conversations</span>
                                   <br>related to your brand across Pinterest.">
                                <strong>Brand Monitoring Suite</strong> 
                            </span>
                    </li>
                    <li>
                            <span class="tip has-tip tip-right" data-toggle="popover" data-container="body"
	data-placement="right" data-content="Analyze and Connect with your
                                    <br><span class='tip-highlight'>most active Brand Pinners</span>,
                                    <br><span class='tip-highlight'>most Influential Followers</span>,
                                    <br>and <span class='tip-highlight'>Top Repinners</span>.">
                                <strong>Manage Your Community</strong>
                            </span>
                    </li>
                    <li>
                            <span class="tip has-tip tip-right" data-toggle="popover" data-container="body"
	data-placement="right" data-content="<span class='tip-highlight'>Sync Google Analytics</span>
                                  to monitor your
                                  <br>most valuable content and relationships.
                                  <br>Target pins and pinners which are
                                  <br>driving the most traffic and revenue
                                  <br>from Pinterest.">
                                Analyze Sources of ROI
                            </span>
                    </li>
                    <li>
                            <span class="tip has-tip tip-right" data-toggle="popover" data-container="body"
	data-placement="right" data-content="Benchmark your progress against competitors'
                                  <br>Pinterest profiles and domains.">
                                Benchmark vs. Competitors
                            </span>
                    </li>
                    <li>
                            <span>
                                <i class="icon-plus" style="font-size:11px;"></i> All Lite Features
                            </span>
                    </li>
                    <li style="border-bottom:1px solid rgba(0,0,0,0.1);">
                            <span >
                                &nbsp;
                            </span>
                    </li>
                </ul>
            </div>
            <div>
                <ul class="plan-features">
                    <li>
                            <span class="tip has-tip tip-right" data-toggle="popover" data-container="body"
	data-placement="right" data-content="Archive up to <span class='tip-highlight'>1 Year of Historical Data</span> on
                                         <br>all dashboard metrics.
                                         <br>
                                         <br>
                                         History before your signup date<br />may be estimated.
                                         <br>
                                         <br>Sign up and <span class='tip-highlight'>start logging your history today!</span>">
                                <strong>1-Year</strong> History Archive 
                            </span>
                    </li>
                    <li>
                            <span class="tip has-tip tip-right" data-toggle="popover" data-container="body"
	data-placement="right" data-content="<span class='tip-highlight'>Export your dashboard data to .CSV</span>,
                                  <br>and take your data where ever you need it to go.">
                                Export Data
                            </span>
                    </li>
                    <li>
                            <span class="tip has-tip tip-right" data-toggle="popover" data-container="body"
	data-placement="right" data-content="Invite up to 5 colleagues, co-workers or clients
                                    <br>to access your dashboard with their own login.">
                                5 Collaborators 
                            </span>
                    </li>
                    <li>
                            <span class="tip has-tip tip-right" data-toggle="popover" data-container="body"
	data-placement="right" data-content="1 Primary Admin user + 5 collaborators with
                                  Viewer access.">
                                Basic Roles & Permissions 
                            </span>
                    </li>
                    <li>
                            <span >
                                &nbsp;
                            </span>
                    </li>
                    <li>
                            <span >
                                &nbsp;
                            </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>


    <!-- Agencies -->
    <div id="agencies">
        <div class="span3 price-badge">
            <div>
                <h4 class="price" style="line-height:38px;">
                    <div class="contact-text">
                        <a href="<?=$agency_link;?>"
                           style="cursor:pointer;"
                           class="hidden-link">Contact Us</a></div>
                </h4>
                <h5 class="highlight-imp-grey">Enterprise</h5>
                <h6>For large brands and agencies</h6>
                <a href="<?= $agency_link;?>"
                   style="cursor:pointer;"
                   class="button btn btn-success" <?= $agency_track; ?> target="_blank">
                    <?=$agency_button;?></a>
                <button class="button btn" disabled="disabled">You Have This Plan
                </button>
                <div class="sub-cta"><?=$agency_sub_cta;?></div>
                <ul class="plan-details">
                    <li>
                            <span class="tip has-tip tip-left" data-toggle="popover" data-container="body"
	data-placement="left" data-content="Quickly find <span class='tip-highlight'>the perfect content
                                  <br>for your boards</span> to promote
                                  <br>your brand image and inspire
                                   <br>your audience.">
                                <strong>Content Discovery Engine</strong> 
                            </span>
                    </li>
                    <li>
                            <span class="tip has-tip tip-left" data-toggle="popover" data-container="body"
	data-placement="left" data-content="Uncover the most frequently used hashtags
                                  <br>related to your brand's organic activity.">
                                <strong>Hashtag Analysis</strong> 
                            </span>
                    </li>
                    <li>
                            <span class="tip has-tip tip-left" data-toggle="popover" data-container="body"
	data-placement="left" data-content="<span class='tip-highlight'>Contact us</span> to learn more.">
                                <strong>Influencer Campaigns</strong>
                            </span>
                    </li>
                    <li>
                            <span class="tip has-tip tip-left" data-toggle="popover" data-container="body"
	data-placement="left" data-content="<span class='tip-highlight'>Contact us</span> to learn more.">
                                <strong>Custom Campaign Tracking</strong>
                            </span>
                    </li>
                    <li>
                            <span class="tip has-tip tip-left" data-toggle="popover" data-container="body"
	data-placement="left" data-content="<span class='tip-highlight'>Contact us</span> to learn more.">
                                <strong>Advanced Industry Benchmarks</strong>
                            </span>
                    </li>
                    <li>
                            <span class="tip has-tip tip-left" data-toggle="popover" data-container="body"
	data-placement="left" data-content="Seamlessly manage all of your accounts with
                                        <br><span class='tip-highlight'>one login</span>
                                        and a <span class='tip-highlight'>unified dashboard</span>.">
                                <strong>Multi-Account Capability</strong> 
                            </span>
                    </li>
                    <li style="border-bottom:1px solid rgba(0,0,0,0.1);">
                            <span>
                                <i class="icon-plus" style="font-size:11px;"></i> All Pro Features
                            </span>
                    </li>
                </ul>
            </div>
            <div>
                <ul class="plan-features">
                    <li>
                            <span class="tip has-tip tip-left" data-toggle="popover" data-container="body"
	data-placement="left" data-content="Archive <span class='tip-highlight'>Unlimited Historical Data</span> on
                                         <br>all dashboard metrics.">
                                <strong>Unlimited</strong> History Archive 
                            </span>
                    </li>
                    <li>
                            <span class="tip has-tip tip-left" data-toggle="popover" data-container="body"
	data-placement="left" data-content="<span class='tip-highlight'>Export your dashboard data to .CSV</span>,
                                  <br>and take your data where ever you need it to go.">
                                Export Data
                            </span>
                    </li>
                    <li>
                            <span class="tip has-tip tip-left" data-toggle="popover" data-container="body"
	data-placement="left" data-content="Add an unlimited number of colleagues, co-workers
                                   <br>or clients to access your dashboard with
                                   <br>their own login.">
                                <strong>Unlimited</strong> Collaborators 
                            </span>
                    </li>
                    <li>
                            <span class="tip has-tip tip-left" data-toggle="popover" data-container="body"
	data-placement="left" data-content="Create custom roles and permissions across
                                  <br> your entire team and all of your accounts.">
                                <strong>Advanced </strong>Roles & Permissions 
                            </span>
                    </li>
                    <li>
                            <span class="tip has-tip tip-left" data-toggle="popover" data-container="body"
	data-placement="left" data-content="Get your own dedicated customer success manager to
                                  <br>make sure all of your needs are being met.">
                                Dedicated Support 
                            </span>
                    </li>
                    <li>
                            <span class="tip has-tip tip-left" data-toggle="popover" data-container="body"
	data-placement="left" data-content="Need invoicing? We've got you covered.">
                                Invoiced Billing 
                            </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>


    <div id="free">
        <div class="span3 free-wrapper">
            <div class="row price-badge free-right">
                <div>
                    <h5>FREE</h5>


                    <a href="<?= $free_link; ?>"
                        <?= $free_track; ?>
                       class="button btn free-button"
                       style="text-decoration:none;"
                    >
                        Downgrade to Free Plan
                    </a>




                    <button class="button btn" disabled="disabled">You Have This Plan
                    </button>
                    <ul class="plan-details">
                        <li>
                            <span class="tip has-tip tip-right" data-toggle="popover" data-container="body"
	data-placement="left" data-content="See current metrics for your profile and boards, and
                                  <br>dig into your most recent 250 pins.">
                                Basic Profile & Board Metrics 
                            </span>
                        </li>
                        <li>
                            <span class="tip has-tip tip-right" data-toggle="popover" data-container="body"
	data-placement="left" data-content="See basic metrics about <span class='tip-highlight'>what people are pinning
                                  <br>from your domain</span> recently.">
                                Track Basic Domain Metrics 
                            </span>
                        </li>
                        <li>
                            <span class="tip has-tip tip-right" data-toggle="popover" data-container="body"
	data-placement="left" data-content="<span class='tip-highlight'>Discover your most recent Followers</span>
                                  <br>and engage them.">
                                Engage Newest Followers 
                            </span>
                        </li>
                        <li>
                            <span >
                                &nbsp;
                            </span>
                        </li>
                        <li>
                            <span >
                                &nbsp;
                            </span>
                        </li>
                        <li>
                            <span >
                                &nbsp;
                            </span>
                        </li>
                        <li style="border-bottom:1px solid rgba(0,0,0,0.1);">
                            <span >
                                &nbsp;
                            </span>
                        </li>
                    </ul>
                </div>
                <div>
                    <ul class="plan-details">
                        <li>
                            <span class="tip has-tip tip-right" data-toggle="popover" data-container="body"
	data-placement="left" data-content="<span class='tip-highlight'>Limited 7-day historical archive</span> available for
                                         <br>brand trends, followers and pin activity.">
                                7-Day History Archive*  
                            </span>
                        </li>
                        <li>
                            <span >
                                &nbsp;
                            </span>
                        </li>
                        <li>
                            <span >
                                &nbsp;
                            </span>
                        </li>
                        <li>
                            <span >
                                &nbsp;
                            </span>
                        </li>
                        <li>
                            <span >
                                &nbsp;
                            </span>
                        </li>
                        <li>
                            <span >
                                &nbsp;
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>


    </div>


    </div>
    </div>
    </div>

<div class="row-fluid">
    <div id="social-proof" style="margin-bottom:0px;">
        <div class="row margin-fix">
            <div class="span12 pricing-table">
                <div class="pricing-wrapper full-feature-button-wrapper fixed">
                    <div class="under text-center full-feature-button fixed">
                        <a onclick="$('#main-content-scroll').animate({scrollTop: $('.full-feature-button-wrapper').offset().top+0}, 500);$('.feature-table').fadeToggle(500,function(){setTimeout($('#feature-toggle').html($('.feature-table').is(':visible') ? '<strong>Hide Full Feature Comparison</strong>' : '<strong>Show Full Feature Comparison</strong>'),5000);});
                        return false;">
                            <button id="feature-toggle" class="btn btn-large all-features fixed"><strong>See Full Feature Comparison</strong></button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row-fluid">
    <div id="social-proof" style="margin:0;">
        <div class="row margin-fix">
            <div class="span12 pricing-table">
                <div class="pricing-wrapper">

                    <?php include(base_path() . '/app/views/analytics/components/feature_grid.php') ?>

                </div>
            </div>
        </div>
    </div>
</div>

<div class="row-fluid">
    <div id="social-proof" class="call-us" style="margin-bottom:0;">
        <div class="row margin-fix">
            <div class="pricing-wrapper">
                <div class="text-center">
                    <h3 style="margin-bottom:10px;font-weight:normal;">Still not sure which plan fits you best?</h3>
                    <h4>We can build a plan to suit your needs and your budget.</h4>

                    <div class="text-center">
                        <div <button class="btn disabled" style="display:block;margin:40px auto 0;">Call Us: (405) 702-9998</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row-fluid">
    <div id="faq" class="faq">
        <div class="row-fluid">
            <div class="span12">
                <div class="pricing-wrapper">
                    <div class="row margin-fix" style='padding:0 30px;'>
                        <div class="span4">
                            <h4>Is there a setup fee?</h4>

                            <p>Enterprise plans require a setup fee, but otherwise, no way Jose.
                                <br>&nbsp;
                            </p>

                            <h4>Do I have to sign a long term contract?</h4>

                            <p>No. Tailwind is a pay as you go service, although discounts are available
                                for annual contracts.
                                <a href="mailto:bd@tailwindapp.com" target="_blank">Contact us</a>
                                to learn more.</p>
                        </div>
                        <div class="span4">
                            <h4>Do you offer custom plans?</h4>

                            <p>Yes! Contact us at (405) 702-9998 or via <a
                                    href="mailto:help@tailwindapp.com">email</a> and we'll put
                                together a plan that best fits your needs.
                                <br>&nbsp;
                            </p>

                            <h4>Can I cancel at anytime?</h4>

                            <p>
                                Yes, you may cancel your account immediately at any time.  Since
                                payment is made upfront for each month of service, a prorated credit is
                                applied to your account for the remainder of the current period.
                            </p>
                        </div>

                        <div class="span4">
                            <h4>How can I upgrade my plan?</h4>

                            <p>
                                It's easy! You can upgrade your plan by clicking on the green button above
                                for the plan you want to upgrade to.
                                The next time you are billed your new plan pricing will take effect.
                            </p>
                            <?php
                            if($customer->plan()->plan_id == 1){
                                ?>
                                <h4>Do I need to add my credit card to upgrade?</h4>

                                <p>No credit card is required to get up and running with the Free Starter Plan.
                                    Paid accounts do require billing information on file.
                                </p>
                            <?php
                            } else {
                                ?>



                            <?php
                            }
                            ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row-fluid">
    <div id="social-proof">
        <div class="row">
            <div class="small-12 columns text-center">
                <h2><strong>Have more questions? Contact us anytime at <a
                            href="mailto:help@tailwindapp.com">help@tailwindapp.com</a>.</strong>
                </h2>
            </div>
        </div>
    </div>
    </div>
    </div>
    </div>
    </div>
    </div>
    </div>
