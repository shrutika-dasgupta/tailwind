<?php
/**
 * @author Alex
 * Date: 8/28/13 11:26 PM
 *
 * TODO: <<<<< MUST GO BEFORE GOOGLE ANALYTICS TRACKING SCRIPT >>>>>>
 *
 */

?>

<!-- Load the Content Experiment JavaScript API client for the experiment -->

    <script src="//www.google-analytics.com/cx/api.js?experiment=AW0b_gpwTBGT36msU1ks_g"></script>

    <script>
        // Ask Google Analytics which variation to show the visitor.
        var chosenVariation = cxApi.chooseVariation();
    </script>

    <script>
        // Define JavaScript for each page variation of this experiment.
        var pageVariations = [
            function() {},  // Original: Do nothing. This will render the default HTML.
            function() {    // Variation 1: Banner Image
                document.getElementById('nav-trial-button').innerHTML = 'Get Pro for Free';
            },
            function() {    // Variation 2: Sub-heading Text
                document.getElementById('nav-trial-button').innerHTML = 'Go Pro for Free';
            },
            function() {    // Variation 3: Button Text
                document.getElementById('nav-trial-button').innerHTML = 'Try Pro for Free';
                $('#nav-trial-button').removeClass('btn-success');
                $('#nav-trial-button').addClass('btn-warning');
            },
            function() {    // Variation 4: Button Color
                document.getElementById('nav-trial-button').innerHTML = 'Get Pro for Free';
                $('#nav-trial-button').removeClass('btn-success');
                $('#nav-trial-button').addClass('btn-warning');
            },
            function() {    // Variation 5: Button Color
                document.getElementById('nav-trial-button').innerHTML = 'Go Pro for Free';
                $('#nav-trial-button').removeClass('btn-success');
                $('#nav-trial-button').addClass('btn-warning');
            }

        ];

        // Wait for the DOM to load, then execute the view for the chosen variation.
        $(document).ready(
        // Execute the chosen view
            pageVariations[chosenVariation]
        );
    </script>


