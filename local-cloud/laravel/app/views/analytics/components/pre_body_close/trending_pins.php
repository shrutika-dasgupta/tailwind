<script type="text/javascript">
    (function (w, d, load) {
        var script,
            first = d.getElementsByTagName('SCRIPT')[0],
            n = load.length,
            i = 0,
            go = function () {
                for (i = 0; i < n; i = i + 1) {
                    script = d.createElement('SCRIPT');
                    script.type = 'text/javascript';
                    script.async = true;
                    script.src = load[i];
                    first.parentNode.insertBefore(script, first);
                }
            }
        if (w.attachEvent) {
            w.attachEvent('onload', go);
        } else {
            w.addEventListener('load', go, false);
        }
    }(window, document,
            ['//assets.pinterest.com/js/pinit.js']
        ));
</script>