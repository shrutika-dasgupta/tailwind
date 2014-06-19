<script type="application/ld+json">
{
  "@context": "http://schema.org",
  "@type": "EmailMessage",
  "action": {
    "@type": "ViewAction",
    "url": "https://analytics.tailwindapp.com/profile",
    "name": "See Pins"
  },
  "description": "Summary email from Tailwind"
}
</script>
<!-- Text -->
<table width="550" cellspacing="0" cellpadding="0" border="0" align="center" class="textCentered">
    <tbody>

    <tr>
        <!-- Text -->
        <td width="100%" valign="top" style="font-size: 12px; color: #656565; text-align: center; font-weight: 200; font-family: Helvetica, Arial, sans-serif; line-height: 24px;">
            <?= $timeframe; ?>
        </td>
    <tr>
        <td width="100%" height="20"></td>
    </tr>
    </tr>


    </tbody>
</table>
<? foreach ($sections as $section) { ?>

    <?= $section; ?>




    <!-- Devider -->
    <table width="590" cellspacing="0" cellpadding="0" border="0" align="center" class="devider">
        <tbody>
        <tr>
            <td width="20"></td>
            <td width="550" height="90"></td>
            <td width="20"></td>
        </tr>
        </tbody>
    </table>

<? } ?>

<table width="590" cellspacing="0" cellpadding="0" border="0" align="center" class="scaleForMobile">
    <tbody>
    <tr>
        <td width="20"></td>
        <td width="550">

            <!-- Text -->
            <table width="550" cellspacing="0" cellpadding="0" border="0" align="center" class="textCentered">
                <tbody>
                <tr>
                    <!-- Headline -->
                    <td width="100%" style="font-size: 16px; color: #343434; text-align: center; font-weight: bold; font-family: Helvetica, Arial, sans-serif; line-height: 28px;">
                        <a href="#" style="text-decoration: none; color: #343434;">
                            Love these emails? Hate them?
                        </a>
                    </td>
                </tr>
                <tr>
                    <td width="100%" height="5"></td>
                </tr>
                <tr>
                    <!-- Text -->
                    <td width="100%" valign="top" style="font-size: 14px; color: #656565; text-align: center; font-weight: 200; font-family: Helvetica, Arial, sans-serif; line-height: 24px;">
                        Tell us what you think! Just respond to this email and it will go directly to the team :)
                    </td>
                </tr>
                <tr>
                    <td width="100%" height="20"></td>
                </tr>

                </tbody>
            </table>

        </td>
        <td width="20"></td>
    </tr>
    </tbody>
</table>