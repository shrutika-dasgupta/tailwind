(function(){
    "use strict";

    var options = {
        // The endpoint URL should always start with "//" to support both http and https.
        url: '//analytics.tailwindapp.com/publisher/draft-posts?source=bookmarklet',
        ajaxTimeout: 30000, //ms
        minWidth: 150,
        minHeight: 150
    };

    var domain = window.location.host;

    //check width of the window to set the proper styling for the dom elements
    var windowWidth = window.innerWidth;
    var columnCount = 5;
    if(windowWidth > 1650){
        columnCount = 6;
    } else if (windowWidth < 1260 && windowWidth >= 1024){
        columnCount = 4;
    } else if (windowWidth < 1024 && windowWidth >= 800){
        columnCount = 3;
    } else if (windowWidth < 800){
        columnCount = 2;
    }

    var rollWidth = windowWidth - 200;

    var templates = {
            widget:
                '<aside id="sc-container">' +
                    '<section id="sc-modal">' +
                        '<header id="sc-header">' +
                            '<h1>Select Pins to Schedule</h1>' +
                            '<div id="sc-close" class="sc-close" title="Close"><span class="cancel-text">Cancel &nbsp;</span> &#215;</div>' +
                        '</header>' +
                        '<div id="sc-body">' +
                            '<div id="sc-image-list"><p>No images found on this page</p></div>' +
                        '</div>' +
                    '</section>' +
                    '<footer id="sc-footer">' +
                        '<div id="sc-image-roll"></div>' +
                        '<div id="sc-submit-wrapper">' +
                            '<div class="sc-image-selected-counter">' +
                                '<span id="sc-image-selected-counter-number">0</span>' +
                                '<span class="sc-image-s">Image</span> </span>Selected</span>' +
                            '</div>' +
                            '<button id="sc-submit" class="sc-submit sc-btn" type="button" disabled>Go Schedule!</button>' +
                            '<div id="sc-under-submit">Next step: configure times, choose boards, edit descriptions, etc.</div>' +
                        '</div>' +
                        '<div id="sc-status"></div>' +
                    '</footer>' +
                '</aside>'
    };


    // Stylesheet could be moved to external file in future. To make its editing easier.
    var stylesheetText =
        '#sc-container { transition: 0.3s opacity; opacity: 0; font-size: 18px; font-family: "Helvetica Neue",Helvetica,Arial,sans-serif !important; -moz-box-sizing: border-box; box-sizing: border-box; transition: 0.3s all; position: fixed; top:0; left: 0; right: 0; bottom: 0; width: 100%; height: 100%; background-color: #fff; z-index: 99999999999; padding: 0 0 40px 0; }' +
        '#sc-container * { font-family: "Helvetica Neue",Helvetica,Arial,sans-serif !important; }' +
        '#sc-modal { -moz-box-sizing: border-box; box-sizing: border-box; width: 100%; height: 100%; overflow: auto; padding: 0; }' +
        '#sc-header { position:fixed; height: 40px; width: 100%; background-color: #fff; border-bottom: 1px solid #EEEEEE; box-shadow: 0px 2px 3px rgba(0,0,0,0.1);}' +
        '#sc-header > h1 { margin: 5px 0 0 20px; font-size: 25px; font-weight: bold; line-height: 30px; color: #333333; float:left }' +
        '#sc-body { padding: 60px 15px 100px 15px; overflow: hidden; -webkit-column-count: ' + columnCount + '; -webkit-column-gap: 10px; -moz-column-count: ' + columnCount + '; -moz-column-gap: 10px; column-count: ' + columnCount + '; column-gap: 10px;}' +
        '#sc-footer { position: absolute; bottom: 0; left: 0; right: 0; width: 100%; height: 100px; text-align: left; background-color: #F5F5F5; border-top: 1px solid #DDDDDD; box-shadow: 0 1px 0 #FFFFFF inset; padding:0px;}' +
        'sc-image-list { line-height: 300px; margin: auto; padding: 0; }' +
        '#sc-image-list > li { float: left; width: 250px; height: 250px; list-style: none; line-height: 250px; text-align: center; padding: 0; margin: 10px 0 0 10px; }' +
        '.sc-image-wrapper {background: #999; cursor: pointer; display: inline-block; margin:10px 10px 20px; font-size:11px; border-radius:3px; box-shadow: 0px 0px 3px 1px rgba(0,0,0,0.3); width:236px;-moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box; text-align:center;}' +
        '.sc-image-wrapper:hover { box-shadow: 0px 0px 5px 1px rgba(0,174,230,0.8); }' +
        '.sc-image-wrapper.sc-image-selected { box-shadow: 0px 0px 5px 3px rgba(0,174,230,0.8); }' +
        '.sc-image { vertical-align: middle; -moz-box-sizing: border-box; box-sizing: border-box; transition: 0.1s border; max-width: 100%; max-height: 100%; display: inline-block; border-top-left-radius:3px; border-top-right-radius:3px;}' +
        '.sc-image-size {background: #ddd; padding: 1px; border-bottom: 1px solid rgba(0, 0, 0, 0.1); border-top: 1px solid rgba(0, 0, 0, 0.1);}' +
        '.sc-image-meta {background: #fff; display:block; text-align:left; padding: 14px 14px;}' +
        '.sc-domain-meta {background: #fff; padding: 14px 14px; text-align:left;border-bottom-left-radius:3px; border-bottom-right-radius:3px; border-top:1px solid rgba(0,0,0,0.1);}' +
        '.sc-domain-meta img {vertical-align:middle; margin-right:5px;}' +
        '.sc-close { position: absolute; background:#fff; height:34px; top: 0px; right: 0px; font-size: 30px; font-weight: bold; line-height: 30px; margin: 0; padding: 3px 3px 3px 15px; border-left: 1px solid rgba(0,0,0,0.2); opacity: 0.5; cursor: pointer; }' +
        '.sc-close:hover { background: #ddd; }' +
        '.sc-close .cancel-text {font-size:22px;}' +
        '#sc-image-details { position: relative; display: inline-block; padding: 3px 0 }' +
        '.sc-image-selected-counter { margin: 0 0 0px 0px; font-size: 14px; line-height: 25px; display: inline-block;}' +
        '#sc-image-selected-counter-number { color: red; margin: 0 7px 0 0 }' +
        '#sc-image-selected-counter-number:not([data-value="1"]) ~ .sc-image-s:after { content: "s"}' +
        '#sc-submit {  }' +
        '#sc-under-submit {color: #555; font-size: 10px; line-height: 9px; margin-top: 5px;}' +
        '#sc-status { position: absolute; top: 9px; right: 150px; color: red; min-width: 16px; min-height: 16px; }' +
        '.sc-btn { outline: 0 none; background-color: #006DCC; background-image: linear-gradient(to bottom, #0088CC, #0044CC); background-repeat: repeat-x; border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25); color: #FFFFFF; text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25); border-image: none; border-radius: 4px 4px 4px 4px; border-style: solid; border-width: 1px; box-shadow: 0 1px 0 rgba(255, 255, 255, 0.2) inset, 0 1px 2px rgba(0, 0, 0, 0.05); cursor: pointer; display: inline-block; font-size: 14px; font-weight: bold; line-height: 25px; margin-bottom: 0; padding: 4px 12px; text-align: center; vertical-align: middle }' +
        '.sc-btn:hover { background-color: #0044CC; color: #FFFFFF; background-position: 0 -15px; transition: background-position 0.1s linear 0s; }' +
        '.sc-btn:active { background-color: #0044CC; color: #FFFFFF; background-image: none; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15) inset, 0 1px 2px rgba(0, 0, 0, 0.05); outline: 0 none; }' +
        '.sc-btn:disabled { background-image: none; box-shadow: none; cursor: default; opacity: 0.65; background-color: #0044CC; color: #FFFFFF; }' +
        '.sc-submit-success #sc-status:after { content: "Success"; }' +
        '.sc-submit-fail #sc-status:after { content: "Fail"; }' +
        '.sc-no-image #sc-status:after { content: "Please select an image"; }' +
        '#sc-image-roll {float:left; height:100px; overflow-x:auto; position:relative; width:' + rollWidth + 'px;}' +
        '.sc-image-roll-item { position:relative; }' +
        '.sc-image-roll-item:after {content:\'Click to Remove Image\'; color: #eee; font-size: 12px; top:45px; right: 0px; padding:0px 4px 1px; display:inline-block; background:#000; border-radius: 15px;margin-top:5px; opacity:0; position:absolute; z-index:1;}' +
        '.sc-image-roll-item:hover {}' +
        '.sc-image-roll-item:hover img {opacity: 0.7;}' +
        '.sc-image-roll-item:hover:after {z-index:5; opacity: 0.9;}' +
        '.sc-image-in-roll {float:left; padding:5px; max-height:90px; position:relative; z-index:0; cursor: pointer;}' +
        '#sc-submit-wrapper {right:0px; position:absolute; text-align:center;padding:5px;width:180px;}'
        '.sc-submit-process #sc-status{ background: center center no-repeat; background-image: url(data:image/gif;base64,R0lGODlhEAAQAPIAAP///wAAAMLCwkJCQgAAAGJiYoKCgpKSkiH/C05FVFNDQVBFMi4wAwEAAAAh/hpDcmVhdGVkIHdpdGggYWpheGxvYWQuaW5mbwAh+QQJCgAAACwAAAAAEAAQAAADMwi63P4wyklrE2MIOggZnAdOmGYJRbExwroUmcG2LmDEwnHQLVsYOd2mBzkYDAdKa+dIAAAh+QQJCgAAACwAAAAAEAAQAAADNAi63P5OjCEgG4QMu7DmikRxQlFUYDEZIGBMRVsaqHwctXXf7WEYB4Ag1xjihkMZsiUkKhIAIfkECQoAAAAsAAAAABAAEAAAAzYIujIjK8pByJDMlFYvBoVjHA70GU7xSUJhmKtwHPAKzLO9HMaoKwJZ7Rf8AYPDDzKpZBqfvwQAIfkECQoAAAAsAAAAABAAEAAAAzMIumIlK8oyhpHsnFZfhYumCYUhDAQxRIdhHBGqRoKw0R8DYlJd8z0fMDgsGo/IpHI5TAAAIfkECQoAAAAsAAAAABAAEAAAAzIIunInK0rnZBTwGPNMgQwmdsNgXGJUlIWEuR5oWUIpz8pAEAMe6TwfwyYsGo/IpFKSAAAh+QQJCgAAACwAAAAAEAAQAAADMwi6IMKQORfjdOe82p4wGccc4CEuQradylesojEMBgsUc2G7sDX3lQGBMLAJibufbSlKAAAh+QQJCgAAACwAAAAAEAAQAAADMgi63P7wCRHZnFVdmgHu2nFwlWCI3WGc3TSWhUFGxTAUkGCbtgENBMJAEJsxgMLWzpEAACH5BAkKAAAALAAAAAAQABAAAAMyCLrc/jDKSatlQtScKdceCAjDII7HcQ4EMTCpyrCuUBjCYRgHVtqlAiB1YhiCnlsRkAAAOwAAAAAAAAAAAA==); }';


    // If bookmarklet already running - exit.
    if ( document.getElementById('sc-container') ) return;

    // Copy filter and reduce methods to Array constructor, for non-Firefox based browsers
    Array.filter = Array.filter || function(a,f) {
        return Array.prototype.filter.call(a,f);
    };

    Array.reduce = Array.reduce || function(a,f,i) {
        return Array.prototype.reduce.call(a,f,i);
    };

    options.url = window.location.protocol + options.url;

    var head = document.getElementsByTagName('head')[0],
        body = document.body,

        styleElem = document.createElement('style');


    styleElem.id = 'sc-stylesheet';
    styleElem.innerHTML = stylesheetText;
    head.appendChild(styleElem);
    // insertAdjacentHTML doesn't work for stylesheets in IE9!
    // head.insertAdjacentHTML('beforeEnd', stylesheetText);

    body.insertAdjacentHTML('beforeEnd', templates.widget);
    body.style.overflow = 'hidden';

    var widget = document.getElementById('sc-container'),
        widgetStylesheet = document.getElementById('sc-stylesheet'),

        submitButton = document.getElementById('sc-submit'),
        imageListElem = document.getElementById('sc-image-list'),
        selectedImagesRoll = document.getElementById('sc-image-roll'),
        selectedImagesCounter = document.getElementById('sc-image-selected-counter-number'),

        filteredImages = getImages();


    // Render images only if filteredImages array contain any, or skip.
    filteredImages.length && renderImages();

    widget.addEventListener('click', onClick, false);

    // Wrapped in setTimeout to allow browser to animate this property change.
    setTimeout( function() { widget.style.opacity = 1; }, 0);

    initUsageTracking();

    function renderImages() {
        imageListElem.innerHTML = filteredImages.reduce(function(str, elem, i, arr) {
            str += '<div class="sc-image-wrapper" id="image-' + i + '"><img class="sc-image" src="' + elem.src + '" title="'+ elem.title + '" alt="' + elem.alt +'"><div class="sc-image-size">' + elem.width + ' × ' + elem.height + '</div>';
            if(elem.alt != false){
                str += '<div class="sc-image-meta">' + elem.alt + '</div>'
            }
            str += '<div class="sc-domain-meta"><img height="16" width="16" src="http://www.google.com/s2/favicons?domain=' + domain + '" style="border-radius:15px;" /> ' + domain + ' </div></div>';
            return str;

        }, '');

        selectedImagesRoll.innerHTML = filteredImages.reduce(function(str, elem, i, arr) {
            return str += '<div class="sc-image-roll-item"><img class="sc-image-in-roll"  id="roll-image-' + i + '" style="margin-left: -9999px;" src="' + elem.src + '" title="'+ elem.title + '" alt="' + elem.alt +'"></div>';
        }, '');
    }

    function getImages() {
        return Array.filter(document.images, function(image, i, arr) {
            var width = image.naturalWidth,
                height = image.naturalHeight;

            return width > options.minWidth && height > options.minHeight;
        });
    }

    function onClick(e) {
        var target = e.target,
            targetClass = target.className,
            targetId = target.id;

        console.log(target.id);

        if ( hasClass(target, 'sc-close') || hasClass(target, 'cancel-text')) {
            exitWidget();
        } else if ( hasClass(target, 'sc-submit') ) {
            submitDataForm();
        } else if ( hasClass(target, 'sc-image-wrapper') ) {
            selectImageWrapper(target, targetId);
        } else if ( hasClass(target, 'sc-image')
            || hasClass(target, 'sc-image-size')
            || hasClass(target, 'sc-domain-meta')
            || hasClass(target, 'sc-image-meta')
            ) {
            selectImage(target.parentNode, target.parentNode.id);
        } else if ( hasClass(target, 'sc-image-in-roll') ) {
            selectImageRoll(target, targetId);
        }
    }

    function selectImageWrapper(image, imageId) {
        toggleClass(image, 'sc-image-selected');
        removeClass(widget, 'sc-no-image');

        var rollImage = document.getElementById('roll-' + imageId);

        if(rollImage.style.marginLeft == '-9999px'){
            rollImage.style.marginLeft = '0px';
        } else {
            rollImage.style.marginLeft = '-9999px';
        }

        var imageCount = imageListElem.getElementsByClassName('sc-image-selected').length;
        if(imageCount > 0){
            submitButton.removeAttribute('disabled');
        } else {
            submitButton.setAttribute('disabled', 'disabled');
        }
        selectedImagesCounter.setAttribute('data-value', imageCount);
        selectedImagesCounter.innerHTML = imageCount;
    }

    function selectImage(image, imageId) {
        toggleClass(image, 'sc-image-selected');
        removeClass(widget, 'sc-no-image');

        var rollImage = document.getElementById('roll-' + imageId);

        if(rollImage.style.marginLeft == '-9999px'){
            rollImage.style.marginLeft = '0px';
        } else {
            rollImage.style.marginLeft = '-9999px';
        }

        var imageCount = imageListElem.getElementsByClassName('sc-image-selected').length;
        if(imageCount > 0){
            submitButton.removeAttribute('disabled');
        } else {
            submitButton.setAttribute('disabled', 'disabled');
        }
        selectedImagesCounter.setAttribute('data-value', imageCount);
        selectedImagesCounter.innerHTML = imageCount;
    }

    function selectImageRoll(image, imageId) {
        if(image.style.marginLeft == '-9999px'){
            image.style.marginLeft = '0px';
        } else {
            image.style.marginLeft = '-9999px';
        }

        console.log("roll image id: " + imageId);
        var listId = imageId.substring(imageId.indexOf('image'), imageId.length);
        console.log("list id: " + listId);
        var listImage = document.getElementById(listId);

        toggleClass(listImage, 'sc-image-selected');
        removeClass(widget, 'sc-no-image');

        var imageCount = imageListElem.getElementsByClassName('sc-image-selected').length;
        if(imageCount > 0){
            submitButton.removeAttribute('disabled');
        } else {
            submitButton.setAttribute('disabled', 'disabled');
        }
        selectedImagesCounter.setAttribute('data-value', imageCount);
        selectedImagesCounter.innerHTML = imageCount;
    }

    function exitWidget() {
        body.style.overflow = '';
        body.removeChild(widget);
        head.removeChild(widgetStylesheet);
    }

    function submitDataForm() {
        postToUrl(options.url, composeData());
    }

    function submitDataAjax() {
        removeClass(widget, 'sc-submit-fail');
        removeClass(widget, 'sc-submit-success');

        var dataToSend = composeData();

        if (!dataToSend) return;

        var xhr = new XMLHttpRequest();

        if ( (new XMLHttpRequest()).withCredentials === undefined ) { // If IE9
            if (!window.XDomainRequest) throw new Error('XDR not supported');
            xhr = new XDomainRequest();

            xhr.timeout = options.ajaxTimeout;
            xhr.open('POST', options.url);
        } else { // If modern browsers, supporting cross-domain ajax calls.
            xhr.open('POST', options.url, true);

            xhr.timeout = options.ajaxTimeout;

            xhr.setRequestHeader('Content-type', 'text/plain');
        }

        xhr.onload = function() {
            // If can't parse response JSON obj. - setErrorState.
            try {
                var response = JSON.parse(this.responseText);
            } catch(e) {
                setErrorState();
            }

            if ( response.success == true) {
                setSuccessState();
                exitWidget();
            } else {
                setErrorState();
            }
        };

        xhr.onerror = function() {
            setErrorState();
        };

        xhr.send(dataToSend);

        addClass(widget, 'sc-submit-process');
        submitButton.setAttribute('disabled', ''); // Lock submit button
    }

    function composeData() {
        var selectedImages = imageListElem.querySelectorAll('.sc-image-selected img.sc-image');

        if (!selectedImages.length) {
            addClass(widget, 'sc-no-image');
            return false;
        }

        var data = {};
        data.items = Array.reduce(selectedImages, function(result, elem, i, arr) {
            result.push({
                'site-url'    : document.URL,
                'image-url'   : elem.src,
                'description' : elem.alt
            });

            return result;
        }, []);

        return JSON.stringify(data);
    }

    function setErrorState() {
        removeClass(widget, 'sc-submit-process');
        addClass(widget, 'sc-submit-fail');

        submitButton.removeAttribute('disabled');
    }

    function setSuccessState() {
        removeClass(widget, 'sc-submit-process');
        addClass(widget, 'sc-submit-success');

        submitButton.removeAttribute('disabled');
    }

    // Functions below only for IE9. As it doesn't support classList.
    function addClass(elem, cls) {
        if (document.documentElement.classList) {
            elem.classList.add(cls);
        } else {
            var c = elem.className ? elem.className.split(' ') : [];
            for (var i=0; i<c.length; i++) {
                if (c[i] == cls) return;
            }
            c.push(cls);
            elem.className = c.join(' ');
        }
    }

    function removeClass(elem, cls) {
        if (document.documentElement.classList) {
            elem.classList.remove(cls);
        } else {
            var c = elem.className.split(' ');
            for (var i=0; i<c.length; i++) {
                if (c[i] == cls) c.splice(i--, 1);
            }

            elem.className = c.join(' ');
        }
    }

    function hasClass(elem, cls) {
        if (document.documentElement.classList) {
            return elem.classList.contains(cls);
        }

        for (var c = elem.className.split(' '),i=c.length-1; i>=0; i--) {
            if (c[i] == cls) return true;
        }
        return false;
    }

    function toggleClass(elem, cls) {
        if (document.documentElement.classList) {
            elem.classList.toggle(cls);
        } else {
            if ( hasClass(elem, cls) ) {
                removeClass(elem, cls);
            } else {
                addClass(elem, cls);
            }
        }
    }

    function postToUrl(path, params, method, target) {
        method = method || "POST";
        target = target || "_blank";

        var form = document.createElement("form");
        form.setAttribute("method", method);
        form.setAttribute("action", path);
        form.setAttribute("target", target);

        var formField = document.createElement("input");
        formField.setAttribute("type", "hidden");
        formField.setAttribute("name", "data");
        formField.setAttribute("value", params);
        form.appendChild(formField);

        document.body.appendChild(form);
        form.submit();
    }

    function initUsageTracking() {
        console.log('INIT TRACKING...');

        // Google Analytics
        (function() {
            var ga   = document.createElement('script');
            ga.type  = 'text/javascript';
            ga.async = true;
            ga.src   = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';

            var s    = document.getElementsByTagName('script')[0];
            s.parentNode.insertBefore(ga, s);
  
            var url = "/bookmarklet/" + location.host + location.pathname;

            // Use "tailwindGA" as a 'namespace' to not screw up the real GA for the domain.
            var _gaq = _gaq || [];
            _gaq.push(['tailwindGA._setAccount', 'UA-33652774-1']);
            _gaq.push(['tailwindGA._setPageGroup', 4, 'Bookmarklet']);
            _gaq.push(['tailwindGA._trackPageview', url]);
        })();
    }
})();
