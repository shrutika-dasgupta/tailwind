<div class="module module-trending-topics widget">
    <a href="/discover" class="row-fluid out-of-face">
        <span class="pull-right"><i class="icon-resistor"></i></span>
        <h4 class="title">
            Trending on Pinterest: <span class="pop">love</span>
        </h4>
    </a>

    <div class="row-fluid">

        <table class="table table-condensed">
            <tbody>
            <tr>
                <td class="pins">
                    <?php $first = 'first'; /** @var $pin Pin */ foreach($pins as $pin) { ?>

                    <a target="_blank" href="http://pinterest.com/pin/<?= $pin->pin_id;?>">
                        <img class="<?= $first;?>" src="<?= $pin->image_url;?>" >
                    </a>
                    <?php $first = ''; ?>
                    <?php } ?>
                </td>
            </tr>
            </tbody>
        </table>
        <div class="row-fluid">
            <a class="btn btn-info btn-small" href="/listening?ref=dashboard">See
                more Trending</a>
        </div>
    </div>
</div>
