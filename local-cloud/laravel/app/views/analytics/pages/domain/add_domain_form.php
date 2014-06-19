<?php
/**
 * @author Alex
 * Date: 4/25/14 1:52 AM
 * 
 */
?>

<div class="" style='margin-bottom:10px;'>
    <div class='clearfix'></div>

    <?php if(isset($_GET['e'])): ?>
        <?php if($_GET['e']==2): ?>
            <div class='alert alert-error'><strong>Whoops!</strong> Something went wrong and we were not able to add your domain.  Please make sure it was typed in correctly and try again.  If this problem persists, please contact us by clicking the <i class='icon-help'></i> icon in the upper-right corner and we'll straighten it out for you right away!</div>
        <?php elseif($_GET['e']==3): ?>
            <div class='alert alert-error'><strong>Whoops!</strong> Please enter a domain first!</div>
        <?php endif ?>
    <?php endif ?>


    <h3 style='font-weight:normal; text-align:center'>
        Get Insights on Organic Pinning Activity coming from your website.
    </h3>

    <hr>

    <h3 style='text-align:center'>
        Enter your Domain...
    </h3>

</div>




<div class="brand-mentions">
    <div class="">
        <div class='row no-site' style='margin-top:20px'>
            <center>
                <form action='/domain/add-domain' method='POST' class="">
                    <fieldset>

                        <div class="control-group">
                            <div class="controls">
                                <div class="input-prepend input-append">
                                    <span class="add-on">
                                        <i class="icon-earth"></i> http://
                                    </span>
                                    <input class="input-xlarge" style='margin-left: -4px;' data-minlength='0' value="<?=$cust_domain ?>" id="appendedInputButton" type="text" name='domain' placeholder='e.g. "mysite.com"' pattern='^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}$'>
                                    <button type="submit" class="btn btn-success"'>
                                        Add Your Domain
                                    </button>
                                </div>
                            </div>

                            <h4 style="text-align:center;font-weight:normal;line-height:26px;">
                                Track <u>Impressions</u>,
                                <br>Uncover <u>Trending Images</u>,
                                <br>Discover <u>Influential Brand Advocates</u>,
                                <br>Analyze <u>Hashtags</u> and much more!
                            </h4>

                            <div class="form-actions">
                                <center>
                                    <span class='muted' style='width:50%'>
                                        <small class='muted'>"http://" and "www" not required. Only domains and subdomains can be tracked.
                                        <br>Sub-directories cannot currently be tracked on Pinterest.
                                        <br><span class='text-success'><strong>Trackable:</strong></span> etsy.com, macys.com, yoursite.tumblr.com
                                        <br><span class='text-error'><strong>Not Trackable:</strong></span> etsy.com/shop/mystore, macys.com/mens-clothing</small>
                                    </span>
                                </center>
                            </div>

                    </fieldset>
                </form>
            </center>
        </div>
    </div>
</div>