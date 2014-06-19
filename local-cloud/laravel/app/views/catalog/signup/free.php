<div id="signup">
    <div class="row welcome">
        <div class="small-12 columns">
            <h1>Hi <?= $name; ?>! <img src="<?= $profile_image_url; ?>"/></h1>
        </div>
    </div>
    <div class="row loading">
        <div class="small-12 text-center">
            <div class="cycle-slideshow"
                 data-cycle-slides="p"
                 data-cycle-loop="1"
                 data-cycle-speed="500"
                 data-cycle-timeout="5000"
                 data-cycle-delay="1000"
                >

                <p style="display:block;">
                    <img src="http://d3iavkk36ob651.cloudfront.net/img/loading.gif"> Fetching your pins...
                </p>

                <p>
                    <img src="http://d3iavkk36ob651.cloudfront.net/img/loading.gif"> Analyzing your boards...
                </p>

                <p>
                    <img src="http://d3iavkk36ob651.cloudfront.net/img/loading.gif"> Collecting data on your Followers...
                </p>
                <p>
                    <img src="http://d3iavkk36ob651.cloudfront.net/img/loading.gif"> Completing Your Free Dashboard...
                </p>
                <p>
                    <i class="icon-checkmark"></i> Initial Data Collection Complete!
                </p>
            </div>
        </div>
    </div>

    <div class="row relative">
        <div id="background-images">
            <? $xx = 0;
                foreach ($pins as $row) {
                    ?>
                    <div class="row">
                        <? foreach ($row as $column) { ?>
                            <div class="small-12 large-6 columns">
                                <ul class="small-block-grid-11">
                                    <? foreach ($column as $pin) { ?>
                                        <li>
                                            <img style="visibility: hidden;" id="pin<?= $xx; ?>"
                                                 src="<?= $pin->image_square_url; ?>"/>
                                        </li>
                                        <? $xx++;
                                    } ?>
                                </ul>
                            </div>
                        <? } ?>
                    </div>
                <? } ?>
        </div>
        <div class="row what-we-doin">
            <div class="large-10 small-12 large-centered small-centered columns">
                <div class="cycle-slideshow"
                     data-cycle-slides="p"
                     data-cycle-loop="1"
                     data-cycle-speed="500"
                     data-cycle-timeout="5000"
                    >
                    <p style="display: block">
                        <br><br>
                        <img src="http://d3iavkk36ob651.cloudfront.net/img/loading2.gif">
                    </p>

                    <p>
                        <br><?= $pins_advice; ?>
                    </p>

<!--                <p>Collecting ourselves after seeing how impressive --><?//= $username; ?><!--'s profile is...</p>-->

                    <p>
                        <br><?= $boards_advice; ?>
                    </p>

                    <p>
                        <br><?= $followers_advice; ?>
                    </p>

                    <p>
                        <br><br>Your analytics are almost ready...
                        <br><br><span class="headline"><strong style="line-height: 1.5em;">Create an Account to
                                <br>Claim Your Free Dashboard!</strong></span>
                    </p>
                </div>

            </div>
        </div>
    </div>
    <div class="row relative cta-top" style="background-image: url('http://www.tailwindapp.com/img/bgnoise_lgblue5.png');">
<!--        <a class="skip text-right" href="#">Skip</a>-->
        <div id="tour" class="row">

            <div class="large-10 large-centered small-12 small-centered columns"style="background-image: url('http://www.tailwindapp.com/img/bgnoise_lgblue5.png');" >
                <i class="icon-angle-right"></i>
                <div class="row">
                    <form method="POST" action="<?= $post_domain;?>/signup/free">
                        <div class="large-4 small-12 columns form">
                            <input type="text" name="name" style="display: none" placeholder="Please ignore"/>
                            <input type="hidden" name="username" value="<?= $username; ?>"/>
                            <input type="hidden" name="email_address" type="email"/>
                            <input type="hidden" name="source" value="<?= $source; ?>"/>
                            <input class="input-email" type="email" name="email" placeholder="Enter your email"
                                   required>
                        </div>
                        <div class="large-4 small-12 columns form"?>
                            <input type="password" name="password" placeholder="Create a password" pattern='.{6,}'
                                   title='Thanks'
                                   oninvalid="setCustomValidity('Minimum length: 6 characters')"
                                   onchange="try{setCustomValidity('')}catch(e){}"
                                   onfocus="analytics.page( '/funnel/clickpwdfield');"
                                   required>
                        </div>
                        <div class="large-4 small-12 columns form"?>
                            <button type="submit" class="btn btn-gold btn-large"
                                    onsubmit="analytics.page('/funnel/submitaccount');">Claim Free Dashboard</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>


    <div id="faq" class="faq">
        <div class="row">
            <div class="small-12 columns">
                <ul class="small-block-grid-3">

                    <li>
                        <h4>
                            Why should I sign up? What do I get?
                        </h4>

                        <p>
                            With your Free Dashboard, you'll be able to
                        <ul>
                            <li>Track Profile Growth and Engagement</li>
                            <li>Analyze your Boards, Categories and latest pins</li>
                            <li>Find Trending Pins & Pinners for your Domain</li>
                            <li>and much more...</li>
                        </ul>
                        </p>
                    </li>
                    <li>
                        <h4>Do I need a credit card?</h4>

                        <p>No credit card is required to try out our Free Plan.
                            If you are signing up for one of our paid options we
                            will ask for a credit card to be put on file.</p>
                    </li>
                    <li>
                        <h4>Will I be charged?</h4>

                        <p>No! Your Free Dashboard is.. um..  Free :)</p>
                    </li>
                </ul>
                <ul class="small-block-grid-3">
                    <li>
                        <h4>When will all my data be available?</h4>

                        <p>Your dashboard should be ready almost <u>instantly</u> after you sign up.
                            If you have many thousands of pins, it may take an extra few minutes for us
                            to finish gathering your data completely.</p>
                    </li>
                    <li>
                        <h4>How often will the data in my dashboard be updated?</h4>
                        <p>
                            Some metrics will update every time you login to your dashboard, while others may be
                            updated every few hours.  No matter what, all data is refreshed <em>at least</em> once a day.
                        </p>
                    </li>
                    <li>
                        <h4>Is my email safe?</h4>
                        <p>
                            We take great care in storing all customer emails securely.  Your email will only
                            be used for communication related to your account and important product updates.
                            We HATE SPAM, too.
                        </p>
                    </li>

                </ul>
            </div>
        </div>
    </div>
    <div class="row relative cta-bottom">
        <div id="tour" class="row" style="display:block;">

            <div class="large-10 large-centered small-12 small-centered columns">
                <div class="row">
                    <form method="POST" action="http://analytics.tailwindapp.com/signup/free">
                        <div class="large-4 small-12 columns form">
                            <input type="text" name="name" style="display: none" placeholder="Please ignore"/>
                            <input type="hidden" name="username" value="<?= $username; ?>"/>
                            <input type="hidden" name="source" value="<?= $source; ?>"/>
                            <input class="input-email" type="email" name="email" placeholder="Enter your email"
                                   required>
                        </div>
                        <div class="large-4 small-12 columns form"?>
                        <input type="password" name="password" placeholder="Create a password" pattern='.{6,}'
                               title='Thanks'
                               oninvalid="setCustomValidity('Minimum length: 6 characters')"
                               onchange="try{setCustomValidity('')}catch(e){}"
                               onfocus="ganalytics.page( '/funnel/clickpwdfield');"
                               required>
                        </div>
                        <div class="large-4 small-12 columns form"?>
                            <button type="submit" class="btn btn-warning btn-large"
                                    onsubmit="analytics.page('/funnel/submitaccount');">Create Free Dashboard</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="social-proof" class="signup">
        <div class="row">
            <div class="small-12 columns text-center">
                <h2><strong>Have more questions? Contact us anytime at <a href="mailto:help@tailwindapp.com">help@tailwindapp.com</a>.</strong></h2>
            </div>
        </div>
    </div>

    <div id="faq" class="faq logos">
        <div class="row">
            <div class="small-12 large-12 columns faq-logos">
                <h2>You're in Good Company</h2>
                <ul class="small-block-grid-4 large-block-grid-8">
                    <li><img src="http://d3iavkk36ob651.cloudfront.net/img/logos/2x/target.png"/></li>
                    <li><img src="http://d3iavkk36ob651.cloudfront.net/img/logos/2x/disney.png"/></li>
                    <li><img src="http://d3iavkk36ob651.cloudfront.net/img/logos/2x/walmart.png"/></li>
                    <li><img src="http://d3iavkk36ob651.cloudfront.net/img/logos/2x/nike.png"/></li>
                    <li><img src="http://d3iavkk36ob651.cloudfront.net/img/logos/2x/microsoft.png"/></li>
                    <li><img src="http://d3iavkk36ob651.cloudfront.net/img/logos/2x/humanesociety.png"/></li>
                    <li><img src="http://d3iavkk36ob651.cloudfront.net/img/logos/2x/aol.png"/></li>
                    <li><img src="http://d3iavkk36ob651.cloudfront.net/img/logos/2x/footlocker.png"/></li>
                </ul>
            </div>
        </div>
    </div>

</div>

<!-- begin olark code -->
<script data-cfasync="false" type='text/javascript'>/*<![CDATA[*/window.olark||(function(c){var f=window,d=document,l=f.location.protocol=="https:"?"https:":"http:",z=c.name,r="load";var nt=function(){
        f[z]=function(){
            (a.s=a.s||[]).push(arguments)};var a=f[z]._={
        },q=c.methods.length;while(q--){(function(n){f[z][n]=function(){
            f[z]("call",n,arguments)}})(c.methods[q])}a.l=c.loader;a.i=nt;a.p={
            0:+new Date};a.P=function(u){
            a.p[u]=new Date-a.p[0]};function s(){
            a.P(r);f[z](r)}f.addEventListener?f.addEventListener(r,s,false):f.attachEvent("on"+r,s);var ld=function(){function p(hd){
            hd="head";return["<",hd,"></",hd,"><",i,' onl' + 'oad="var d=',g,";d.getElementsByTagName('head')[0].",j,"(d.",h,"('script')).",k,"='",l,"//",a.l,"'",'"',"></",i,">"].join("")}var i="body",m=d[i];if(!m){
            return setTimeout(ld,100)}a.P(1);var j="appendChild",h="createElement",k="src",n=d[h]("div"),v=n[j](d[h](z)),b=d[h]("iframe"),g="document",e="domain",o;n.style.display="none";m.insertBefore(n,m.firstChild).id=z;b.frameBorder="0";b.id=z+"-loader";if(/MSIE[ ]+6/.test(navigator.userAgent)){
            b.src="javascript:false"}b.allowTransparency="true";v[j](b);try{
            b.contentWindow[g].open()}catch(w){
            c[e]=d[e];o="javascript:var d="+g+".open();d.domain='"+d.domain+"';";b[k]=o+"void(0);"}try{
            var t=b.contentWindow[g];t.write(p());t.close()}catch(x){
            b[k]=o+'d.write("'+p().replace(/"/g,String.fromCharCode(92)+'"')+'");d.close();'}a.P(2)};ld()};nt()})({
        loader: "static.olark.com/jsclient/loader0.js",name:"olark",methods:["configure","extend","declare","identify"]});
    /* custom configuration goes here (www.olark.com/documentation) */

    olark('api.chat.updateVisitorNickname', {
        snippet: "<?= $name; ?>"
    });
    olark('api.visitor.updateCustomFields', {
        username: "<?= $username; ?>",
        followers: "<?= $followers; ?>",
        pin_count: "<?= $pin_count; ?>"
    });

    olark.identify('8229-186-10-2062');/*]]>*/</script><noscript><a href="https://www.olark.com/site/8229-186-10-2062/contact" title="Contact us" target="_blank">Questions? Feedback?</a> powered by <a href="http://www.olark.com?welcome" title="Olark live chat software">Olark live chat software</a></noscript>
<!-- end olark code -->